<?php
/**
 * ScoringInterface — Interface Segregation Principle
 * Used by services that handle scoring operations
 */
interface ScoringInterface
{
    public function submitScores(int $judgeId, int $bandId, array $scores): bool;
    public function getScoresForBand(int $judgeId, int $bandId): array;
    public function isFinalized(int $judgeId, int $bandId): bool;
}
