<?php

require_once __DIR__ . '/../../config/database.php';

class Report {

    // ── 1. Booking summary ──────────────────────────────────────

    public static function bookingSummary(array $filters = []): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['from'])) {
            $where[] = 'b.start_datetime >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'b.start_datetime <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (!empty($filters['auditorium_id'])) {
            $where[] = 'b.auditorium_id = ?';
            $params[] = (int)$filters['auditorium_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $filters['status'];
        }

        $sql = "SELECT b.*, u.name AS user_name, u.department AS user_department,
                       a.name AS auditorium_name
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                JOIN auditoriums a ON a.id = b.auditorium_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.start_datetime ASC";

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function bookingSummaryStats(array $filters = []): array {
        $rows = self::bookingSummary($filters);
        $stats = [
            'total'     => count($rows),
            'approved'  => 0,
            'pending'   => 0,
            'rejected'  => 0,
            'cancelled' => 0,
            'conflict'  => 0,
            'total_hours' => 0.0,
        ];
        foreach ($rows as $r) {
            switch ($r['status']) {
                case 'approved':         $stats['approved']++; break;
                case 'pending':          $stats['pending']++; break;
                case 'pending_conflict': $stats['pending']++; $stats['conflict']++; break;
                case 'rejected':         $stats['rejected']++; break;
                case 'cancelled':        $stats['cancelled']++; break;
            }
            if ($r['status'] === 'approved') {
                $hours = (strtotime($r['end_datetime']) - strtotime($r['start_datetime'])) / 3600;
                $stats['total_hours'] += $hours;
            }
        }
        return $stats;
    }

    // ── 2. Auditorium utilization rate ──────────────────────────

    /**
     * Utilization = total approved booked hours / total available operational hours
     * over the date range, per auditorium.
     */
    public static function utilization(string $from, string $to, ?int $auditoriumId = null): array {
        $auditoriums = self::auditoriumsFor($auditoriumId);
        $results = [];

        $days = (new DateTime($to))->diff(new DateTime($from))->days + 1;

        foreach ($auditoriums as $hall) {
            $opStart = strtotime($hall['operational_start']);
            $opEnd   = strtotime($hall['operational_end']);
            $dailyHours = max(0, ($opEnd - $opStart) / 3600);
            $totalAvailableHours = $dailyHours * $days;

            // Subtract blackout days
            $blackoutDays = self::blackoutDayCount($from, $to, (int)$hall['id']);
            $totalAvailableHours -= $blackoutDays * $dailyHours;
            $totalAvailableHours = max(0, $totalAvailableHours);

            // Booked hours (approved only)
            $stmt = getDB()->prepare(
                "SELECT start_datetime, end_datetime FROM bookings
                 WHERE auditorium_id = ? AND status = 'approved'
                   AND start_datetime >= ? AND start_datetime <= ?"
            );
            $stmt->execute([$hall['id'], $from . ' 00:00:00', $to . ' 23:59:59']);

            $bookedHours = 0.0;
            $bookingCount = 0;
            foreach ($stmt->fetchAll() as $b) {
                $bookedHours += (strtotime($b['end_datetime']) - strtotime($b['start_datetime'])) / 3600;
                $bookingCount++;
            }

            $rate = $totalAvailableHours > 0 ? ($bookedHours / $totalAvailableHours) * 100 : 0;

            $results[] = [
                'auditorium_id'   => $hall['id'],
                'auditorium_name' => $hall['name'],
                'capacity'        => $hall['capacity'],
                'available_hours' => round($totalAvailableHours, 1),
                'booked_hours'    => round($bookedHours, 1),
                'booking_count'   => $bookingCount,
                'utilization_pct' => round($rate, 1),
            ];
        }

        return $results;
    }

    private static function blackoutDayCount(string $from, string $to, int $auditoriumId): int {
        $stmt = getDB()->prepare(
            "SELECT COUNT(DISTINCT blackout_date) FROM blackout_dates
             WHERE blackout_date BETWEEN ? AND ?
               AND (auditorium_id = ? OR auditorium_id IS NULL)"
        );
        $stmt->execute([$from, $to, $auditoriumId]);
        return (int)$stmt->fetchColumn();
    }

    // ── 3. Peak time heatmap ─────────────────────────────────────

    /**
     * Returns a 7x[hours] grid of booking counts by day-of-week and hour.
     * Day 0 = Sunday ... Day 6 = Saturday
     */
    public static function peakTimeHeatmap(string $from, string $to, ?int $auditoriumId = null): array {
        $params = ['approved', $from . ' 00:00:00', $to . ' 23:59:59'];
        $where  = "status = ? AND start_datetime >= ? AND start_datetime <= ?";

        if ($auditoriumId) {
            $where   .= ' AND auditorium_id = ?';
            $params[] = $auditoriumId;
        }

        $stmt = getDB()->prepare("SELECT start_datetime, end_datetime FROM bookings WHERE {$where}");
        $stmt->execute($params);

        // Initialize grid: 7 days x 24 hours
        $grid = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($stmt->fetchAll() as $b) {
            $start = strtotime($b['start_datetime']);
            $end   = strtotime($b['end_datetime']);
            $dow   = (int)date('w', $start); // 0 = Sunday

            $startHour = (int)date('G', $start);
            $endHour   = (int)date('G', $end);
            if ($endHour <= $startHour) $endHour = $startHour + 1; // safety

            for ($h = $startHour; $h < $endHour && $h < 24; $h++) {
                $grid[$dow][$h]++;
            }
        }

        return $grid;
    }

    // ── 4. Equipment usage frequency ─────────────────────────────

    public static function equipmentUsage(string $from, string $to, ?int $auditoriumId = null): array {
        $params = ['approved', $from . ' 00:00:00', $to . ' 23:59:59'];
        $where  = "b.status = ? AND b.start_datetime >= ? AND b.start_datetime <= ?";

        if ($auditoriumId) {
            $where   .= ' AND b.auditorium_id = ?';
            $params[] = $auditoriumId;
        }

        $stmt = getDB()->prepare(
            "SELECT e.equipment_name, SUM(e.quantity) AS total_qty, COUNT(*) AS booking_count,
                    SUM(CASE WHEN e.status = 'unavailable' THEN 1 ELSE 0 END) AS unavailable_count
             FROM equipment_requests e
             JOIN bookings b ON b.id = e.booking_id
             WHERE {$where}
             GROUP BY e.equipment_name
             ORDER BY booking_count DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── 5. Admin override log ─────────────────────────────────────

    public static function overrideLog(string $from, string $to): array {
        $stmt = getDB()->prepare(
            "SELECT b.*, u.name AS user_name, a.name AS auditorium_name,
                    o.name AS override_by_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN auditoriums a ON a.id = b.auditorium_id
             LEFT JOIN users o ON o.id = b.override_by
             WHERE b.override_by IS NOT NULL
               AND b.created_at >= ? AND b.created_at <= ?
             ORDER BY b.created_at DESC"
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    // ── 6. Notification delivery log (cross-reference Module 9) ──

    public static function notificationDeliveryStats(string $from, string $to): array {
        $stmt = getDB()->prepare(
            "SELECT trigger_event,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed
             FROM notification_log
             WHERE sent_at >= ? AND sent_at <= ?
             GROUP BY trigger_event
             ORDER BY total DESC"
        );
        $stmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
        return $stmt->fetchAll();
    }

    // ── Helpers ──────────────────────────────────────────────────

    private static function auditoriumsFor(?int $auditoriumId): array {
        if ($auditoriumId) {
            $stmt = getDB()->prepare('SELECT * FROM auditoriums WHERE id = ?');
            $stmt->execute([$auditoriumId]);
            $row = $stmt->fetch();
            return $row ? [$row] : [];
        }
        $stmt = getDB()->query("SELECT * FROM auditoriums WHERE status = 'active' ORDER BY id ASC");
        return $stmt->fetchAll();
    }
}
