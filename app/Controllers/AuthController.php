<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Helpers/Mailer.php';

class AuthController {

    // ── GET /login ───────────────────────────────────────────

    public function showLogin(): void {
        if (Auth::isLoggedIn()) {
            Auth::redirectToDashboard();
        }
        $timeout = isset($_GET['timeout']);
        $error   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        include __DIR__ . '/../../views/auth/login.php';
    }

    // ── POST /login ──────────────────────────────────────────

    public function handleLogin(): void {
        Auth::verifyCsrf();

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic input validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
            $this->flashAndRedirect('error', 'Please enter a valid email and password.', '/login');
        }

        $user = User::findByEmail($email);

        if (!$user) {
            // Generic message — don't reveal account existence
            $this->flashAndRedirect('error', 'Invalid email or password.', '/login');
        }

        // Lockout check
        if (User::isLocked($user)) {
            $this->flashAndRedirect('error',
                'Account temporarily locked due to too many failed attempts. Try again in ' . LOCKOUT_MINUTES . ' minutes.',
                '/login'
            );
        }

        // Password check
        if (!User::verifyPassword($password, $user['password_hash'])) {
            User::incrementFailedAttempts($user['id']);
            $remaining = MAX_LOGIN_ATTEMPTS - $user['login_attempts'] - 1;
            $msg = $remaining > 0
                ? "Invalid email or password. {$remaining} attempt(s) remaining."
                : 'Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
            $this->flashAndRedirect('error', $msg, '/login');
        }

        // Success
        User::clearFailedAttempts($user['id']);
        Auth::login($user);
        Auth::redirectToDashboard();
    }

    // ── GET /logout ──────────────────────────────────────────

    public function handleLogout(): void {
        Auth::logout();
        header('Location: ' . APP_URL . '/login');
        exit;
    }

    // ── GET /forgot-password ─────────────────────────────────

    public function showForgotPassword(): void {
        $step  = $_SESSION['reset_step'] ?? 'email'; // email | otp | password
        $error = $_SESSION['flash_error'] ?? null;
        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
        include __DIR__ . '/../../views/auth/forgot-password.php';
    }

    // ── POST /forgot-password (step: email) ──────────────────

    public function handleForgotEmail(): void {
        Auth::verifyCsrf();
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashAndRedirect('error', 'Please enter a valid email address.', '/forgot-password');
        }

        $user = User::findByEmail($email);
        if ($user) {
            $otp = User::createOtp($user['id']);
            Mailer::sendOtp($user['email'], $user['name'], $otp);
            $_SESSION['reset_user_id'] = $user['id'];
        }
        // Always show success (don't reveal if email exists)
        $_SESSION['reset_step']    = 'otp';
        $_SESSION['flash_success'] = 'If that email is registered, an OTP has been sent.';
        header('Location: ' . APP_URL . '/forgot-password');
        exit;
    }

    // ── POST /forgot-password (step: otp) ────────────────────

    public function handleVerifyOtp(): void {
        Auth::verifyCsrf();
        $userId = $_SESSION['reset_user_id'] ?? null;
        $otp    = trim($_POST['otp'] ?? '');

        if (!$userId || strlen($otp) !== 6 || !ctype_digit($otp)) {
            $this->flashAndRedirect('error', 'Invalid OTP. Please try again.', '/forgot-password');
        }

        if (!User::verifyOtp((int)$userId, $otp)) {
            $this->flashAndRedirect('error', 'Incorrect or expired OTP.', '/forgot-password');
        }

        $_SESSION['reset_step']     = 'password';
        $_SESSION['reset_verified'] = true;
        header('Location: ' . APP_URL . '/forgot-password');
        exit;
    }

    // ── POST /forgot-password (step: new password) ───────────

    public function handleResetPassword(): void {
        Auth::verifyCsrf();
        $userId   = $_SESSION['reset_user_id']  ?? null;
        $verified = $_SESSION['reset_verified'] ?? false;

        if (!$userId || !$verified) {
            header('Location: ' . APP_URL . '/forgot-password');
            exit;
        }

        $password = $_POST['password']         ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if ($password !== $confirm) {
            $this->flashAndRedirect('error', 'Passwords do not match.', '/forgot-password');
        }

        $errors = User::validatePassword($password);
        if ($errors) {
            $this->flashAndRedirect('error', implode(' ', $errors), '/forgot-password');
        }

        User::updatePassword((int)$userId, $password);
        User::consumeOtp((int)$userId);

        // Clean up reset session
        unset($_SESSION['reset_user_id'], $_SESSION['reset_step'], $_SESSION['reset_verified']);

        $this->flashAndRedirect('success', 'Password updated. You can now log in.', '/login');
    }

    // ── Utility ──────────────────────────────────────────────

    private function flashAndRedirect(string $type, string $message, string $path): never {
        $_SESSION["flash_{$type}"] = $message;
        header('Location: ' . APP_URL . $path);
        exit;
    }
}
