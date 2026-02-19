<?php
/**
 * AJAX endpoint: Save settings
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/SettingsService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input) || !is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No settings provided.']);
    exit;
}

// Only allow known keys
$allowedKeys = ['event_title', 'event_subtitle', 'head_name', 'head_title', 'signatories'];
$filtered = [];
foreach ($allowedKeys as $key) {
    if (isset($input[$key])) {
        if ($key === 'signatories' && is_array($input[$key])) {
            $filtered[$key] = json_encode($input[$key]);
        } else {
            $filtered[$key] = trim($input[$key]);
        }
    }
}

if (empty($filtered)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid settings provided.']);
    exit;
}

$settingsService = new SettingsService($db);
$ok = $settingsService->saveAll($filtered);

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Settings saved successfully.' : 'Failed to save settings.'
]);
