<?php
/**
 * AJAX endpoint: Update admin credentials
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = trim($input['current_password'] ?? '');
$newName          = trim($input['name'] ?? '');
$newEmail         = trim($input['email'] ?? '');
$newPassword      = trim($input['new_password'] ?? '');

if (empty($currentPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Current password is required to make changes.']);
    exit;
}

$adminId = $_SESSION['user_id'];

// Verify current password
$stmt = $db->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($currentPassword, $admin['password'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

// Build update
$updates = [];
$params  = [];

if (!empty($newName)) {
    $updates[] = "name = ?";
    $params[]  = $newName;
}

if (!empty($newEmail)) {
    // Check uniqueness
    $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$newEmail, $adminId]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use.']);
        exit;
    }
    $updates[] = "email = ?";
    $params[]  = $newEmail;
}

if (!empty($newPassword)) {
    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    $updates[] = "password = ?";
    $params[]  = password_hash($newPassword, PASSWORD_BCRYPT);
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No changes provided.']);
    exit;
}

$params[] = $adminId;
$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $db->prepare($sql);
$ok = $stmt->execute($params);

if ($ok) {
    // Update session name if changed
    if (!empty($newName)) {
        $_SESSION['user_name'] = $newName;
    }
    echo json_encode(['success' => true, 'message' => 'Admin credentials updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update credentials.']);
}
