<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../interfaces/CRUDInterface.php';

/**
 * JudgeService — Single Responsibility: manages judge accounts
 * Implements CRUDInterface (Interface Segregation)
 */
class JudgeService implements CRUDInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, created_at FROM users WHERE role = 'judge' ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? AND role = 'judge'");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'judge')"
        );
        return $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
    }

    public function update(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare(
                "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'judge'"
            );
            return $stmt->execute([
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $id
            ]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'judge'"
            );
            return $stmt->execute([
                $data['name'],
                $data['email'],
                $id
            ]);
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND role = 'judge'");
        return $stmt->execute([$id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }
}
