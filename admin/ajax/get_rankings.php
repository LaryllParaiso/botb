<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/RankingService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$roundId = intval($_GET['round_id'] ?? 0);

if (empty($roundId) || !in_array($roundId, [1, 2])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid round ID is required.']);
    exit;
}

$rankingService = new RankingService($db);

$judges   = $rankingService->getJudgesForRound($roundId);
$rankings = $rankingService->getRankings($roundId);

echo json_encode([
    'success'  => true,
    'judges'   => $judges,
    'rankings' => $rankings
]);
