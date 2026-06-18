<?php

require_once __DIR__ . '/../../config/database.php';

class NotificationLog {

    public static function record(
        ?int $bookingId,
        string $event,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        bool $success,
        ?string $error = null
    ): void {
        try {
            getDB()->prepare(
                'INSERT INTO notification_log
                 (booking_id, trigger_event, recipient_email, recipient_name, subject, status, error_message)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $bookingId,
                $event,
                $recipientEmail,
                $recipientName,
                $subject,
                $success ? 'sent' : 'failed',
                $error,
            ]);
        } catch (PDOException $e) {
            error_log('NotificationLog error: ' . $e->getMessage());
        }
    }

    public static function forBooking(int $bookingId): array {
        $stmt = getDB()->prepare(
            'SELECT * FROM notification_log WHERE booking_id = ? ORDER BY sent_at DESC'
        );
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }

    public static function recent(int $limit = 50, array $filters = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['event'])) {
            $where[] = 'trigger_event = ?';
            $params[] = $filters['event'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'sent_at >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'sent_at <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }

        $sql = 'SELECT * FROM notification_log WHERE ' . implode(' AND ', $where) . ' ORDER BY sent_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function stats(): array {
        $db = getDB();
        $total  = (int)$db->query("SELECT COUNT(*) FROM notification_log")->fetchColumn();
        $sent   = (int)$db->query("SELECT COUNT(*) FROM notification_log WHERE status = 'sent'")->fetchColumn();
        $failed = (int)$db->query("SELECT COUNT(*) FROM notification_log WHERE status = 'failed'")->fetchColumn();
        $today  = (int)$db->query("SELECT COUNT(*) FROM notification_log WHERE DATE(sent_at) = CURDATE()")->fetchColumn();
        return ['total' => $total, 'sent' => $sent, 'failed' => $failed, 'today' => $today];
    }
}
