<?php
require_once __DIR__ . '/../config/db.php';

/**
 * RankingService — Single Responsibility: computes and retrieves rankings
 */
class RankingService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get judges who have submitted scores for a given round
     */
    public function getJudgesForRound(int $roundId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id, u.name
             FROM users u
             JOIN scores s ON u.id = s.judge_id
             JOIN bands b ON s.band_id = b.id
             WHERE b.round_id = ? AND s.is_finalized = 1
             ORDER BY u.id ASC"
        );
        $stmt->execute([$roundId]);
        return $stmt->fetchAll();
    }

    /**
     * Get rankings for a specific round
     * Returns bands sorted by average weighted score DESC
     * Each band includes per-judge weighted totals and the overall average
     */
    public function getRankings(int $roundId): array
    {
        // Get all bands in this round
        $stmt = $this->db->prepare(
            "SELECT b.id, b.name AS band_name
             FROM bands b
             WHERE b.round_id = ?
             ORDER BY b.performance_order ASC"
        );
        $stmt->execute([$roundId]);
        $bands = $stmt->fetchAll();

        // Get judges who scored in this round
        $judges = $this->getJudgesForRound($roundId);

        $rankings = [];

        foreach ($bands as $band) {
            $bandId = $band['id'];

            // Get each judge's weighted total for this band
            $judgeScores = [];
            $totalWeighted = 0;
            $judgeCount = 0;

            foreach ($judges as $judge) {
                $stmt = $this->db->prepare(
                    "SELECT SUM(s.score) AS weighted_total
                     FROM scores s
                     WHERE s.judge_id = ? AND s.band_id = ? AND s.is_finalized = 1"
                );
                $stmt->execute([$judge['id'], $bandId]);
                $result = $stmt->fetch();
                $wt = floatval($result['weighted_total'] ?? 0);

                if ($result['weighted_total'] !== null) {
                    $judgeScores[$judge['id']] = $wt;
                    $totalWeighted += $wt;
                    $judgeCount++;
                }
            }

            $averageScore = $judgeCount > 0 ? $totalWeighted / $judgeCount : 0;

            $rankings[] = [
                'band_id'       => $bandId,
                'band_name'     => $band['band_name'],
                'judge_scores'  => $judgeScores,
                'average_score' => $averageScore,
                'judge_count'   => $judgeCount,
            ];
        }

        // Sort by average score DESC
        usort($rankings, function ($a, $b) {
            return $b['average_score'] <=> $a['average_score'];
        });

        // Assign ranks with tie handling (same score = same rank)
        $rank = 0;
        $lastScore = null;
        foreach ($rankings as $i => &$entry) {
            if ($lastScore === null || round($entry['average_score'], 2) !== round($lastScore, 2)) {
                $rank = $i + 1;
            }
            $entry['rank'] = $rank;
            $lastScore = $entry['average_score'];
        }
        unset($entry);

        return $rankings;
    }
}
