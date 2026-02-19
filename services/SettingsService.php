<?php
require_once __DIR__ . '/../config/db.php';

/**
 * SettingsService — manages key-value settings for event configuration
 */
class SettingsService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all settings as associative array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Get a single setting value by key
     */
    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Save multiple settings at once (upsert)
     */
    public function saveAll(array $settings): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        $this->db->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
