<?php
/**
 * AJAX endpoint: Get all settings
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/SettingsService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$settingsService = new SettingsService($db);
$settings = $settingsService->getAll();

echo json_encode([
    'success'  => true,
    'settings' => $settings
]);
