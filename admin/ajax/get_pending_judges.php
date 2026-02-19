<?php
/**
 * AJAX endpoint: Get pending judges for the currently active band
 * Returns which judges have/haven't submitted scores
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/ScoreService.php';
require_once __DIR__ . '/../../services/BandService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);

$bandService  = new BandService($db);
$scoreService = new ScoreService($db);

$activeBand = $bandService->getActiveBand();

if (!$activeBand) {
    echo json_encode([
        'success'        => true,
        'active_band'    => null,
        'pending_judges' => [],
        'total_judges'   => $scoreService->getTotalJudgeCount(),
        'all_submitted'  => true
    ]);
    exit;
}

$pendingJudges = $scoreService->getPendingJudges($activeBand['id']);
$totalJudges   = $scoreService->getTotalJudgeCount();

echo json_encode([
    'success'        => true,
    'active_band'    => $activeBand,
    'pending_judges' => $pendingJudges,
    'total_judges'   => $totalJudges,
    'submitted_count'=> $totalJudges - count($pendingJudges),
    'all_submitted'  => count($pendingJudges) === 0
]);
