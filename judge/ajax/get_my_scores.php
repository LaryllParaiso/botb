<?php
/**
 * AJAX endpoint: Get all scores submitted by the current judge (read-only history)
 */
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/JudgeController.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$controller = new JudgeController($db);

$judgeId = $_SESSION['user_id'];

// Get all finalized scores for this judge, grouped by band
$stmt = $db->prepare(
    "SELECT s.band_id, s.criteria_id, s.score,
            b.name AS band_name, b.performance_order, b.round_id,
            r.name AS round_name,
            c.name AS criteria_name, c.weight
     FROM scores s
     JOIN bands b ON s.band_id = b.id
     JOIN rounds r ON b.round_id = r.id
     JOIN criteria c ON s.criteria_id = c.id
     WHERE s.judge_id = ? AND s.is_finalized = 1
     ORDER BY b.round_id ASC, b.performance_order ASC, c.id ASC"
);
$stmt->execute([$judgeId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by band
$bands = [];
foreach ($rows as $row) {
    $bid = $row['band_id'];
    if (!isset($bands[$bid])) {
        $bands[$bid] = [
            'band_id'    => $bid,
            'band_name'  => $row['band_name'],
            'round_name' => $row['round_name'],
            'criteria'   => [],
            'total'      => 0
        ];
    }
    $score = floatval($row['score']);
    $bands[$bid]['criteria'][] = [
        'criteria_name' => $row['criteria_name'],
        'weight'        => floatval($row['weight']),
        'score'         => $score
    ];
    $bands[$bid]['total'] += $score;
}

echo json_encode([
    'success' => true,
    'bands'   => array_values($bands)
]);
