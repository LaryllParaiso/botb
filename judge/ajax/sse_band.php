<?php
/**
 * SSE (Server-Sent Events) endpoint for judges
 * Pushes real-time band change notifications to judge browsers
 *
 * CRITICAL: session_write_close() is called immediately after auth check
 * to release the session file lock. Without this, ALL other requests
 * from the same session are blocked until this long-lived connection ends.
 */
session_start();
require_once __DIR__ . '/../../config/db.php';

// Must be a logged-in judge
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'judge') {
    http_response_code(403);
    exit;
}

// Release session lock immediately — this is the key fix for slow requests
session_write_close();

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Disable output buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');
while (ob_get_level()) ob_end_flush();

$db = Database::getInstance();
$lastBandId = null;
$lastEventFile = __DIR__ . '/../../storage/band_event.txt';

// Send initial heartbeat
echo ":" . str_repeat(" ", 2048) . "\n\n"; // Flush buffer for some proxies
echo "retry: 3000\n\n";
flush();

$maxRuntime = 120; // Max 2 minutes per connection, then client reconnects
$startTime = time();

while (true) {
    // Check runtime limit
    if ((time() - $startTime) >= $maxRuntime) {
        echo "event: reconnect\ndata: timeout\n\n";
        flush();
        break;
    }

    // Check connection
    if (connection_aborted()) break;

    // Query current active band
    $stmt = $db->query(
        "SELECT b.id, b.name, b.round_id, r.name AS round_name
         FROM bands b JOIN rounds r ON b.round_id = r.id
         WHERE b.is_active = 1 LIMIT 1"
    );
    $activeBand = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentBandId = $activeBand ? (int)$activeBand['id'] : 0;

    // Detect change
    if ($lastBandId === null) {
        $lastBandId = $currentBandId;
        // Send initial state
        $payload = json_encode(['type' => 'init', 'band_id' => $currentBandId, 'band' => $activeBand]);
        echo "event: band_change\ndata: {$payload}\n\n";
        flush();
    } elseif ($currentBandId !== $lastBandId) {
        $lastBandId = $currentBandId;
        $payload = json_encode(['type' => 'change', 'band_id' => $currentBandId, 'band' => $activeBand]);
        echo "event: band_change\ndata: {$payload}\n\n";
        flush();
    }

    // Also check event file for faster detection (written by admin on activate)
    if (file_exists($lastEventFile)) {
        $eventTs = (int)@file_get_contents($lastEventFile);
        if ($eventTs > ($startTime - 1)) {
            // Re-query to get latest
            $stmt2 = $db->query(
                "SELECT b.id, b.name, b.round_id, r.name AS round_name
                 FROM bands b JOIN rounds r ON b.round_id = r.id
                 WHERE b.is_active = 1 LIMIT 1"
            );
            $freshBand = $stmt2->fetch(PDO::FETCH_ASSOC);
            $freshId = $freshBand ? (int)$freshBand['id'] : 0;
            if ($freshId !== $lastBandId) {
                $lastBandId = $freshId;
                $payload = json_encode(['type' => 'change', 'band_id' => $freshId, 'band' => $freshBand]);
                echo "event: band_change\ndata: {$payload}\n\n";
                flush();
            }
        }
    }

    // Heartbeat every loop
    echo ": heartbeat\n\n";
    flush();

    sleep(2); // Check every 2 seconds
}
