<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/AuthService.php';

$authService = new AuthService(Database::getInstance());

// If already logged in, redirect by role
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header('Location: ' . $authService->getRedirectUrl($_SESSION['role']));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $user = $authService->login($email, $password);
            if ($user) {
                $authService->createSession($user);
                header('Location: ' . $authService->getRedirectUrl($user['role']));
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

// Generate CSRF token for login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Login — BotB Tabulator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/BOB_SYSTEM/assets/css/app.css" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <div class="card-header">
                <h4 class="mb-1"><i class="bi bi-music-note-beamed"></i> Battle of the Bands</h4>
                <small class="text-white-50">NEUST 118th Founding Anniversary — Tabulator System</small>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Enter your email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required autofocus>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password" required>
                            <button type="button" class="input-group-text bg-white border-start-0" id="togglePassword" tabindex="-1" style="cursor:pointer;">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter your password.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small py-3">
                NEUST 2026 &mdash; Battle of the Bands Tabulator
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password show/hide toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });

        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let valid = true;

            if (!email.value.trim() || !email.value.includes('@')) {
                email.classList.add('is-invalid');
                valid = false;
            } else {
                email.classList.remove('is-invalid');
            }

            if (!password.value.trim()) {
                password.classList.add('is-invalid');
                valid = false;
            } else {
                password.classList.remove('is-invalid');
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
