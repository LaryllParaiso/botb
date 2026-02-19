<?php
/**
 * AJAX endpoint: Admin delete all scores for a judge on a band
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

$judgeId = intval($input['judge_id'] ?? 0);
$bandId  = intval($input['band_id'] ?? 0);

if (!$judgeId || !$bandId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Judge ID and Band ID are required.']);
    exit;
}

$scoreService = new ScoreService($db);
$result = $scoreService->adminDeleteJudgeBandScores($judgeId, $bandId);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Scores deleted successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete scores.']);
}
