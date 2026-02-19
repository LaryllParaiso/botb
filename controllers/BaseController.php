<?php
/**
 * BaseController — Liskov Substitution Principle
 * Common functionality for Admin and Judge controllers
 */
class BaseController
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Require user to be logged in; redirect to login if not
     */
    protected function requireLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: /BOB_SYSTEM/index.php');
            exit;
        }
    }

    /**
     * Require a specific role; redirect if unauthorized
     */
    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: /BOB_SYSTEM/index.php');
            exit;
        }
    }

    /**
     * Validate CSRF token on POST requests
     */
    public function validateCsrf(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
            exit;
        }
        return true;
    }

    /**
     * Send JSON response
     */
    public function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
