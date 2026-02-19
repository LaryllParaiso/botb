<?php
/**
 * AJAX endpoint: Admin reset ALL system data (scores, bands, judges)
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/ScoreService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);

// Require explicit confirmation keyword
$confirm = $input['confirm'] ?? '';
if ($confirm !== 'RESET_ALL') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Confirmation keyword required.']);
    exit;
}

$scoreService = new ScoreService($db);
$result = $scoreService->resetAllData();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'All data has been reset successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to reset data.']);
}
