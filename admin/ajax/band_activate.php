<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/BandService.php';
require_once __DIR__ . '/../../services/ScoreService.php';
require_once __DIR__ . '/../../services/WebSocketService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Band ID is required.']);
    exit;
}

$bandService  = new BandService($db);
$scoreService = new ScoreService($db);

// Check if there's a currently active band with pending judges
$activeBand = $bandService->getActiveBand();
if ($activeBand && $activeBand['id'] !== $id) {
    $totalJudges = $scoreService->getTotalJudgeCount();
    if ($totalJudges > 0) {
        $pendingJudges = $scoreService->getPendingJudges($activeBand['id']);
        if (count($pendingJudges) > 0) {
            $names = array_map(fn($j) => $j['name'], $pendingJudges);
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Cannot switch bands. The following judges have not yet submitted scores for "' . $activeBand['name'] . '": ' . implode(', ', $names),
                'pending_judges' => $pendingJudges
            ]);
            exit;
        }
    }
}

try {
    $result = $bandService->activate($id);
    if ($result) {
        // Write event trigger file for SSE (fallback)
        $storageDir = __DIR__ . '/../../storage';
        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);
        file_put_contents($storageDir . '/band_event.txt', time());

        // Notify WebSocket server
        $activeBandNow = $bandService->getActiveBand();
        WebSocketService::notify('band_change', [
            'band_id' => $activeBandNow ? (int)$activeBandNow['id'] : 0,
            'band'    => $activeBandNow
        ]);

        echo json_encode(['success' => true, 'message' => 'Band activated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to activate band.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to activate band.']);
}
