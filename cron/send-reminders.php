<?php
/**
 * Cron script: sends a reminder email 24 hours before each approved booking.
 *
 * Setup (run once per hour via crontab):
 *   0 * * * * php /path/to/auditorium-booking/cron/send-reminders.php >> /path/to/logs/reminders.log 2>&1
 *
 * Logic: finds approved bookings starting between 23 and 25 hours from now
 * that haven't had a reminder sent yet, and emails the requester.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Booking.php';
require_once __DIR__ . '/../app/Helpers/NotificationService.php';

$db = getDB();

$stmt = $db->prepare(
    "SELECT b.*, u.name AS user_name, u.email AS user_email, a.name AS auditorium_name
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN auditoriums a ON a.id = b.auditorium_id
     WHERE b.status = 'approved'
       AND b.reminder_sent = 0
       AND b.start_datetime BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)"
);
$stmt->execute();
$bookings = $stmt->fetchAll();

$sent = 0;
foreach ($bookings as $booking) {
    NotificationService::bookingReminder($booking);

    $db->prepare('UPDATE bookings SET reminder_sent = 1 WHERE id = ?')
       ->execute([$booking['id']]);

    $sent++;
    echo "[" . date('Y-m-d H:i:s') . "] Reminder sent for booking #{$booking['id']} ({$booking['event_name']}) to {$booking['user_email']}\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Done. {$sent} reminder(s) sent.\n";
