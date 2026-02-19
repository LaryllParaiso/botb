<?php
/**
 * Database Configuration — PDO Singleton
 * Battle of the Bands Tabulator System
 */

// Production error suppression — no raw PHP errors shown to users
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

class Database
{
    private static ?PDO $instance = null;

    private const DB_HOST = '127.0.0.1';
    private const DB_NAME = 'botb_tabulator';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    /**
     * Get PDO singleton instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Database connection failed. Please check your XAMPP MySQL service.');
            }
        }

        return self::$instance;
    }

    // Prevent cloning and unserialization
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
