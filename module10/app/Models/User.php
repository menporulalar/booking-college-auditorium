<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

class User {

    // ── Fetch ────────────────────────────────────────────────

    public static function findByEmail(string $email): array|false {
        $db  = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById(int $id): array|false {
        $db  = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ── Auth ─────────────────────────────────────────────────

    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public static function hashPassword(string $plain): string {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // ── Lockout ──────────────────────────────────────────────

    public static function isLocked(array $user): bool {
        if ($user['locked_until'] === null) return false;
        return new DateTime() < new DateTime($user['locked_until']);
    }

    public static function incrementFailedAttempts(int $userId): void {
        $db   = getDB();
        $stmt = $db->prepare('SELECT login_attempts FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row      = $stmt->fetch();
        $attempts = ($row['login_attempts'] ?? 0) + 1;

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = (new DateTime())->modify('+' . LOCKOUT_MINUTES . ' minutes')->format('Y-m-d H:i:s');
            $db->prepare('UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?')
               ->execute([$attempts, $lockedUntil, $userId]);
        } else {
            $db->prepare('UPDATE users SET login_attempts = ? WHERE id = ?')
               ->execute([$attempts, $userId]);
        }
    }

    public static function clearFailedAttempts(int $userId): void {
        $db = getDB();
        $db->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?')
           ->execute([$userId]);
    }

    // ── Password reset OTP ───────────────────────────────────

    public static function createOtp(int $userId): string {
        $db  = getDB();
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash      = password_hash($otp, PASSWORD_BCRYPT);
        $expiresAt = (new DateTime())->modify('+' . OTP_EXPIRY_MINUTES . ' minutes')->format('Y-m-d H:i:s');

        // Invalidate any existing unused OTPs for this user
        $db->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')
           ->execute([$userId]);

        $db->prepare('INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (?, ?, ?)')
           ->execute([$userId, $hash, $expiresAt]);

        return $otp; // plaintext returned once — email to user
    }

    public static function verifyOtp(int $userId, string $otp): bool {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT otp_hash FROM password_resets
             WHERE user_id = ? AND used = 0 AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) return false;
        return password_verify($otp, $row['otp_hash']);
    }

    public static function consumeOtp(int $userId): void {
        getDB()->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')
               ->execute([$userId]);
    }

    public static function updatePassword(int $userId, string $newPassword): void {
        getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
               ->execute([self::hashPassword($newPassword), $userId]);
    }

    // ── Validation ───────────────────────────────────────────

    public static function validatePassword(string $password): array {
        $errors = [];
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        return $errors;
    }
}
