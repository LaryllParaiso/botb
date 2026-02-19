<?php
require_once __DIR__ . '/../config/db.php';

/**
 * AuthService — Single Responsibility: handles authentication only
 */
class AuthService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Attempt login with email and password
     * Returns user array on success, null on failure
     */
    public function login(string $email, string $password): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Create session for authenticated user
     */
    public function createSession(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Destroy current session (logout)
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Get redirect URL based on user role
     */
    public function getRedirectUrl(string $role): string
    {
        return match ($role) {
            'admin' => '/BOB_SYSTEM/admin/dashboard.php',
            'judge' => '/BOB_SYSTEM/judge/score.php',
            default => '/BOB_SYSTEM/index.php',
        };
    }
}
