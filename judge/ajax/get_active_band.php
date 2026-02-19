<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/JudgeController.php';
require_once __DIR__ . '/../../services/BandService.php';
require_once __DIR__ . '/../../services/ScoreService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new JudgeController($db);

$bandService  = new BandService($db);
$scoreService = new ScoreService($db);

$activeBand = $bandService->getActiveBand();

if (!$activeBand) {
    echo json_encode(['success' => true, 'band' => null]);
    exit;
}

$judgeId = $_SESSION['user_id'];
$bandId  = $activeBand['id'];

// Get criteria for this round
$criteria = $bandService->getCriteriaForRound($activeBand['round_id']);

// Check if already finalized
$isFinalized = $scoreService->isFinalized($judgeId, $bandId);

$response = [
    'success'      => true,
    'band'         => $activeBand,
    'criteria'     => $criteria,
    'is_finalized' => $isFinalized,
];

if ($isFinalized) {
    $response['scores']         = $scoreService->getScoresForBand($judgeId, $bandId);
    $response['weighted_total'] = $scoreService->getWeightedTotal($judgeId, $bandId);
}

echo json_encode($response);
