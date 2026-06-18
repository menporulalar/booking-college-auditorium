<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/Auth.php';

Auth::startSession();

// ── Router ────────────────────────────────────────────────────
$uri    = strtok($_SERVER['REQUEST_URI'], '?');
$base   = parse_url(APP_URL, PHP_URL_PATH);
$path   = '/' . ltrim(substr($uri, strlen($base)), '/');
$method = $_SERVER['REQUEST_METHOD'];

if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

// ── Load controllers ──────────────────────────────────────────
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/AuditoriumController.php';
require_once __DIR__ . '/../app/Models/Auditorium.php';
require_once __DIR__ . '/../app/Models/BlackoutDate.php';
require_once __DIR__ . '/../app/Models/AdminLog.php';

$auth        = new AuthController();
$auditoriumC = new AuditoriumController();

// ── Helper: extract numeric id from path segment ──────────────
function extractId(string $path, string $prefix): int|false {
    if (preg_match('#^' . preg_quote($prefix, '#') . '/(\d+)#', $path, $m)) {
        return (int)$m[1];
    }
    return false;
}

// ── Route matching ────────────────────────────────────────────
$matched = true;

// Auth
if ($path === '/login'  && $method === 'GET')  { $auth->showLogin(); }
elseif ($path === '/login'  && $method === 'POST') { $auth->handleLogin(); }
elseif ($path === '/logout' && $method === 'GET')  { $auth->handleLogout(); }
elseif ($path === '/forgot-password' && $method === 'GET')  { $auth->showForgotPassword(); }
elseif ($path === '/forgot-password' && $method === 'POST') {
    $step = $_SESSION['reset_step'] ?? 'email';
    match ($step) {
        'email'    => $auth->handleForgotEmail(),
        'otp'      => $auth->handleVerifyOtp(),
        'password' => $auth->handleResetPassword(),
        default    => $auth->showForgotPassword(),
    };
}

// Staff dashboard stub
elseif ($path === '/dashboard') {
    Auth::check();
    include __DIR__ . '/../views/staff/dashboard.php';
}

// Admin dashboard
elseif ($path === '/admin' && $method === 'GET') {
    include __DIR__ . '/../views/admin/dashboard.php';
}

// ── Auditorium routes ─────────────────────────────────────────
elseif ($path === '/admin/auditoriums' && $method === 'GET') {
    $auditoriumC->index();
}
elseif ($path === '/admin/auditoriums/create' && $method === 'GET') {
    $auditoriumC->create();
}
elseif ($path === '/admin/auditoriums/create' && $method === 'POST') {
    $auditoriumC->store();
}
elseif (preg_match('#^/admin/auditoriums/(\d+)$#', $path, $m) && $method === 'GET') {
    $auditoriumC->show((int)$m[1]);
}
elseif (preg_match('#^/admin/auditoriums/(\d+)/edit$#', $path, $m) && $method === 'GET') {
    $auditoriumC->edit((int)$m[1]);
}
elseif (preg_match('#^/admin/auditoriums/(\d+)/edit$#', $path, $m) && $method === 'POST') {
    $auditoriumC->update((int)$m[1]);
}
elseif (preg_match('#^/admin/auditoriums/(\d+)/toggle$#', $path, $m) && $method === 'POST') {
    $auditoriumC->toggleStatus((int)$m[1]);
}

// ── Blackout date routes ──────────────────────────────────────
elseif ($path === '/admin/blackout-dates' && $method === 'GET') {
    $auditoriumC->blackoutIndex();
}
elseif ($path === '/admin/blackout-dates/create' && $method === 'POST') {
    $auditoriumC->blackoutStore();
}
elseif (preg_match('#^/admin/blackout-dates/(\d+)/delete$#', $path, $m) && $method === 'POST') {
    $auditoriumC->blackoutDelete((int)$m[1]);
}

// Root redirect
elseif ($path === '/') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

// 404
else {
    http_response_code(404);
    include __DIR__ . '/../views/errors/404.php';
}
