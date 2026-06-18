<?php

require_once __DIR__ . '/../../config/app.php';

/**
 * ICalExport — generates RFC 5545 compliant .ics calendar files
 * for single bookings, full series, or a date-range export.
 */
class ICalExport {

    private const PRODID = '-//' . 'College Auditorium Booking' . '//EN';

    // ── Single booking ─────────────────────────────────────────

    public static function forBooking(array $booking): string {
        $event = self::buildEvent($booking);
        return self::wrap([$event]);
    }

    // ── Multiple bookings (series or admin export) ──────────────

    public static function forBookings(array $bookings): string {
        $events = array_map([self::class, 'buildEvent'], $bookings);
        return self::wrap($events);
    }

    // ── Build a single VEVENT block ──────────────────────────────

    private static function buildEvent(array $b): string {
        $uid     = $b['ical_uid'] ?: ('abs-' . $b['id'] . '@college.edu');
        $start   = self::formatDate($b['start_datetime']);
        $end     = self::formatDate($b['end_datetime']);
        $created = self::formatDate($b['created_at'] ?? date('Y-m-d H:i:s'));
        $stamp   = self::formatDate(date('Y-m-d H:i:s'));

        $summary  = self::escape($b['event_name']);
        $location = self::escape($b['auditorium_name'] ?? '');
        $description = self::buildDescription($b);
        $organizer   = $b['user_email'] ?? '';
        $organizerName = $b['user_name'] ?? '';

        $statusMap = [
            'approved'         => 'CONFIRMED',
            'pending'          => 'TENTATIVE',
            'pending_conflict' => 'TENTATIVE',
            'rejected'         => 'CANCELLED',
            'cancelled'        => 'CANCELLED',
        ];
        $status = $statusMap[$b['status']] ?? 'TENTATIVE';

        $lines = [
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$stamp}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "CREATED:{$created}",
            "SUMMARY:{$summary}",
            "LOCATION:{$location}",
            "DESCRIPTION:{$description}",
            "STATUS:{$status}",
        ];

        if ($organizer) {
            $orgName = $organizerName ? self::escape($organizerName) : $organizer;
            $lines[] = "ORGANIZER;CN={$orgName}:mailto:{$organizer}";
        }

        // 30-minute reminder for approved events
        if ($b['status'] === 'approved') {
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'DESCRIPTION:Reminder: ' . $summary;
            $lines[] = 'TRIGGER:-PT30M';
            $lines[] = 'END:VALARM';
        }

        $lines[] = 'END:VEVENT';

        return implode("\r\n", self::foldLines($lines));
    }

    private static function buildDescription(array $b): string {
        $parts = [];
        if (!empty($b['event_description'])) {
            $parts[] = $b['event_description'];
        }
        if (!empty($b['attendee_count'])) {
            $parts[] = 'Expected attendees: ' . $b['attendee_count'];
        }
        if (!empty($b['special_requirements'])) {
            $parts[] = 'Special requirements: ' . $b['special_requirements'];
        }
        $parts[] = 'Booking ID: #' . $b['id'];
        $parts[] = 'Status: ' . ucfirst(str_replace('_', ' ', $b['status']));

        return self::escape(implode('\n', $parts));
    }

    // ── Wrap events in VCALENDAR ──────────────────────────────────

    private static function wrap(array $events): string {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:' . self::PRODID,
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::escape(APP_NAME),
            'X-WR-TIMEZONE:Asia/Kolkata',
        ];

        foreach ($events as $event) {
            $lines[] = $event;
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    // ── Helpers ─────────────────────────────────────────────────

    private static function formatDate(string $datetime): string {
        // Convert local datetime to UTC 'Z' format for iCal
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Kolkata'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Ymd\THis\Z');
    }

    private static function escape(string $text): string {
        $text = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);
        $text = str_replace(["\r\n", "\n", "\r"], '\\n', $text);
        return $text;
    }

    // RFC 5545: lines should be folded at 75 octets
    private static function foldLines(array $lines): array {
        $folded = [];
        foreach ($lines as $line) {
            if (strlen($line) <= 75) {
                $folded[] = $line;
                continue;
            }
            $chunks = [];
            $remaining = $line;
            $first = true;
            while (strlen($remaining) > 0) {
                $limit = $first ? 75 : 74; // continuation lines start with a space (1 char)
                $chunk = substr($remaining, 0, $limit);
                $chunks[] = $first ? $chunk : ' ' . $chunk;
                $remaining = substr($remaining, $limit);
                $first = false;
            }
            $folded[] = implode("\r\n", $chunks);
        }
        return $folded;
    }

    // ── Google Calendar deep link ────────────────────────────────

    public static function googleCalendarUrl(array $b): string {
        $start = (new DateTime($b['start_datetime'], new DateTimeZone('Asia/Kolkata')))
                    ->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
        $end   = (new DateTime($b['end_datetime'], new DateTimeZone('Asia/Kolkata')))
                    ->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');

        $params = [
            'action'   => 'TEMPLATE',
            'text'     => $b['event_name'],
            'dates'    => "{$start}/{$end}",
            'details'  => self::plainDescription($b),
            'location' => $b['auditorium_name'] ?? '',
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    private static function plainDescription(array $b): string {
        $parts = [];
        if (!empty($b['event_description'])) $parts[] = $b['event_description'];
        if (!empty($b['attendee_count']))    $parts[] = 'Expected attendees: ' . $b['attendee_count'];
        $parts[] = 'Booking ID: #' . $b['id'];
        return implode("\n", $parts);
    }
}
