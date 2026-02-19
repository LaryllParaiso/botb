<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/BandService.php';

header('Content-Type: application/json');

$controller = new AdminController(Database::getInstance());
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);

$id                = intval($input['id'] ?? 0);
$name              = trim($input['name'] ?? '');
$round_id          = intval($input['round_id'] ?? 0);
$performance_order = intval($input['performance_order'] ?? 0);

if (empty($id) || empty($name) || empty($round_id) || empty($performance_order)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!in_array($round_id, [1, 2])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid round.']);
    exit;
}

$service = new BandService(Database::getInstance());

try {
    $service->update($id, [
        'name' => $name,
        'round_id' => $round_id,
        'performance_order' => $performance_order
    ]);
    echo json_encode(['success' => true, 'message' => 'Band updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update band.']);
}
