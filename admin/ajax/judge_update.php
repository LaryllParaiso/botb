<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/JudgeService.php';

header('Content-Type: application/json');

$controller = new AdminController(Database::getInstance());
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);

$id       = intval($input['id'] ?? 0);
$name     = trim($input['name'] ?? '');
$email    = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $input['password'] ?? '';

if (empty($id) || empty($name) || empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

$service = new JudgeService(Database::getInstance());

if ($service->emailExists($email, $id)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already exists.']);
    exit;
}

try {
    $data = ['name' => $name, 'email' => $email];
    if (!empty($password)) {
        $data['password'] = $password;
    }
    $service->update($id, $data);
    echo json_encode(['success' => true, 'message' => 'Judge updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update judge.']);
}
