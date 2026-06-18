<?php

require_once __DIR__ . '/../../config/database.php';

class Booking {

    public const STATUSES = [
        'pending'          => ['label' => 'Pending',          'color' => '#D97706', 'bg' => '#FEF3C7'],
        'pending_conflict' => ['label' => 'Conflict Review',  'color' => '#DC2626', 'bg' => '#FEF2F2'],
        'approved'         => ['label' => 'Approved',         'color' => '#16A34A', 'bg' => '#F0FDF4'],
        'rejected'         => ['label' => 'Rejected',         'color' => '#6B7280', 'bg' => '#F3F4F6'],
        'cancelled'        => ['label' => 'Cancelled',        'color' => '#6B7280', 'bg' => '#F3F4F6'],
    ];

    public const EQUIPMENT_OPTIONS = [
        'microphone_handheld' => 'Microphone (Handheld)',
        'microphone_lapel'    => 'Microphone (Lapel)',
        'projector'           => 'Projector',
        'screen'              => 'Projection Screen',
        'pa_system'           => 'PA System',
        'podium'              => 'Podium / Lectern',
        'laptop'              => 'Laptop',
        'hdmi_adapter'        => 'HDMI Adapter',
        'vga_adapter'         => 'VGA Adapter',
        'extension_cord'      => 'Extension Cord',
    ];

    // ── Fetch ─────────────────────────────────────────────────

    public static function find(int $id): array|false {
        $stmt = getDB()->prepare(
            'SELECT b.*, u.name AS user_name, u.email AS user_email,
                    u.department AS user_department,
                    a.name AS auditorium_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN auditoriums a ON a.id = b.auditorium_id
             WHERE b.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function forUser(int $userId, string $status = '', int $limit = 50, int $offset = 0): array {
        $params = [$userId];
        $where  = 'b.user_id = ?';
        if ($status) { $where .= ' AND b.status = ?'; $params[] = $status; }

        $stmt = getDB()->prepare(
            "SELECT b.*, a.name AS auditorium_name
             FROM bookings b
             JOIN auditoriums a ON a.id = b.auditorium_id
             WHERE {$where}
             ORDER BY b.start_datetime DESC
             LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countForUser(int $userId, string $status = ''): int {
        $params = [$userId];
        $where  = 'user_id = ?';
        if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
        $stmt = getDB()->prepare("SELECT COUNT(*) FROM bookings WHERE {$where}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ── For FullCalendar JSON API ──────────────────────────────

    public static function forCalendar(string $start, string $end, ?int $auditoriumId = null): array {
        $params = [$start, $end];
        $where  = 'b.start_datetime < ? AND b.end_datetime > ?';
        $where .= " AND b.status NOT IN ('cancelled','rejected')";

        if ($auditoriumId) {
            $where   .= ' AND b.auditorium_id = ?';
            $params[] = $auditoriumId;
        }

        $stmt = getDB()->prepare(
            "SELECT b.id, b.event_name, b.start_datetime, b.end_datetime,
                    b.status, b.auditorium_id,
                    a.name AS auditorium_name,
                    u.name AS user_name
             FROM bookings b
             JOIN auditoriums a ON a.id = b.auditorium_id
             JOIN users u ON u.id = b.user_id
             WHERE {$where}
             ORDER BY b.start_datetime ASC"
        );
        $stmt->execute($params);
        $rows   = $stmt->fetchAll();
        $events = [];

        foreach ($rows as $r) {
            $s = self::STATUSES[$r['status']] ?? self::STATUSES['pending'];
            $events[] = [
                'id'              => $r['id'],
                'title'           => $r['event_name'] . ' — ' . $r['auditorium_name'],
                'start'           => $r['start_datetime'],
                'end'             => $r['end_datetime'],
                'backgroundColor' => $s['bg'],
                'borderColor'     => $s['color'],
                'textColor'       => $s['color'],
                'extendedProps'   => [
                    'status'         => $r['status'],
                    'statusLabel'    => $s['label'],
                    'auditorium'     => $r['auditorium_name'],
                    'bookedBy'       => $r['user_name'],
                    'auditoriumId'   => $r['auditorium_id'],
                ],
            ];
        }
        return $events;
    }

    // Blackout dates as calendar events
    public static function blackoutsForCalendar(string $start, string $end, ?int $auditoriumId = null): array {
        $from   = substr($start, 0, 10);
        $to     = substr($end, 0, 10);
        $params = [$from, $to];
        $where  = 'blackout_date BETWEEN ? AND ?';

        if ($auditoriumId) {
            $where   .= ' AND (auditorium_id = ? OR auditorium_id IS NULL)';
            $params[] = $auditoriumId;
        }

        $stmt = getDB()->prepare(
            "SELECT blackout_date, reason, auditorium_id FROM blackout_dates WHERE {$where}"
        );
        $stmt->execute($params);
        $events = [];
        foreach ($stmt->fetchAll() as $b) {
            $events[] = [
                'title'           => '🚫 ' . ($b['reason'] ?: ($b['auditorium_id'] ? 'Blocked' : 'All Halls Blocked')),
                'start'           => $b['blackout_date'],
                'allDay'          => true,
                'backgroundColor' => '#F3F4F6',
                'borderColor'     => '#9CA3AF',
                'textColor'       => '#6B7280',
                'display'         => 'background',
                'extendedProps'   => ['type' => 'blackout'],
            ];
        }
        return $events;
    }

    // ── Conflict detection ────────────────────────────────────

    public static function detectConflicts(int $auditoriumId, string $start, string $end, ?int $excludeId = null): array {
        $params = [$auditoriumId, $end, $start];
        $where  = "auditorium_id = ?
                   AND end_datetime > ?
                   AND start_datetime < ?
                   AND status NOT IN ('cancelled','rejected')";
        if ($excludeId) { $where .= ' AND id != ?'; $params[] = $excludeId; }

        $stmt = getDB()->prepare(
            "SELECT b.id, b.event_name, b.start_datetime, b.end_datetime, b.status,
                    u.name AS user_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             WHERE {$where}
             ORDER BY b.start_datetime ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function hasConflict(int $auditoriumId, string $start, string $end, ?int $excludeId = null): bool {
        return count(self::detectConflicts($auditoriumId, $start, $end, $excludeId)) > 0;
    }

    // ── Create single booking ─────────────────────────────────

    public static function create(array $data): int {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO bookings
             (user_id, auditorium_id, event_name, event_description,
              start_datetime, end_datetime, attendee_count,
              special_requirements, status, recurrence_group_id, ical_uid)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $status = $data['has_conflict'] ? 'pending_conflict' : 'pending';
        $icalUid = 'abs-' . bin2hex(random_bytes(12)) . '@college.edu';

        $stmt->execute([
            $data['user_id'],
            $data['auditorium_id'],
            $data['event_name'],
            $data['event_description']    ?? null,
            $data['start_datetime'],
            $data['end_datetime'],
            $data['attendee_count']       ?? null,
            $data['special_requirements'] ?? null,
            $status,
            $data['recurrence_group_id']  ?? null,
            $icalUid,
        ]);
        return (int)$db->lastInsertId();
    }

    // ── Equipment ─────────────────────────────────────────────

    public static function saveEquipment(int $bookingId, array $items): void {
        $db   = getDB();
        $db->prepare('DELETE FROM equipment_requests WHERE booking_id = ?')->execute([$bookingId]);
        $stmt = $db->prepare(
            'INSERT INTO equipment_requests (booking_id, equipment_name, quantity) VALUES (?,?,?)'
        );
        foreach ($items as $key => $qty) {
            if ((int)$qty > 0 && isset(self::EQUIPMENT_OPTIONS[$key])) {
                $stmt->execute([$bookingId, $key, (int)$qty]);
            }
        }
    }

    public static function getEquipment(int $bookingId): array {
        $stmt = getDB()->prepare('SELECT * FROM equipment_requests WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll();
    }

    // ── Recurrence ────────────────────────────────────────────

    public static function createRecurrenceGroup(string $pattern, int $interval, ?string $endDate): int {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO recurrence_groups (pattern, interval_value, end_date) VALUES (?,?,?)'
        );
        $stmt->execute([$pattern, $interval, $endDate]);
        return (int)$db->lastInsertId();
    }

    /**
     * Generate occurrence datetimes from recurrence settings.
     * Returns array of ['start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s']
     */
    public static function generateOccurrences(
        string $startDatetime,
        string $endDatetime,
        string $pattern,
        int    $interval,
        string $endDate,
        array  $customDates = []
    ): array {
        $duration = strtotime($endDatetime) - strtotime($startDatetime);
        $occurrences = [];
        $baseTime    = date('H:i:s', strtotime($startDatetime));

        if ($pattern === 'custom') {
            foreach ($customDates as $d) {
                $d = trim($d);
                if (!$d || !strtotime($d)) continue;
                $s = $d . ' ' . $baseTime;
                $occurrences[] = [
                    'start' => $s,
                    'end'   => date('Y-m-d H:i:s', strtotime($s) + $duration),
                ];
            }
            return $occurrences;
        }

        $current  = strtotime($startDatetime);
        $limit    = strtotime($endDate . ' 23:59:59');
        $maxOccurrences = 365; // hard cap
        $count    = 0;

        while ($current <= $limit && $count < $maxOccurrences) {
            $s = date('Y-m-d H:i:s', $current);
            $occurrences[] = [
                'start' => $s,
                'end'   => date('Y-m-d H:i:s', $current + $duration),
            ];
            $count++;
            $current = match ($pattern) {
                'daily'   => strtotime("+{$interval} day",   $current),
                'weekly'  => strtotime("+{$interval} week",  $current),
                'monthly' => strtotime("+{$interval} month", $current),
                default   => $limit + 1,
            };
        }
        return $occurrences;
    }

    // ── Cancel ────────────────────────────────────────────────

    public static function cancel(int $id, int $userId): bool {
        // Only pending/pending_conflict bookings can be cancelled by staff
        $stmt = getDB()->prepare(
            "UPDATE bookings SET status = 'cancelled'
             WHERE id = ? AND user_id = ? AND status IN ('pending','pending_conflict')"
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function cancelSeries(int $groupId, int $userId): int {
        $stmt = getDB()->prepare(
            "UPDATE bookings SET status = 'cancelled'
             WHERE recurrence_group_id = ? AND user_id = ? AND status IN ('pending','pending_conflict')"
        );
        $stmt->execute([$groupId, $userId]);
        return $stmt->rowCount();
    }

    // ── Validation ────────────────────────────────────────────

    public static function validate(array $data, array $hall): array {
        $errors = [];

        if (empty(trim($data['event_name'] ?? ''))) {
            $errors[] = 'Event name is required.';
        }
        if (empty($data['auditorium_id'])) {
            $errors[] = 'Please select an auditorium.';
        }
        if (empty($data['start_datetime']) || empty($data['end_datetime'])) {
            $errors[] = 'Start and end date/time are required.';
        }

        if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
            $s = strtotime($data['start_datetime']);
            $e = strtotime($data['end_datetime']);

            if ($s === false || $e === false) {
                $errors[] = 'Invalid date/time format.';
            } elseif ($e <= $s) {
                $errors[] = 'End time must be after start time.';
            } elseif ($s < time()) {
                $errors[] = 'Booking cannot be in the past.';
            } elseif (!empty($hall)) {
                // Check within operational hours
                $opStart = strtotime(date('Y-m-d', $s) . ' ' . $hall['operational_start']);
                $opEnd   = strtotime(date('Y-m-d', $s) . ' ' . $hall['operational_end']);
                if ($s < $opStart || $e > $opEnd) {
                    $errors[] = 'Booking must be within operational hours (' .
                        date('g:i A', strtotime($hall['operational_start'])) . ' – ' .
                        date('g:i A', strtotime($hall['operational_end'])) . ').';
                }
            }
        }

        $cap = (int)($data['attendee_count'] ?? 0);
        if ($cap > 0 && !empty($hall['capacity']) && $cap > (int)$hall['capacity']) {
            $errors[] = "Attendee count ({$cap}) exceeds hall capacity ({$hall['capacity']}).";
        }

        if (!empty($data['is_recurring'])) {
            if (empty($data['recurrence_end_date'])) {
                $errors[] = 'Recurrence end date is required for recurring bookings.';
            } elseif (!empty($data['start_datetime']) && $data['recurrence_end_date'] <= substr($data['start_datetime'], 0, 10)) {
                $errors[] = 'Recurrence end date must be after the first booking date.';
            }
        }

        return $errors;
    }

    public static function statusBadge(string $status): string {
        $s = self::STATUSES[$status] ?? self::STATUSES['pending'];
        return "<span style='display:inline-flex;align-items:center;gap:5px;padding:3px 10px;
                border-radius:20px;font-size:12px;font-weight:600;
                background:{$s['bg']};color:{$s['color']};'>
                {$s['label']}</span>";
    }
}
