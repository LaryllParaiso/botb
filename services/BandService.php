<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../interfaces/CRUDInterface.php';

/**
 * BandService — Single Responsibility: manages bands
 * Implements CRUDInterface (Interface Segregation)
 */
class BandService implements CRUDInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT b.*, r.name AS round_name
             FROM bands b
             JOIN rounds r ON b.round_id = r.id
             ORDER BY b.round_id ASC, b.performance_order ASC"
        );
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, r.name AS round_name
             FROM bands b
             JOIN rounds r ON b.round_id = r.id
             WHERE b.id = ?"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO bands (name, round_id, performance_order) VALUES (?, ?, ?)"
        );
        return $stmt->execute([
            $data['name'],
            $data['round_id'],
            $data['performance_order']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE bands SET name = ?, round_id = ?, performance_order = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['round_id'],
            $data['performance_order'],
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM bands WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Activate a band — ACID transaction: set all to 0, then target to 1
     */
    public function activate(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("UPDATE bands SET is_active = 0");
            $stmt = $this->db->prepare("UPDATE bands SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get the currently active band with its round info
     */
    public function getActiveBand(): ?array
    {
        $stmt = $this->db->query(
            "SELECT b.*, r.name AS round_name
             FROM bands b
             JOIN rounds r ON b.round_id = r.id
             WHERE b.is_active = 1
             LIMIT 1"
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get criteria for a specific round
     */
    public function getCriteriaForRound(int $roundId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, weight FROM criteria WHERE round_id = ? ORDER BY id ASC"
        );
        $stmt->execute([$roundId]);
        return $stmt->fetchAll();
    }
}
