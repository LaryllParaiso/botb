<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../interfaces/ScoringInterface.php';

/**
 * ScoreService — Single Responsibility: handles scoring operations
 * Implements ScoringInterface (Interface Segregation)
 */
class ScoreService implements ScoringInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Submit scores for a band — validates, inserts, and finalizes atomically
     */
    public function submitScores(int $judgeId, int $bandId, array $scores): bool
    {
        // Check if already finalized
        if ($this->isFinalized($judgeId, $bandId)) {
            return false;
        }

        // Get band's round to validate criteria
        $stmt = $this->db->prepare("SELECT round_id FROM bands WHERE id = ?");
        $stmt->execute([$bandId]);
        $band = $stmt->fetch();
        if (!$band) return false;

        // Get valid criteria IDs and their weights for this round
        $stmt = $this->db->prepare("SELECT id, weight FROM criteria WHERE round_id = ?");
        $stmt->execute([$band['round_id']]);
        $criteriaWeights = [];
        while ($row = $stmt->fetch()) {
            $criteriaWeights[(int)$row['id']] = floatval($row['weight']);
        }

        $this->db->beginTransaction();
        try {
            foreach ($scores as $scoreData) {
                $criteriaId = intval($scoreData['criteria_id']);
                $scoreVal   = floatval($scoreData['score']);

                // Validate criteria belongs to this round
                if (!isset($criteriaWeights[$criteriaId])) {
                    throw new Exception("Invalid criteria ID: $criteriaId");
                }

                // Validate score range — max is the criteria weight
                $maxScore = $criteriaWeights[$criteriaId];
                if ($scoreVal < 0 || $scoreVal > $maxScore) {
                    throw new Exception("Score out of range: $scoreVal (max: $maxScore)");
                }

                $stmt = $this->db->prepare(
                    "INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized)
                     VALUES (?, ?, ?, ?, 1)
                     ON DUPLICATE KEY UPDATE score = VALUES(score), is_finalized = 1"
                );
                $stmt->execute([$judgeId, $bandId, $criteriaId, $scoreVal]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get scores for a specific judge and band
     */
    public function getScoresForBand(int $judgeId, int $bandId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.criteria_id, s.score, s.is_finalized, c.name AS criteria_name, c.weight
             FROM scores s
             JOIN criteria c ON s.criteria_id = c.id
             WHERE s.judge_id = ? AND s.band_id = ?
             ORDER BY c.id ASC"
        );
        $stmt->execute([$judgeId, $bandId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if a judge has finalized scores for a band
     */
    public function isFinalized(int $judgeId, int $bandId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM scores WHERE judge_id = ? AND band_id = ? AND is_finalized = 1"
        );
        $stmt->execute([$judgeId, $bandId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get judges who have NOT finalized scores for a specific band
     */
    public function getPendingJudges(int $bandId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.email
             FROM users u
             WHERE u.role = 'judge'
             AND u.id NOT IN (
                 SELECT DISTINCT s.judge_id FROM scores s
                 WHERE s.band_id = ? AND s.is_finalized = 1
             )
             ORDER BY u.name ASC"
        );
        $stmt->execute([$bandId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if ALL judges have finalized scores for a specific band
     */
    public function allJudgesFinalized(int $bandId): bool
    {
        return count($this->getPendingJudges($bandId)) === 0;
    }

    /**
     * Get total judge count
     */
    public function getTotalJudgeCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'judge'");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Admin: update a single score by judge, band, criteria
     */
    public function adminUpdateScore(int $judgeId, int $bandId, int $criteriaId, float $score): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE scores SET score = ? WHERE judge_id = ? AND band_id = ? AND criteria_id = ?"
        );
        $stmt->execute([$score, $judgeId, $bandId, $criteriaId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Admin: delete all scores for a judge on a specific band
     */
    public function adminDeleteJudgeBandScores(int $judgeId, int $bandId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM scores WHERE judge_id = ? AND band_id = ?");
        $stmt->execute([$judgeId, $bandId]);
        return true;
    }

    /**
     * Admin: get all scores grouped by band and judge for management view
     */
    public function getAllScoresGrouped(): array
    {
        $stmt = $this->db->query(
            "SELECT s.id, s.judge_id, s.band_id, s.criteria_id, s.score, s.is_finalized,
                    u.name AS judge_name, b.name AS band_name, b.round_id,
                    r.name AS round_name, c.name AS criteria_name, c.weight
             FROM scores s
             JOIN users u ON s.judge_id = u.id
             JOIN bands b ON s.band_id = b.id
             JOIN rounds r ON b.round_id = r.id
             JOIN criteria c ON s.criteria_id = c.id
             ORDER BY b.round_id, b.performance_order, b.name, u.name, c.id"
        );
        return $stmt->fetchAll();
    }

    /**
     * Admin: reset all system data (scores, bands, judges)
     */
    public function resetAllData(): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("DELETE FROM scores");
            $this->db->exec("DELETE FROM bands");
            $this->db->exec("DELETE FROM users WHERE role = 'judge'");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Calculate weighted total for a judge's scores on a band
     */
    public function getWeightedTotal(int $judgeId, int $bandId): float
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(s.score) AS weighted_total
             FROM scores s
             WHERE s.judge_id = ? AND s.band_id = ?"
        );
        $stmt->execute([$judgeId, $bandId]);
        $result = $stmt->fetch();
        return floatval($result['weighted_total'] ?? 0);
    }
}
