<?php

require_once __DIR__ . '/../../config/app.php';

class Auth {

    // ── Session bootstrap ────────────────────────────────────

    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => (APP_ENV === 'production'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ── Login / logout ───────────────────────────────────────

    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_active'] = time();
    }

    public static function logout(): void {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    // ── Check helpers ────────────────────────────────────────

    public static function isLoggedIn(): bool {
        if (empty($_SESSION['user_id'])) return false;
        // Check inactivity timeout
        if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        $_SESSION['last_active'] = time();
        return true;
    }

    public static function check(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/login?timeout=1');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void {
        self::check();
        if (!in_array($_SESSION['user_role'], $roles, true)) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }

    public static function user(): array {
        return [
            'id'    => $_SESSION['user_id']    ?? null,
            'name'  => $_SESSION['user_name']  ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role']  ?? '',
        ];
    }

    public static function role(): string {
        return $_SESSION['user_role'] ?? '';
    }

    public static function isAdmin(): bool {
        return in_array(self::role(), ['admin', 'superadmin'], true);
    }

    // ── CSRF ─────────────────────────────────────────────────

    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('CSRF validation failed.');
        }
    }

    // ── Redirect shortcuts ───────────────────────────────────

    public static function redirectToDashboard(): void {
        $role = self::role();
        if ($role === 'admin' || $role === 'superadmin') {
            header('Location: ' . APP_URL . '/admin');
        } else {
            header('Location: ' . APP_URL . '/dashboard');
        }
        exit;
    }
}
