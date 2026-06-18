<?php

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../Models/NotificationSetting.php';
require_once __DIR__ . '/../Models/NotificationLog.php';
require_once __DIR__ . '/../../config/app.php';

/**
 * NotificationService — builds booking-related email content,
 * checks admin toggles, sends via Mailer, and logs every attempt.
 */
class NotificationService {

    // ── Admin recipient (configurable; falls back to MAIL_FROM_EMAIL) ──

    private static function adminEmail(): string {
        return defined('ADMIN_NOTIFY_EMAIL') ? ADMIN_NOTIFY_EMAIL : MAIL_USERNAME;
    }

    private static function adminName(): string {
        return 'Auditorium Admin';
    }

    // ── Core dispatch ────────────────────────────────────────

    private static function dispatch(
        string $eventKey,
        ?int $bookingId,
        string $toEmail,
        string $toName,
        string $subject,
        string $body
    ): bool {
        if (!NotificationSetting::isEnabled($eventKey)) {
            return false; // skipped, not an error
        }

        $success = Mailer::send($toEmail, $toName, $subject, $body);
        NotificationLog::record(
            $bookingId,
            $eventKey,
            $toEmail,
            $toName,
            $subject,
            $success,
            $success ? null : 'SMTP send failed'
        );
        return $success;
    }

    // ── 1. New booking submitted → notify admin ───────────────

    public static function bookingSubmitted(array $booking): void {
        $subject = "New Booking Request: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Requested by', ($booking['user_name'] ?? '') . ' (' . ($booking['user_email'] ?? '') . ')')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])))
              . self::row('Attendees', $booking['attendee_count'] ?: '—');

        $html = self::intro("A new booking request needs your review.")
              . self::table($body)
              . self::button(APP_URL . '/admin/bookings/pending', 'Review Pending Approvals');

        self::dispatch('booking_submitted', $booking['id'], self::adminEmail(), self::adminName(), $subject, $html);
    }

    // ── 2. Conflict flagged → notify admin ────────────────────

    public static function conflictFlagged(array $booking): void {
        $subject = "⚠ Scheduling Conflict: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Requested by', $booking['user_name'] ?? '')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $html = self::intro("A new booking request overlaps with an existing booking and needs conflict review.")
              . self::table($body)
              . self::button(APP_URL . '/admin/bookings/' . $booking['id'], 'Review Conflict');

        self::dispatch('booking_conflict', $booking['id'], self::adminEmail(), self::adminName(), $subject, $html);
    }

    // ── 3. Booking approved → notify staff ────────────────────

    public static function bookingApproved(array $booking): void {
        $subject = "✅ Booking Approved: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $note = !empty($booking['admin_note'])
            ? "<p style='margin-top:16px;background:#F9FAFB;border-radius:8px;padding:12px 14px;font-size:13px;'><strong>Note from admin:</strong> " . htmlspecialchars($booking['admin_note']) . "</p>"
            : '';

        $html = self::intro("Great news — your booking request has been approved.")
              . self::table($body)
              . $note
              . self::button(APP_URL . '/bookings/' . $booking['id'], 'View Booking');

        self::dispatch('booking_approved', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 4. Booking approved via override → notify staff ───────

    public static function bookingApprovedOverride(array $booking): void {
        $subject = "✅ Booking Approved: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $html = self::intro("Your booking request — which had a potential scheduling conflict — has been reviewed and approved by the admin.")
              . self::table($body)
              . self::button(APP_URL . '/bookings/' . $booking['id'], 'View Booking');

        self::dispatch('booking_override', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 5. Booking rejected → notify staff ────────────────────

    public static function bookingRejected(array $booking): void {
        $subject = "Booking Not Approved: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $reason = "<p style='margin-top:16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;font-size:13px;color:#7F1D1D;'><strong>Reason:</strong> " . htmlspecialchars($booking['admin_note'] ?? '') . "</p>";

        $html = self::intro("Unfortunately, your booking request was not approved.")
              . self::table($body)
              . $reason
              . self::button(APP_URL . '/bookings/new', 'Submit a New Request');

        self::dispatch('booking_rejected', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 6. Alternate time suggested → notify staff ────────────

    public static function alternateSuggested(array $booking): void {
        $subject = "Alternate Time Suggested: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Original Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Original Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $suggestion = "<p style='margin-top:16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;padding:12px 14px;font-size:13px;color:#92400E;'><strong>Admin's suggestion:</strong><br>" . nl2br(htmlspecialchars($booking['admin_note'] ?? '')) . "</p>";

        $html = self::intro("Your original time slot wasn't available, but the admin has suggested an alternative.")
              . self::table($body)
              . $suggestion
              . self::button(APP_URL . '/bookings/new', 'Submit New Request');

        self::dispatch('booking_alternate', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 7. Reminder (24hrs before) → notify staff ─────────────

    public static function bookingReminder(array $booking): void {
        $subject = "Reminder: {$booking['event_name']} is tomorrow";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $html = self::intro("This is a friendly reminder that your approved event is happening tomorrow.")
              . self::table($body)
              . self::button(APP_URL . '/bookings/' . $booking['id'], 'View Booking Details');

        self::dispatch('booking_reminder', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 8. Booking cancelled by staff → notify admin ──────────

    public static function bookingCancelled(array $booking): void {
        $subject = "Booking Cancelled: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Cancelled by', $booking['user_name'] ?? '')
              . self::row('Original Date', date('l, d F Y', strtotime($booking['start_datetime'])))
              . self::row('Original Time', date('g:i A', strtotime($booking['start_datetime'])) . ' – ' . date('g:i A', strtotime($booking['end_datetime'])));

        $html = self::intro("A staff member has cancelled the following booking.")
              . self::table($body);

        self::dispatch('booking_cancelled', $booking['id'], self::adminEmail(), self::adminName(), $subject, $html);
    }

    // ── 9. Equipment unavailable → notify staff ───────────────

    public static function equipmentUnavailable(array $booking, array $items): void {
        $subject = "Equipment Update: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Date', date('l, d F Y', strtotime($booking['start_datetime'])));

        $itemList = '<ul style="margin:8px 0 0 18px;padding:0;font-size:13px;">';
        foreach ($items as $item) {
            $itemList .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $itemList .= '</ul>';

        $html = self::intro("Some of the equipment you requested is unfortunately unavailable for your event:")
              . self::table($body)
              . "<div style='margin-top:12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;color:#7F1D1D;'><strong>Unavailable items:</strong>{$itemList}</div>"
              . self::button(APP_URL . '/bookings/' . $booking['id'], 'View Booking');

        self::dispatch('equipment_unavailable', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── 10/11. Series approved/rejected → notify staff ────────

    public static function seriesApproved(array $booking, int $count): void {
        $subject = "✅ Recurring Series Approved: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Occurrences approved', (string)$count)
              . self::row('First occurrence', date('l, d F Y', strtotime($booking['start_datetime'])));

        $html = self::intro("Your recurring booking series has been approved.")
              . self::table($body)
              . self::button(APP_URL . '/bookings', 'View My Bookings');

        self::dispatch('series_approved', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    public static function seriesRejected(array $booking, int $count, string $reason): void {
        $subject = "Recurring Series Not Approved: {$booking['event_name']}";
        $body = self::row('Event', $booking['event_name'])
              . self::row('Auditorium', $booking['auditorium_name'] ?? '')
              . self::row('Occurrences affected', (string)$count);

        $reasonHtml = "<p style='margin-top:16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:12px 14px;font-size:13px;color:#7F1D1D;'><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";

        $html = self::intro("Your recurring booking series was not approved.")
              . self::table($body)
              . $reasonHtml;

        self::dispatch('series_rejected', $booking['id'], $booking['user_email'], $booking['user_name'], $subject, $html);
    }

    // ── HTML helpers ───────────────────────────────────────────

    private static function intro(string $text): string {
        return "<p style='font-size:15px;margin-bottom:16px;'>{$text}</p>";
    }

    private static function row(string $label, string $value): string {
        return "<tr>
                  <td style='padding:6px 0;font-size:12.5px;font-weight:600;color:#6B7280;width:35%;vertical-align:top;'>{$label}</td>
                  <td style='padding:6px 0;font-size:13.5px;color:#111827;'>" . htmlspecialchars($value) . "</td>
                </tr>";
    }

    private static function table(string $rows): string {
        return "<table style='width:100%;border-collapse:collapse;background:#F9FAFB;border-radius:8px;padding:4px;margin:4px 0;'>
                  <tbody style='display:table;width:100%;'>{$rows}</tbody>
                </table>";
    }

    private static function button(string $url, string $label): string {
        return "<div style='margin-top:24px;'>
                  <a href='{$url}' style='display:inline-block;background:#1E3A5F;color:#ffffff;
                     padding:11px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;'>
                    {$label}
                  </a>
                </div>";
    }
}
