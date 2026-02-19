<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../services/RankingService.php';
require_once __DIR__ . '/../../services/SettingsService.php';

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
$settingsService = new SettingsService($db);

$judges   = $rankingService->getJudgesForRound($roundId);
$rankings = $rankingService->getRankings($roundId);
$settings = $settingsService->getAll();

// Get ALL registered judges (for print footer)
$allJudgesStmt = $db->query("SELECT id, name FROM users WHERE role = 'judge' ORDER BY id ASC");
$allJudges = $allJudgesStmt->fetchAll();

echo json_encode([
    'success'    => true,
    'judges'     => $judges,
    'rankings'   => $rankings,
    'settings'   => $settings,
    'all_judges' => $allJudges
]);
