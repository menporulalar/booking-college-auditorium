<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/NotificationSetting.php';
require_once __DIR__ . '/../Models/NotificationLog.php';
require_once __DIR__ . '/../Models/AdminLog.php';
require_once __DIR__ . '/../Helpers/Mailer.php';

class NotificationController {

    // ── GET /admin/notifications ──────────────────────────────

    public function index(): void {
        Auth::requireRole('admin', 'superadmin');

        $settings = NotificationSetting::all();

        $filters = [
            'event'  => $_GET['event']  ?? '',
            'status' => $_GET['status'] ?? '',
            'from'   => $_GET['from']   ?? '',
            'to'     => $_GET['to']     ?? '',
        ];
        $logs  = NotificationLog::recent(50, $filters);
        $stats = NotificationLog::stats();
        $flash = $this->popFlash();

        include __DIR__ . '/../../views/admin/notifications/index.php';
    }

    // ── POST /admin/notifications ─────────────────────────────

    public function update(): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $enabledKeys = $_POST['enabled'] ?? [];
        NotificationSetting::updateBulk($enabledKeys);

        AdminLog::record(Auth::user()['id'], 'notification_settings_updated', '', 0,
            count($enabledKeys) . ' event(s) enabled');

        $this->flash('success', 'Notification settings updated.');
        header('Location: ' . APP_URL . '/admin/notifications');
        exit;
    }

    // ── POST /admin/notifications/test ────────────────────────

    public function sendTest(): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $user = Auth::user();
        $body = "<p>This is a test email from <strong>" . htmlspecialchars(APP_NAME) . "</strong>.</p>
                 <p>If you received this, your SMTP configuration in <code>config/app.php</code> is working correctly.</p>
                 <p>Sent at: " . date('d M Y, g:i A') . "</p>";

        $success = Mailer::send($user['email'], $user['name'], 'Test Email — ' . APP_NAME, $body);

        NotificationLog::record(null, 'test_email', $user['email'], $user['name'], 'Test Email — ' . APP_NAME, $success, $success ? null : 'SMTP send failed');

        if ($success) {
            $this->flash('success', "Test email sent to <strong>{$user['email']}</strong>. Check your inbox.");
        } else {
            $this->flash('error', 'Failed to send test email. Check your SMTP settings in config/app.php.');
        }

        header('Location: ' . APP_URL . '/admin/notifications');
        exit;
    }

    // ── Helpers ────────────────────────────────────────────────

    private function flash(string $type, string $msg): void {
        $_SESSION["flash_{$type}"] = $msg;
    }

    private function popFlash(): array {
        $flash = [
            'success' => $_SESSION['flash_success'] ?? null,
            'error'   => $_SESSION['flash_error']   ?? null,
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $flash;
    }
}
