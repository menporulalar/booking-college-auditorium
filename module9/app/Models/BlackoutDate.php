<?php

require_once __DIR__ . '/../../config/database.php';

class BlackoutDate {

    // ── Fetch ─────────────────────────────────────────────────

    public static function all(array $filters = []): array {
        $db     = getDB();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['auditorium_id'])) {
            $where[]  = '(auditorium_id = ? OR auditorium_id IS NULL)';
            $params[] = (int)$filters['auditorium_id'];
        }
        if (!empty($filters['from'])) {
            $where[]  = 'blackout_date >= ?';
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[]  = 'blackout_date <= ?';
            $params[] = $filters['to'];
        }

        $sql  = 'SELECT b.*, u.name AS created_by_name,
                        a.name AS auditorium_name
                 FROM blackout_dates b
                 LEFT JOIN users u ON u.id = b.created_by
                 LEFT JOIN auditoriums a ON a.id = b.auditorium_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY b.blackout_date ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): array|false {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT b.*, a.name AS auditorium_name
             FROM blackout_dates b
             LEFT JOIN auditoriums a ON a.id = b.auditorium_id
             WHERE b.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function isBlackout(string $date, int $auditoriumId): bool {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM blackout_dates
             WHERE blackout_date = ?
               AND (auditorium_id = ? OR auditorium_id IS NULL)'
        );
        $stmt->execute([$date, $auditoriumId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // Get all blackout dates for a given month (for calendar)
    public static function forMonth(int $year, int $month, ?int $auditoriumId = null): array {
        $db   = getDB();
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));

        $sql    = 'SELECT blackout_date, reason, auditorium_id FROM blackout_dates
                   WHERE blackout_date BETWEEN ? AND ?';
        $params = [$from, $to];

        if ($auditoriumId !== null) {
            $sql    .= ' AND (auditorium_id = ? OR auditorium_id IS NULL)';
            $params[] = $auditoriumId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Create ────────────────────────────────────────────────

    public static function create(array $data): int|false {
        $db = getDB();
        try {
            $stmt = $db->prepare(
                'INSERT INTO blackout_dates (auditorium_id, blackout_date, reason, created_by)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['auditorium_id'] ?: null,
                $data['blackout_date'],
                $data['reason'] ?? null,
                $data['created_by'],
            ]);
            return (int)$db->lastInsertId();
        } catch (PDOException $e) {
            // Duplicate unique constraint
            return false;
        }
    }

    // ── Delete ────────────────────────────────────────────────

    public static function delete(int $id): bool {
        $stmt = getDB()->prepare('DELETE FROM blackout_dates WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // ── Validate ──────────────────────────────────────────────

    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['blackout_date']) || !strtotime($data['blackout_date'])) {
            $errors[] = 'A valid date is required.';
        }
        if (!empty($data['blackout_date']) && $data['blackout_date'] < date('Y-m-d')) {
            $errors[] = 'Blackout date cannot be in the past.';
        }
        return $errors;
    }
}
