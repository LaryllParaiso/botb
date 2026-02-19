<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/JudgeService.php';

header('Content-Type: application/json');

$controller = new AdminController(Database::getInstance());
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Judge ID is required.']);
    exit;
}

$service = new JudgeService(Database::getInstance());

try {
    $service->delete($id);
    echo json_encode(['success' => true, 'message' => 'Judge deleted successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete judge.']);
}
