<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/JudgeService.php';

header('Content-Type: application/json');

$controller = new AdminController(Database::getInstance());
$service = new JudgeService(Database::getInstance());

echo json_encode([
    'success' => true,
    'judges' => $service->getAll()
]);
