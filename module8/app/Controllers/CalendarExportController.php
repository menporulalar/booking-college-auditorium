<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Helpers/ICalExport.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/Auditorium.php';

class CalendarExportController {

    // ── GET /admin/calendar-export ────────────────────────────

    public function index(): void {
        Auth::requireRole('admin', 'superadmin');
        $auditoriums = Auditorium::all();

        $filters = [
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'from'          => $_GET['from'] ?? date('Y-m-01'),
            'to'            => $_GET['to']   ?? date('Y-m-t'),
        ];

        $preview = [];
        if (!empty($_GET['preview'])) {
            $preview = $this->fetchBookings($filters);
        }

        $flash = $this->popFlash();
        include __DIR__ . '/../../views/admin/calendar-export.php';
    }

    // ── GET /admin/calendar-export/download ───────────────────

    public function download(): void {
        Auth::requireRole('admin', 'superadmin');

        $filters = [
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'from'          => $_GET['from'] ?? date('Y-m-01'),
            'to'            => $_GET['to']   ?? date('Y-m-t'),
        ];

        $bookings = $this->fetchBookings($filters);

        if (empty($bookings)) {
            $this->flash('error', 'No approved bookings found for the selected filters.');
            header('Location: ' . APP_URL . '/admin/calendar-export?' . http_build_query($filters));
            exit;
        }

        $ics = ICalExport::forBookings($bookings);

        $hallName = '';
        if (!empty($filters['auditorium_id'])) {
            $hall = Auditorium::find((int)$filters['auditorium_id']);
            $hallName = $hall ? '-' . preg_replace('/[^a-z0-9]+/i', '-', $hall['name']) : '';
        }
        $filename = 'bookings' . $hallName . '-' . $filters['from'] . '-to-' . $filters['to'] . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ics));
        echo $ics;
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function fetchBookings(array $filters): array {
        $params = ['approved', $filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'];
        $where  = "b.status = ? AND b.start_datetime BETWEEN ? AND ?";

        if (!empty($filters['auditorium_id'])) {
            $where   .= ' AND b.auditorium_id = ?';
            $params[] = (int)$filters['auditorium_id'];
        }

        $stmt = getDB()->prepare(
            "SELECT b.*, u.name AS user_name, u.email AS user_email, a.name AS auditorium_name
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN auditoriums a ON a.id = b.auditorium_id
             WHERE {$where}
             ORDER BY b.start_datetime ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function flash(string $type, string $msg): void {
        $_SESSION["flash_{$type}"] = $msg;
    }

    private function popFlash(): array {
        $flash = [
            'success' => $_SESSION['flash_success'] ?? null,
            'error'   => $_SESSION['flash_error']   ?? null,
        ];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $flash;
    }
}
