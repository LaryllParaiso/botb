<?php
/**
 * AJAX endpoint: Upload a logo image and save its path to settings
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/SettingsService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

// Validate field name (which logo slot)
$field = $_POST['field'] ?? '';
$allowedFields = ['logo_left', 'logo_right', 'logo_watermark'];

if (!in_array($field, $allowedFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid logo field.']);
    exit;
}

if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['logo'];
$allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PNG, JPG, GIF, WebP, SVG.']);
    exit;
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../../assets/uploads/logos/';
$ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
$filename = $field . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$webPath = '/BOB_SYSTEM/assets/uploads/logos/' . $filename;

$settingsService = new SettingsService($db);
$ok = $settingsService->saveAll([$field => $webPath]);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Logo uploaded successfully.' : 'Failed to save logo setting.',
    'path'    => $webPath
]);
