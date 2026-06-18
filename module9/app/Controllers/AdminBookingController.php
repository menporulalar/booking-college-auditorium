<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/Auditorium.php';
require_once __DIR__ . '/../Models/AdminLog.php';
require_once __DIR__ . '/../Helpers/NotificationService.php';

class AdminBookingController {

    // ── GET /admin/bookings/pending ───────────────────────────

    public function pending(): void {
        Auth::requireRole('admin', 'superadmin');
        $bookings = Booking::allPending();

        // Pre-fetch conflict info + equipment for each
        foreach ($bookings as &$b) {
            if ($b['status'] === 'pending_conflict') {
                $b['conflicts'] = Booking::detectConflicts(
                    (int)$b['auditorium_id'],
                    $b['start_datetime'],
                    $b['end_datetime'],
                    (int)$b['id']
                );
            } else {
                $b['conflicts'] = [];
            }
            $b['equipment'] = Booking::getEquipment((int)$b['id']);
        }
        unset($b);

        $flash = $this->popFlash();
        include __DIR__ . '/../../views/admin/bookings/pending.php';
    }

    // ── GET /admin/bookings ────────────────────────────────────

    public function index(): void {
        Auth::requireRole('admin', 'superadmin');

        $filters = [
            'status'        => $_GET['status'] ?? '',
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'from'          => $_GET['from'] ?? '',
            'to'            => $_GET['to'] ?? '',
            'search'        => trim($_GET['search'] ?? ''),
        ];

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        $offset  = ($page - 1) * $perPage;

        $bookings    = Booking::allForAdmin($filters, $perPage, $offset);
        $total       = Booking::countForAdmin($filters);
        $pages       = (int)ceil($total / $perPage);
        $auditoriums = Auditorium::all();
        $flash       = $this->popFlash();

        include __DIR__ . '/../../views/admin/bookings/index.php';
    }

    // ── GET /admin/bookings/{id} ──────────────────────────────

    public function show(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }

        $conflicts = [];
        if (in_array($booking['status'], ['pending', 'pending_conflict'])) {
            $conflicts = Booking::detectConflicts(
                (int)$booking['auditorium_id'],
                $booking['start_datetime'],
                $booking['end_datetime'],
                $id
            );
        }

        $equipment = Booking::getEquipment($id);
        $hall      = Auditorium::find((int)$booking['auditorium_id']);

        $seriesBookings = [];
        if ($booking['recurrence_group_id']) {
            $seriesBookings = Booking::seriesBookings((int)$booking['recurrence_group_id']);
        }

        $flash = $this->popFlash();
        include __DIR__ . '/../../views/admin/bookings/show.php';
    }

    // ── POST /admin/bookings/{id}/approve ─────────────────────

    public function approve(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }

        $note          = trim($_POST['admin_note'] ?? '');
        $isOverride    = $booking['status'] === 'pending_conflict';
        $overrideReason = trim($_POST['override_reason'] ?? '');

        if ($isOverride && empty($overrideReason)) {
            $this->flash('error', 'An override reason is required to approve a booking with a scheduling conflict.');
            header('Location: ' . APP_URL . '/admin/bookings/' . $id);
            exit;
        }

        Booking::approve(
            $id,
            $note ?: null,
            $isOverride ? Auth::user()['id'] : null,
            $isOverride ? $overrideReason : null
        );

        AdminLog::record(
            Auth::user()['id'],
            $isOverride ? 'booking_approved_override' : 'booking_approved',
            'booking', $id,
            $isOverride ? "Override: {$overrideReason}" : ($note ?: $booking['event_name'])
        );

        // Notify staff
        $updated = Booking::find($id);
        if ($updated) {
            if ($isOverride) {
                NotificationService::bookingApprovedOverride($updated);
            } else {
                NotificationService::bookingApproved($updated);
            }
        }

        $this->flash('success', "Booking <strong>{$booking['event_name']}</strong> approved" . ($isOverride ? ' (with conflict override).' : '.'));
        $this->redirectBack($id);
    }

    // ── POST /admin/bookings/{id}/reject ──────────────────────

    public function reject(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }

        $note = trim($_POST['admin_note'] ?? '');
        if (empty($note)) {
            $this->flash('error', 'A reason is required when rejecting a booking.');
            header('Location: ' . APP_URL . '/admin/bookings/' . $id);
            exit;
        }

        Booking::reject($id, $note);
        AdminLog::record(Auth::user()['id'], 'booking_rejected', 'booking', $id, $note);

        $updated = Booking::find($id);
        if ($updated) {
            NotificationService::bookingRejected($updated);
        }

        $this->flash('success', "Booking <strong>{$booking['event_name']}</strong> rejected.");
        $this->redirectBack($id);
    }

    // ── POST /admin/bookings/{id}/edit ────────────────────────

    public function update(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }

        $startDt = ($_POST['start_date'] ?? '') . ' ' . ($_POST['start_time'] ?? '') . ':00';
        $endDt   = ($_POST['start_date'] ?? '') . ' ' . ($_POST['end_time']   ?? '') . ':00';

        $data = [
            'event_name'           => trim($_POST['event_name'] ?? ''),
            'event_description'    => trim($_POST['event_description'] ?? ''),
            'attendee_count'       => (int)($_POST['attendee_count'] ?? 0) ?: null,
            'start_datetime'       => $startDt,
            'end_datetime'         => $endDt,
            'special_requirements' => trim($_POST['special_requirements'] ?? ''),
        ];

        if (empty($data['event_name']) || strtotime($endDt) <= strtotime($startDt)) {
            $this->flash('error', 'Invalid booking details — check event name and time range.');
            header('Location: ' . APP_URL . '/admin/bookings/' . $id . '/edit');
            exit;
        }

        Booking::updateAdminDetails($id, $data);

        // Re-check conflict after edit (if still pending)
        if (in_array($booking['status'], ['pending', 'pending_conflict'])) {
            $hasConflict = Booking::hasConflict((int)$booking['auditorium_id'], $startDt, $endDt, $id);
            $newStatus   = $hasConflict ? 'pending_conflict' : 'pending';
            getDB()->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
        }

        AdminLog::record(Auth::user()['id'], 'booking_edited_by_admin', 'booking', $id, $data['event_name']);
        $this->flash('success', 'Booking details updated.');
        header('Location: ' . APP_URL . '/admin/bookings/' . $id);
        exit;
    }

    public function edit(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }
        $flash = $this->popFlash();
        include __DIR__ . '/../../views/admin/bookings/edit.php';
    }

    // ── POST /admin/bookings/{id}/suggest-alternate ───────────

    public function suggestAlternate(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $booking = Booking::find($id);
        if (!$booking) { $this->notFound(); }

        $suggestedDate = $_POST['suggested_date'] ?? '';
        $suggestedStart = $_POST['suggested_start'] ?? '';
        $suggestedEnd   = $_POST['suggested_end'] ?? '';

        $note = "Admin suggests an alternate slot: {$suggestedDate} {$suggestedStart}–{$suggestedEnd}. "
              . trim($_POST['message'] ?? '');

        Booking::reject($id, $note);
        AdminLog::record(Auth::user()['id'], 'booking_alternate_suggested', 'booking', $id, $note);

        $updated = Booking::find($id);
        if ($updated) {
            NotificationService::alternateSuggested($updated);
        }

        $this->flash('success', 'Alternate time suggested. The booking has been marked rejected with your suggestion noted for the staff member.');
        header('Location: ' . APP_URL . '/admin/bookings/' . $id);
        exit;
    }

    // ── Series actions ─────────────────────────────────────────

    public function approveSeries(int $groupId): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $note  = trim($_POST['admin_note'] ?? '');
        $count = Booking::approveSeries($groupId, $note ?: null);

        AdminLog::record(Auth::user()['id'], 'series_approved', 'recurrence_group', $groupId, "{$count} occurrences approved");

        $series = Booking::seriesBookings($groupId);
        if ($series && $count > 0) {
            NotificationService::seriesApproved($series[0], $count);
        }

        $this->flash('success', "{$count} occurrence(s) in the series approved.");
        header('Location: ' . APP_URL . '/admin/bookings/pending');
        exit;
    }

    public function rejectSeries(int $groupId): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $note = trim($_POST['admin_note'] ?? '');
        if (empty($note)) {
            $this->flash('error', 'A reason is required when rejecting a series.');
            header('Location: ' . APP_URL . '/admin/bookings/pending');
            exit;
        }

        $series = Booking::seriesBookings($groupId);
        $count  = Booking::rejectSeries($groupId, $note);
        AdminLog::record(Auth::user()['id'], 'series_rejected', 'recurrence_group', $groupId, "{$count} occurrences rejected");

        if ($series && $count > 0) {
            NotificationService::seriesRejected($series[0], $count, $note);
        }

        $this->flash('success', "{$count} occurrence(s) in the series rejected.");
        header('Location: ' . APP_URL . '/admin/bookings/pending');
        exit;
    }

    // ── Bulk actions ────────────────────────────────────────────

    public function bulkAction(): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $ids    = array_map('intval', $_POST['booking_ids'] ?? []);
        $action = $_POST['bulk_action'] ?? '';

        if (empty($ids)) {
            $this->flash('error', 'No bookings selected.');
            header('Location: ' . APP_URL . '/admin/bookings/pending');
            exit;
        }

        if ($action === 'approve') {
            $count = Booking::bulkApprove($ids);
            AdminLog::record(Auth::user()['id'], 'bulk_approved', 'booking', 0, "{$count} bookings approved");
            $this->flash('success', "{$count} booking(s) approved. (Bookings with conflicts must be reviewed individually.)");
        } elseif ($action === 'reject') {
            $note = trim($_POST['bulk_note'] ?? 'Rejected via bulk action.');
            $count = Booking::bulkReject($ids, $note);
            AdminLog::record(Auth::user()['id'], 'bulk_rejected', 'booking', 0, "{$count} bookings rejected");
            $this->flash('success', "{$count} booking(s) rejected.");
        } else {
            $this->flash('error', 'Unknown bulk action.');
        }

        header('Location: ' . APP_URL . '/admin/bookings/pending');
        exit;
    }

    // ── Equipment status update ─────────────────────────────────

    public function updateEquipment(int $bookingId): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $booking = Booking::find($bookingId);
        if (!$booking) { $this->notFound(); }

        $equipment = Booking::getEquipment($bookingId);
        $equipmentById = [];
        foreach ($equipment as $e) { $equipmentById[$e['id']] = $e; }

        $newlyUnavailable = [];

        foreach ($_POST['equipment_status'] ?? [] as $equipmentId => $status) {
            if (in_array($status, ['requested', 'confirmed', 'unavailable'], true)) {
                $equipmentId = (int)$equipmentId;
                $existing = $equipmentById[$equipmentId] ?? null;

                if ($status === 'unavailable' && $existing && $existing['status'] !== 'unavailable') {
                    $newlyUnavailable[] = Booking::EQUIPMENT_OPTIONS[$existing['equipment_name']] ?? $existing['equipment_name'];
                }

                Booking::updateEquipmentStatus($equipmentId, $status);
            }
        }

        AdminLog::record(Auth::user()['id'], 'equipment_status_updated', 'booking', $bookingId, '');

        if ($newlyUnavailable) {
            NotificationService::equipmentUnavailable($booking, $newlyUnavailable);
        }

        $this->flash('success', 'Equipment status updated.');
        header('Location: ' . APP_URL . '/admin/bookings/' . $bookingId);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function redirectBack(int $id): never {
        // After approve/reject on the pending list, return to the list;
        // if acted on from detail page, return to detail
        $ref = $_POST['return_to'] ?? 'pending';
        if ($ref === 'detail') {
            header('Location: ' . APP_URL . '/admin/bookings/' . $id);
        } else {
            header('Location: ' . APP_URL . '/admin/bookings/pending');
        }
        exit;
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

    private function notFound(): never {
        http_response_code(404);
        include __DIR__ . '/../../views/errors/404.php';
        exit;
    }
}
