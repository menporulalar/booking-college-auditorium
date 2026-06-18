<?php

require_once __DIR__ . '/../../config/database.php';

class Auditorium {

    // Allowed equipment/facility keys
    public const FACILITIES = [
        'microphone_handheld' => 'Microphone (Handheld)',
        'microphone_lapel'    => 'Microphone (Lapel)',
        'projector'           => 'Projector',
        'screen'              => 'Projection Screen',
        'pa_system'           => 'PA System / Speakers',
        'podium'              => 'Podium / Lectern',
        'laptop'              => 'Laptop',
        'hdmi_adapter'        => 'HDMI Adapter',
        'vga_adapter'         => 'VGA Adapter',
        'extension_cord'      => 'Extension Cord',
        'whiteboard'          => 'Whiteboard',
        'video_conf'          => 'Video Conferencing',
        'stage_lighting'      => 'Stage Lighting',
        'air_conditioning'    => 'Air Conditioning',
    ];

    public const UPLOAD_DIR  = __DIR__ . '/../../public/uploads/auditoriums/';
    public const UPLOAD_URL  = '/uploads/auditoriums/';
    public const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    public const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    // ── Fetch ─────────────────────────────────────────────────

    public static function all(bool $includeInactive = false): array {
        $db  = getDB();
        $sql = 'SELECT * FROM auditoriums';
        if (!$includeInactive) $sql .= ' WHERE status = "active"';
        $sql .= ' ORDER BY id ASC';
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();
        return array_map([self::class, 'decode'], $rows);
    }

    public static function find(int $id): array|false {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM auditoriums WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        return $row ? self::decode($row) : false;
    }

    public static function count(): int {
        return (int) getDB()->query('SELECT COUNT(*) FROM auditoriums WHERE status = "active"')->fetchColumn();
    }

    // ── Create ────────────────────────────────────────────────

    public static function create(array $data): int {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO auditoriums
             (name, capacity, facilities, operational_start, operational_end, status, description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            (int) $data['capacity'],
            json_encode($data['facilities'] ?? []),
            $data['operational_start'] ?? '08:00:00',
            $data['operational_end']   ?? '20:00:00',
            $data['status']            ?? 'active',
            $data['description']       ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    // ── Update ────────────────────────────────────────────────

    public static function update(int $id, array $data): bool {
        $db   = getDB();
        $stmt = $db->prepare(
            'UPDATE auditoriums SET
                name               = ?,
                capacity           = ?,
                facilities         = ?,
                operational_start  = ?,
                operational_end    = ?,
                status             = ?,
                description        = ?
             WHERE id = ?'
        );
        return $stmt->execute([
            $data['name'],
            (int) $data['capacity'],
            json_encode($data['facilities'] ?? []),
            $data['operational_start'] ?? '08:00:00',
            $data['operational_end']   ?? '20:00:00',
            $data['status']            ?? 'active',
            $data['description']       ?? null,
            $id,
        ]);
    }

    public static function updatePhoto(int $id, string $filename, string $type = 'photo'): void {
        $col = $type === 'floor_plan' ? 'floor_plan' : 'photo';
        getDB()->prepare("UPDATE auditoriums SET {$col} = ? WHERE id = ?")
               ->execute([$filename, $id]);
    }

    public static function deletePhoto(int $id, string $type = 'photo'): void {
        $row = self::find($id);
        if (!$row) return;
        $col  = $type === 'floor_plan' ? 'floor_plan' : 'photo';
        $file = self::UPLOAD_DIR . $row[$col];
        if ($row[$col] && file_exists($file)) {
            @unlink($file);
        }
        getDB()->prepare("UPDATE auditoriums SET {$col} = NULL WHERE id = ?")
               ->execute([$id]);
    }

    // ── Toggle status ─────────────────────────────────────────

    public static function toggleStatus(int $id): string {
        $row    = self::find($id);
        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
        getDB()->prepare('UPDATE auditoriums SET status = ? WHERE id = ?')
               ->execute([$newStatus, $id]);
        return $newStatus;
    }

    // ── Image upload ──────────────────────────────────────────

    public static function handleUpload(array $file, string $prefix = 'hall'): string|false {
        if ($file['error'] !== UPLOAD_ERR_OK) return false;
        if ($file['size']  >  self::MAX_FILE_SIZE) return false;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME, true)) return false;

        $ext      = match($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = self::UPLOAD_DIR . $filename;

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        return move_uploaded_file($file['tmp_name'], $dest) ? $filename : false;
    }

    // ── Validation ────────────────────────────────────────────

    public static function validate(array $data): array {
        $errors = [];

        if (empty(trim($data['name'] ?? ''))) {
            $errors[] = 'Auditorium name is required.';
        }
        $cap = (int)($data['capacity'] ?? 0);
        if ($cap < 1 || $cap > 5000) {
            $errors[] = 'Capacity must be between 1 and 5000.';
        }

        $start = $data['operational_start'] ?? '';
        $end   = $data['operational_end']   ?? '';
        if ($start && $end && $start >= $end) {
            $errors[] = 'Operational end time must be after start time.';
        }

        return $errors;
    }

    // ── Helper ────────────────────────────────────────────────

    private static function decode(array $row): array {
        $row['facilities'] = json_decode($row['facilities'] ?? '[]', true) ?? [];
        return $row;
    }

    public static function facilitiesLabel(string $key): string {
        return self::FACILITIES[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
}
