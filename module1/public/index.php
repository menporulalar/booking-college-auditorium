<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';

Auth::startSession();

// ── Simple router ─────────────────────────────────────────────
$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$base   = parse_url(APP_URL, PHP_URL_PATH);
$path   = '/' . ltrim(substr($uri, strlen($base)), '/');
$method = $_SERVER['REQUEST_METHOD'];

// Strip trailing slash (except root)
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

require_once __DIR__ . '/../app/Controllers/AuthController.php';
$auth = new AuthController();

// ── Auth routes ───────────────────────────────────────────────
match (true) {

    // Login
    $path === '/login'  && $method === 'GET'  => $auth->showLogin(),
    $path === '/login'  && $method === 'POST' => $auth->handleLogin(),

    // Logout
    $path === '/logout' && $method === 'GET'  => $auth->handleLogout(),

    // Forgot password (multi-step)
    $path === '/forgot-password' && $method === 'GET'  => $auth->showForgotPassword(),
    $path === '/forgot-password' && $method === 'POST' => (function() use ($auth) {
        $step = $_SESSION['reset_step'] ?? 'email';
        match ($step) {
            'email'    => $auth->handleForgotEmail(),
            'otp'      => $auth->handleVerifyOtp(),
            'password' => $auth->handleResetPassword(),
            default    => $auth->showForgotPassword(),
        };
    })(),

    // Placeholder dashboards (stubs until Module 3)
    $path === '/dashboard' => (function() {
        Auth::check();
        include __DIR__ . '/../views/staff/dashboard.php';
    })(),

    $path === '/admin' => (function() {
        Auth::requireRole('admin', 'superadmin');
        include __DIR__ . '/../views/admin/dashboard.php';
    })(),

    // Root redirect
    $path === '/' => (function() {
        header('Location: ' . APP_URL . '/login');
        exit;
    })(),

    // 404
    default => (function() {
        http_response_code(404);
        include __DIR__ . '/../views/errors/404.php';
    })(),
};
