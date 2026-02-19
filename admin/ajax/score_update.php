<?php
/**
 * AJAX endpoint: Admin update a single score
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

$judgeId    = intval($input['judge_id'] ?? 0);
$bandId     = intval($input['band_id'] ?? 0);
$criteriaId = intval($input['criteria_id'] ?? 0);
$score      = floatval($input['score'] ?? -1);

if (!$judgeId || !$bandId || !$criteriaId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Judge ID, Band ID, and Criteria ID are required.']);
    exit;
}

// Validate score against criteria weight
$stmt = $db->prepare("SELECT weight FROM criteria WHERE id = ?");
$stmt->execute([$criteriaId]);
$criteria = $stmt->fetch();
if (!$criteria) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Criteria not found.']);
    exit;
}

$maxScore = floatval($criteria['weight']);
if ($score < 0 || $score > $maxScore) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Score must be between 0 and {$maxScore}."]);
    exit;
}

$scoreService = new ScoreService($db);
$result = $scoreService->adminUpdateScore($judgeId, $bandId, $criteriaId, $score);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Score updated successfully.']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Score record not found.']);
}
