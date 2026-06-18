<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/Auditorium.php';
require_once __DIR__ . '/../Models/BlackoutDate.php';
require_once __DIR__ . '/../Models/AdminLog.php';
require_once __DIR__ . '/../Helpers/NotificationService.php';
require_once __DIR__ . '/../Helpers/ICalExport.php';

class BookingController {

    // ── GET /calendar ─────────────────────────────────────────

    public function showCalendar(): void {
        Auth::check();
        $auditoriums = Auditorium::all();
        $selectedHall = (int)($_GET['hall'] ?? 0);
        $flash = $this->popFlash();
        include __DIR__ . '/../../views/staff/calendar.php';
    }

    // ── GET /api/calendar-events (JSON for FullCalendar) ──────

    public function calendarEvents(): void {
        Auth::check();
        header('Content-Type: application/json');

        $start        = $_GET['start']         ?? date('Y-m-01');
        $end          = $_GET['end']           ?? date('Y-m-t');
        $auditoriumId = (int)($_GET['hall']    ?? 0) ?: null;

        $bookings   = Booking::forCalendar($start, $end, $auditoriumId);
        $blackouts  = Booking::blackoutsForCalendar($start, $end, $auditoriumId);

        echo json_encode(array_merge($bookings, $blackouts));
        exit;
    }

    // ── GET /bookings/new ─────────────────────────────────────

    public function showBookingForm(): void {
        Auth::check();
        $auditoriums = Auditorium::all();
        $equipment   = Booking::EQUIPMENT_OPTIONS;
        $errors      = [];
        $old         = $_GET; // pre-fill from calendar click
        $flash       = $this->popFlash();
        include __DIR__ . '/../../views/staff/booking-form.php';
    }

    // ── POST /bookings/new ────────────────────────────────────

    public function storeBooking(): void {
        Auth::check();
        Auth::verifyCsrf();

        $user = Auth::user();
        $data = $this->extractFormData();

        // Load hall for validation
        $hall = $data['auditorium_id'] ? Auditorium::find((int)$data['auditorium_id']) : [];

        $errors = Booking::validate($data, $hall ?: []);

        // Check blackout date
        if (empty($errors) && $hall) {
            $bookingDate = substr($data['start_datetime'], 0, 10);
            if (BlackoutDate::isBlackout($bookingDate, (int)$data['auditorium_id'])) {
                $errors[] = "The selected date ({$bookingDate}) is a blackout date and unavailable for booking.";
            }
        }

        if ($errors) {
            $auditoriums = Auditorium::all();
            $equipment   = Booking::EQUIPMENT_OPTIONS;
            $old         = $_POST;
            $flash       = null;
            include __DIR__ . '/../../views/staff/booking-form.php';
            return;
        }

        if (!$data['is_recurring']) {
            // Single booking
            $data['has_conflict'] = Booking::hasConflict(
                (int)$data['auditorium_id'],
                $data['start_datetime'],
                $data['end_datetime']
            );
            $bookingId = Booking::create(array_merge($data, ['user_id' => $user['id']]));
            Booking::saveEquipment($bookingId, $data['equipment'] ?? []);

            AdminLog::record($user['id'], 'booking_submitted', 'booking', $bookingId, $data['event_name']);

            // Notify admin
            $newBooking = Booking::find($bookingId);
            if ($newBooking) {
                NotificationService::bookingSubmitted($newBooking);
                if ($data['has_conflict']) {
                    NotificationService::conflictFlagged($newBooking);
                }
            }

            $this->flash('success', $data['has_conflict']
                ? 'Your booking request was submitted. Note: there may be a scheduling conflict — an admin will review it.'
                : 'Booking request submitted successfully! You will be notified once approved.'
            );
            header('Location: ' . APP_URL . '/bookings');
            exit;
        }

        // Recurring booking
        $groupId     = Booking::createRecurrenceGroup(
            $data['recurrence_pattern'],
            (int)($data['recurrence_interval'] ?? 1),
            $data['recurrence_end_date']
        );
        $occurrences = Booking::generateOccurrences(
            $data['start_datetime'],
            $data['end_datetime'],
            $data['recurrence_pattern'],
            (int)($data['recurrence_interval'] ?? 1),
            $data['recurrence_end_date'],
            $data['custom_dates'] ?? []
        );

        if (empty($occurrences)) {
            $errors[]    = 'No valid occurrences could be generated from your recurrence settings.';
            $auditoriums = Auditorium::all();
            $equipment   = Booking::EQUIPMENT_OPTIONS;
            $old         = $_POST;
            $flash       = null;
            include __DIR__ . '/../../views/staff/booking-form.php';
            return;
        }

        $createdCount   = 0;
        $conflictCount  = 0;
        $firstBookingId = null;

        foreach ($occurrences as $occ) {
            // Check blackout per occurrence
            $occDate = substr($occ['start'], 0, 10);
            if (BlackoutDate::isBlackout($occDate, (int)$data['auditorium_id'])) continue;

            $hasConflict = Booking::hasConflict((int)$data['auditorium_id'], $occ['start'], $occ['end']);
            if ($hasConflict) $conflictCount++;

            $bookingId = Booking::create([
                'user_id'              => $user['id'],
                'auditorium_id'        => $data['auditorium_id'],
                'event_name'           => $data['event_name'],
                'event_description'    => $data['event_description'],
                'start_datetime'       => $occ['start'],
                'end_datetime'         => $occ['end'],
                'attendee_count'       => $data['attendee_count'],
                'special_requirements' => $data['special_requirements'],
                'recurrence_group_id'  => $groupId,
                'has_conflict'         => $hasConflict,
            ]);
            Booking::saveEquipment($bookingId, $data['equipment'] ?? []);
            $createdCount++;
            if ($firstBookingId === null) $firstBookingId = $bookingId;
        }

        AdminLog::record($user['id'], 'recurring_booking_submitted', 'recurrence_group', $groupId,
            "{$data['event_name']} — {$createdCount} occurrences");

        // Notify admin once for the whole series (using first occurrence as reference)
        if ($firstBookingId) {
            $firstBooking = Booking::find($firstBookingId);
            if ($firstBooking) {
                NotificationService::bookingSubmitted($firstBooking);
                if ($conflictCount > 0) {
                    NotificationService::conflictFlagged($firstBooking);
                }
            }
        }

        $msg = "Recurring booking submitted: {$createdCount} occurrence(s) created.";
        if ($conflictCount > 0) {
            $msg .= " {$conflictCount} occurrence(s) have potential conflicts and will be reviewed by admin.";
        }
        $this->flash('success', $msg);
        header('Location: ' . APP_URL . '/bookings');
        exit;
    }

    // ── GET /bookings ─────────────────────────────────────────

    public function myBookings(): void {
        Auth::check();
        $user     = Auth::user();
        $status   = $_GET['status'] ?? '';
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 15;
        $offset   = ($page - 1) * $perPage;

        $bookings = Booking::forUser($user['id'], $status, $perPage, $offset);
        $total    = Booking::countForUser($user['id'], $status);
        $pages    = (int)ceil($total / $perPage);
        $flash    = $this->popFlash();
        include __DIR__ . '/../../views/staff/my-bookings.php';
    }

    // ── GET /bookings/{id} ────────────────────────────────────

    public function showBooking(int $id): void {
        Auth::check();
        $booking = Booking::find($id);

        if (!$booking || $booking['user_id'] !== Auth::user()['id']) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }
        $equipment = Booking::getEquipment($id);
        $flash     = $this->popFlash();
        include __DIR__ . '/../../views/staff/booking-detail.php';
    }

    // ── GET /bookings/{id}/ical ────────────────────────────────

    public function downloadIcal(int $id): void {
        Auth::check();
        $booking = Booking::find($id);

        if (!$booking || $booking['user_id'] !== Auth::user()['id']) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        if ($booking['status'] !== 'approved') {
            $this->flash('error', 'Only approved bookings can be exported to your calendar.');
            header('Location: ' . APP_URL . '/bookings/' . $id);
            exit;
        }

        $ics      = ICalExport::forBooking($booking);
        $filename = 'booking-' . $id . '-' . preg_replace('/[^a-z0-9]+/i', '-', $booking['event_name']) . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ics));
        echo $ics;
        exit;
    }

    // ── GET /bookings/series/{groupId}/ical ────────────────────

    public function downloadSeriesIcal(int $groupId): void {
        Auth::check();
        $user = Auth::user();

        $bookings = Booking::seriesBookings($groupId, 'approved');

        // Verify ownership of at least one booking in the series
        $owned = array_filter($bookings, function($b) use ($user) {
            $full = Booking::find((int)$b['id']);
            return $full && $full['user_id'] === $user['id'];
        });

        if (empty($owned)) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Re-fetch full records for description fields
        $fullBookings = array_map(fn($b) => Booking::find((int)$b['id']), $owned);

        $ics      = ICalExport::forBookings($fullBookings);
        $filename = 'booking-series-' . $groupId . '.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($ics));
        echo $ics;
        exit;
    }

    // ── POST /bookings/{id}/cancel ────────────────────────────

    public function cancelBooking(int $id): void {
        Auth::check();
        Auth::verifyCsrf();

        $user    = Auth::user();
        $booking = Booking::find($id);

        if (!$booking || $booking['user_id'] !== $user['id']) {
            $this->flash('error', 'Booking not found.');
            header('Location: ' . APP_URL . '/bookings');
            exit;
        }

        $cancelAll = !empty($_POST['cancel_series']) && $booking['recurrence_group_id'];

        if ($cancelAll) {
            $count = Booking::cancelSeries((int)$booking['recurrence_group_id'], $user['id']);
            AdminLog::record($user['id'], 'booking_series_cancelled', 'recurrence_group',
                (int)$booking['recurrence_group_id'], "{$count} occurrences cancelled");
            $this->flash('success', "{$count} recurring booking(s) cancelled successfully.");
        } else {
            $result = Booking::cancel($id, $user['id']);
            if ($result) {
                AdminLog::record($user['id'], 'booking_cancelled', 'booking', $id, $booking['event_name']);
                NotificationService::bookingCancelled($booking);
                $this->flash('success', 'Booking cancelled successfully.');
            } else {
                $this->flash('error', 'This booking cannot be cancelled (it may already be approved or cancelled).');
            }
        }

        header('Location: ' . APP_URL . '/bookings');
        exit;
    }

    // ── GET /api/check-conflict (AJAX) ────────────────────────

    public function checkConflict(): void {
        Auth::check();
        header('Content-Type: application/json');

        $auditoriumId = (int)($_GET['auditorium_id'] ?? 0);
        $start        = $_GET['start'] ?? '';
        $end          = $_GET['end']   ?? '';
        $excludeId    = (int)($_GET['exclude'] ?? 0) ?: null;

        if (!$auditoriumId || !$start || !$end) {
            echo json_encode(['conflict' => false, 'conflicts' => []]);
            exit;
        }

        $conflicts = Booking::detectConflicts($auditoriumId, $start, $end, $excludeId);
        echo json_encode([
            'conflict'  => count($conflicts) > 0,
            'conflicts' => array_map(fn($c) => [
                'id'        => $c['id'],
                'eventName' => $c['event_name'],
                'start'     => date('d M Y, g:i A', strtotime($c['start_datetime'])),
                'end'       => date('g:i A', strtotime($c['end_datetime'])),
                'bookedBy'  => $c['user_name'],
                'status'    => $c['status'],
            ], $conflicts),
        ]);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function extractFormData(): array {
        $equipment = [];
        foreach (Booking::EQUIPMENT_OPTIONS as $key => $_) {
            $qty = (int)($_POST['equipment'][$key] ?? 0);
            if ($qty > 0) $equipment[$key] = $qty;
        }

        $customDates = [];
        if (!empty($_POST['custom_dates'])) {
            $customDates = array_filter(array_map('trim', explode(',', $_POST['custom_dates'])));
        }

        $startDate = $_POST['start_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime   = $_POST['end_time']   ?? '';
        $startDt   = $startDate && $startTime ? $startDate . ' ' . $startTime . ':00' : '';
        $endDt     = $startDate && $endTime   ? $startDate . ' ' . $endTime   . ':00' : '';

        return [
            'auditorium_id'        => $_POST['auditorium_id']        ?? '',
            'event_name'           => trim($_POST['event_name']       ?? ''),
            'event_description'    => trim($_POST['event_description'] ?? ''),
            'start_datetime'       => $startDt,
            'end_datetime'         => $endDt,
            'attendee_count'       => (int)($_POST['attendee_count']  ?? 0) ?: null,
            'special_requirements' => trim($_POST['special_requirements'] ?? ''),
            'is_recurring'         => !empty($_POST['is_recurring']),
            'recurrence_pattern'   => $_POST['recurrence_pattern']   ?? 'weekly',
            'recurrence_interval'  => (int)($_POST['recurrence_interval'] ?? 1),
            'recurrence_end_date'  => $_POST['recurrence_end_date']  ?? '',
            'custom_dates'         => $customDates,
            'equipment'            => $equipment,
        ];
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
