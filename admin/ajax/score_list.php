<?php
/**
 * AJAX endpoint: Get all scores grouped for admin management
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/ScoreService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$scoreService = new ScoreService($db);
$scores = $scoreService->getAllScoresGrouped();

echo json_encode([
    'success' => true,
    'scores'  => $scores
]);
