<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/BandService.php';
require_once __DIR__ . '/../../services/ScoreService.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new AdminController($db);
$bandService  = new BandService($db);
$scoreService = new ScoreService($db);

$bands = $bandService->getAll();
$totalJudges = $scoreService->getTotalJudgeCount();

// Add is_finished flag: true when all judges have finalized scores for this band
foreach ($bands as &$band) {
    if ($totalJudges > 0) {
        $pending = $scoreService->getPendingJudges($band['id']);
        $band['is_finished'] = count($pending) === 0;
    } else {
        $band['is_finished'] = false;
    }
}
unset($band);

echo json_encode([
    'success' => true,
    'bands' => $bands
]);
