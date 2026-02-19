<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/BandService.php';
require_once __DIR__ . '/../../services/WebSocketService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);
$controller->validateCsrf();

$bandService = new BandService($db);

try {
    $result = $bandService->deactivateAll();
    if ($result) {
        // Write event trigger file for SSE (fallback)
        $storageDir = __DIR__ . '/../../storage';
        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);
        file_put_contents($storageDir . '/band_event.txt', time());

        // Notify WebSocket server — no active band
        WebSocketService::notify('band_change', [
            'band_id' => 0,
            'band'    => null
        ]);

        echo json_encode(['success' => true, 'message' => 'Band deactivated. No band is currently performing.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to deactivate band.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to deactivate band.']);
}
