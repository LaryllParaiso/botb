<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/JudgeController.php';
require_once __DIR__ . '/../../services/ScoreService.php';
require_once __DIR__ . '/../../services/WebSocketService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new JudgeController($db);
$controller->validateCsrf();

$input = json_decode(file_get_contents('php://input'), true);

$bandId = intval($input['band_id'] ?? 0);
$scores = $input['scores'] ?? [];

if (empty($bandId) || empty($scores) || !is_array($scores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Band ID and scores are required.']);
    exit;
}

$judgeId = $_SESSION['user_id'];

// Validate band exists and is currently active
$bandCheck = $db->prepare("SELECT id, is_active FROM bands WHERE id = ?");
$bandCheck->execute([$bandId]);
$bandRow = $bandCheck->fetch();
if (!$bandRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Band not found.']);
    exit;
}
if (!$bandRow['is_active']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This band is not currently active.']);
    exit;
}

// Get band's round to load criteria weights for validation
$roundStmt = $db->prepare("SELECT round_id FROM bands WHERE id = ?");
$roundStmt->execute([$bandId]);
$bandRound = $roundStmt->fetch();
$criteriaStmt = $db->prepare("SELECT id, weight FROM criteria WHERE round_id = ?");
$criteriaStmt->execute([$bandRound['round_id']]);
$criteriaWeights = [];
while ($row = $criteriaStmt->fetch()) {
    $criteriaWeights[$row['id']] = floatval($row['weight']);
}

// Validate each score — max is the criteria weight
foreach ($scores as $s) {
    $criteriaId = intval($s['criteria_id'] ?? 0);
    $scoreVal = floatval($s['score'] ?? -1);
    $maxScore = $criteriaWeights[$criteriaId] ?? 0;

    if ($maxScore <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid criteria.']);
        exit;
    }
    if ($scoreVal < 0 || $scoreVal > $maxScore) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Score must be between 0 and {$maxScore}."]);
        exit;
    }
}

$scoreService = new ScoreService($db);

// Check if already finalized
if ($scoreService->isFinalized($judgeId, $bandId)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Scores already finalized for this band.']);
    exit;
}

$result = $scoreService->submitScores($judgeId, $bandId, $scores);

if ($result) {
    // Notify WebSocket server so admin sees updated pending judges
    WebSocketService::notify('scores_submitted', [
        'band_id'  => $bandId,
        'judge_id' => $judgeId
    ]);

    echo json_encode(['success' => true, 'message' => 'Scores submitted and finalized successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit scores. Please try again.']);
}
