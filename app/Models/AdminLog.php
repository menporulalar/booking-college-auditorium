<?php

require_once __DIR__ . '/../../config/database.php';

class AdminLog {

    public static function record(int $adminId, string $action, string $targetType = '', int $targetId = 0, string $detail = ''): void {
        try {
            getDB()->prepare(
                'INSERT INTO admin_log (admin_id, action, target_type, target_id, detail)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$adminId, $action, $targetType ?: null, $targetId ?: null, $detail ?: null]);
        } catch (PDOException $e) {
            error_log("AdminLog error: " . $e->getMessage());
        }
    }

    public static function recent(int $limit = 20): array {
        $stmt = getDB()->prepare(
            'SELECT l.*, u.name AS admin_name
             FROM admin_log l
             JOIN users u ON u.id = l.admin_id
             ORDER BY l.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
