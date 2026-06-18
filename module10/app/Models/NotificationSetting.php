<?php

require_once __DIR__ . '/../../config/database.php';

class NotificationSetting {

    public static function all(): array {
        $stmt = getDB()->query('SELECT * FROM notification_settings ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public static function isEnabled(string $eventKey): bool {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            foreach (self::all() as $row) {
                $cache[$row['event_key']] = (bool)$row['enabled'];
            }
        }
        // Default to enabled if no row exists (fail-safe so emails aren't silently dropped)
        return $cache[$eventKey] ?? true;
    }

    public static function setEnabled(string $eventKey, bool $enabled): bool {
        $stmt = getDB()->prepare('UPDATE notification_settings SET enabled = ? WHERE event_key = ?');
        return $stmt->execute([$enabled ? 1 : 0, $eventKey]);
    }

    public static function updateBulk(array $enabledKeys): void {
        $db  = getDB();
        $all = self::all();
        $stmt = $db->prepare('UPDATE notification_settings SET enabled = ? WHERE event_key = ?');
        foreach ($all as $row) {
            $enabled = in_array($row['event_key'], $enabledKeys, true);
            $stmt->execute([$enabled ? 1 : 0, $row['event_key']]);
        }
    }
}
