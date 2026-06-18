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
require_once __DIR__ . '/../app/Controllers/BookingController.php';
require_once __DIR__ . '/../app/Controllers/AdminBookingController.php';
require_once __DIR__ . '/../app/Models/Auditorium.php';
require_once __DIR__ . '/../app/Models/BlackoutDate.php';
require_once __DIR__ . '/../app/Models/AdminLog.php';
require_once __DIR__ . '/../app/Models/Booking.php';

$auth        = new AuthController();
$auditoriumC = new AuditoriumController();
$bookingC    = new BookingController();
$adminBookC  = new AdminBookingController();

// ── Route matching ────────────────────────────────────────────

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

// Staff dashboard
elseif ($path === '/dashboard') {
    include __DIR__ . '/../views/staff/dashboard.php';
}

// Admin dashboard
elseif ($path === '/admin' && $method === 'GET') {
    include __DIR__ . '/../views/admin/dashboard.php';
}

// Admin bookings — approval workflow
elseif ($path === '/admin/bookings/pending' && $method === 'GET') {
    $adminBookC->pending();
}
elseif ($path === '/admin/bookings/bulk-action' && $method === 'POST') {
    $adminBookC->bulkAction();
}
elseif (preg_match('#^/admin/bookings/series/(\d+)/approve$#', $path, $m) && $method === 'POST') {
    $adminBookC->approveSeries((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/series/(\d+)/reject$#', $path, $m) && $method === 'POST') {
    $adminBookC->rejectSeries((int)$m[1]);
}
elseif ($path === '/admin/bookings' && $method === 'GET') {
    $adminBookC->index();
}
elseif (preg_match('#^/admin/bookings/(\d+)/edit$#', $path, $m) && $method === 'GET') {
    $adminBookC->edit((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)/edit$#', $path, $m) && $method === 'POST') {
    $adminBookC->update((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)/approve$#', $path, $m) && $method === 'POST') {
    $adminBookC->approve((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)/reject$#', $path, $m) && $method === 'POST') {
    $adminBookC->reject((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)/suggest-alternate$#', $path, $m) && $method === 'POST') {
    $adminBookC->suggestAlternate((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)/equipment$#', $path, $m) && $method === 'POST') {
    $adminBookC->updateEquipment((int)$m[1]);
}
elseif (preg_match('#^/admin/bookings/(\d+)$#', $path, $m) && $method === 'GET') {
    $adminBookC->show((int)$m[1]);
}
elseif ($path === '/admin/reports' && $method === 'GET') {
    $pageTitle = 'Reports';
    $activePage = 'reports';
    include __DIR__ . '/../views/admin/bookings-stub.php';
}

// ── Calendar ───────────────────────────────────────────────────
elseif ($path === '/calendar' && $method === 'GET') {
    $bookingC->showCalendar();
}

// ── Booking routes ─────────────────────────────────────────────
elseif ($path === '/bookings/new' && $method === 'GET') {
    $bookingC->showBookingForm();
}
elseif ($path === '/bookings/new' && $method === 'POST') {
    $bookingC->storeBooking();
}
elseif ($path === '/bookings' && $method === 'GET') {
    $bookingC->myBookings();
}
elseif (preg_match('#^/bookings/(\d+)$#', $path, $m) && $method === 'GET') {
    $bookingC->showBooking((int)$m[1]);
}
elseif (preg_match('#^/bookings/(\d+)/cancel$#', $path, $m) && $method === 'POST') {
    $bookingC->cancelBooking((int)$m[1]);
}

// ── API routes (JSON) ─────────────────────────────────────────
elseif ($path === '/api/calendar-events' && $method === 'GET') {
    $bookingC->calendarEvents();
}
elseif ($path === '/api/check-conflict' && $method === 'GET') {
    $bookingC->checkConflict();
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

// ── Blackout date routes ────────────────────────────────────────
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
