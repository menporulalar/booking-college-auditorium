<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Auditorium.php';
require_once __DIR__ . '/../Models/BlackoutDate.php';
require_once __DIR__ . '/../Models/AdminLog.php';

class AuditoriumController {

    // ── GET /admin/auditoriums ────────────────────────────────

    public function index(): void {
        Auth::requireRole('admin', 'superadmin');
        $auditoriums = Auditorium::all(true); // include inactive
        $flash       = $this->popFlash();
        include __DIR__ . '/../../views/admin/auditoriums/index.php';
    }

    // ── GET /admin/auditoriums/create ─────────────────────────

    public function create(): void {
        Auth::requireRole('admin', 'superadmin');
        $facilities = Auditorium::FACILITIES;
        $errors     = [];
        $old        = [];
        $flash      = $this->popFlash();
        include __DIR__ . '/../../views/admin/auditoriums/form.php';
    }

    // ── POST /admin/auditoriums/create ────────────────────────

    public function store(): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $data   = $this->extractFormData();
        $errors = Auditorium::validate($data);

        if ($errors) {
            $facilities = Auditorium::FACILITIES;
            $old        = $_POST;
            $flash      = null;
            include __DIR__ . '/../../views/admin/auditoriums/form.php';
            return;
        }

        $id = Auditorium::create($data);

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $file = Auditorium::handleUpload($_FILES['photo'], "hall{$id}_photo");
            if ($file) Auditorium::updatePhoto($id, $file, 'photo');
        }

        // Handle floor plan upload
        if (!empty($_FILES['floor_plan']['name'])) {
            $file = Auditorium::handleUpload($_FILES['floor_plan'], "hall{$id}_fp");
            if ($file) Auditorium::updatePhoto($id, $file, 'floor_plan');
        }

        AdminLog::record(Auth::user()['id'], 'auditorium_created', 'auditorium', $id, "Created: {$data['name']}");
        $this->flash('success', "Auditorium <strong>{$data['name']}</strong> created successfully.");
        header('Location: ' . APP_URL . '/admin/auditoriums');
        exit;
    }

    // ── GET /admin/auditoriums/{id}/edit ──────────────────────

    public function edit(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        $auditorium = Auditorium::find($id);
        if (!$auditorium) { $this->notFound(); }

        $facilities = Auditorium::FACILITIES;
        $errors     = [];
        $old        = $auditorium;
        $flash      = $this->popFlash();
        include __DIR__ . '/../../views/admin/auditoriums/form.php';
    }

    // ── POST /admin/auditoriums/{id}/edit ─────────────────────

    public function update(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $auditorium = Auditorium::find($id);
        if (!$auditorium) { $this->notFound(); }

        $data   = $this->extractFormData();
        $errors = Auditorium::validate($data);

        if ($errors) {
            $facilities = Auditorium::FACILITIES;
            $old        = array_merge($auditorium, $_POST);
            $flash      = null;
            include __DIR__ . '/../../views/admin/auditoriums/form.php';
            return;
        }

        Auditorium::update($id, $data);

        // Photo upload
        if (!empty($_FILES['photo']['name'])) {
            Auditorium::deletePhoto($id, 'photo');
            $file = Auditorium::handleUpload($_FILES['photo'], "hall{$id}_photo");
            if ($file) Auditorium::updatePhoto($id, $file, 'photo');
        }

        // Floor plan upload
        if (!empty($_FILES['floor_plan']['name'])) {
            Auditorium::deletePhoto($id, 'floor_plan');
            $file = Auditorium::handleUpload($_FILES['floor_plan'], "hall{$id}_fp");
            if ($file) Auditorium::updatePhoto($id, $file, 'floor_plan');
        }

        // Remove photo if requested
        if (isset($_POST['remove_photo']))     Auditorium::deletePhoto($id, 'photo');
        if (isset($_POST['remove_floor_plan'])) Auditorium::deletePhoto($id, 'floor_plan');

        AdminLog::record(Auth::user()['id'], 'auditorium_updated', 'auditorium', $id, "Updated: {$data['name']}");
        $this->flash('success', "Auditorium <strong>{$data['name']}</strong> updated successfully.");
        header('Location: ' . APP_URL . '/admin/auditoriums');
        exit;
    }

    // ── POST /admin/auditoriums/{id}/toggle ───────────────────

    public function toggleStatus(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $auditorium = Auditorium::find($id);
        if (!$auditorium) { $this->notFound(); }

        $newStatus = Auditorium::toggleStatus($id);
        $action    = $newStatus === 'active' ? 'activated' : 'deactivated';

        AdminLog::record(Auth::user()['id'], "auditorium_{$action}", 'auditorium', $id, "{$auditorium['name']} {$action}");
        $this->flash('success', "Auditorium <strong>{$auditorium['name']}</strong> {$action}.");
        header('Location: ' . APP_URL . '/admin/auditoriums');
        exit;
    }

    // ── GET /admin/auditoriums/{id} (detail view) ─────────────

    public function show(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        $auditorium = Auditorium::find($id);
        if (!$auditorium) { $this->notFound(); }

        $blackouts  = BlackoutDate::all(['auditorium_id' => $id, 'from' => date('Y-m-d')]);
        $facilities = Auditorium::FACILITIES;
        $flash      = $this->popFlash();
        include __DIR__ . '/../../views/admin/auditoriums/show.php';
    }

    // ── Blackout dates ────────────────────────────────────────

    public function blackoutIndex(): void {
        Auth::requireRole('admin', 'superadmin');
        $auditoriums = Auditorium::all();
        $filter      = [
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'from'          => $_GET['from'] ?? date('Y-m-d'),
            'to'            => $_GET['to']   ?? date('Y-m-d', strtotime('+3 months')),
        ];
        $blackouts   = BlackoutDate::all($filter);
        $flash       = $this->popFlash();
        include __DIR__ . '/../../views/admin/blackout/index.php';
    }

    public function blackoutStore(): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $data   = [
            'auditorium_id' => $_POST['auditorium_id'] ?? null,
            'blackout_date' => $_POST['blackout_date'] ?? '',
            'reason'        => trim($_POST['reason'] ?? ''),
            'created_by'    => Auth::user()['id'],
        ];
        $errors = BlackoutDate::validate($data);

        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            header('Location: ' . APP_URL . '/admin/blackout-dates');
            exit;
        }

        $result = BlackoutDate::create($data);
        if ($result === false) {
            $this->flash('error', 'That date is already blocked for this auditorium.');
        } else {
            $hallName = $data['auditorium_id']
                ? (Auditorium::find((int)$data['auditorium_id'])['name'] ?? 'Unknown')
                : 'All Auditoriums';
            AdminLog::record(Auth::user()['id'], 'blackout_added', 'blackout', $result,
                "Blocked {$data['blackout_date']} for {$hallName}");
            $this->flash('success', "Blackout date added for <strong>{$data['blackout_date']}</strong>.");
        }

        header('Location: ' . APP_URL . '/admin/blackout-dates');
        exit;
    }

    public function blackoutDelete(int $id): void {
        Auth::requireRole('admin', 'superadmin');
        Auth::verifyCsrf();

        $row = BlackoutDate::find($id);
        if ($row) {
            BlackoutDate::delete($id);
            AdminLog::record(Auth::user()['id'], 'blackout_removed', 'blackout', $id,
                "Removed blackout {$row['blackout_date']}");
            $this->flash('success', "Blackout date removed.");
        }
        header('Location: ' . APP_URL . '/admin/blackout-dates');
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────

    private function extractFormData(): array {
        $facilities = array_keys(Auditorium::FACILITIES);
        $selected   = [];
        foreach ($facilities as $key) {
            if (!empty($_POST['facilities'][$key])) {
                $selected[] = $key;
            }
        }
        return [
            'name'               => trim($_POST['name'] ?? ''),
            'capacity'           => (int)($_POST['capacity'] ?? 0),
            'facilities'         => $selected,
            'operational_start'  => $_POST['operational_start'] ?? '08:00',
            'operational_end'    => $_POST['operational_end']   ?? '20:00',
            'status'             => $_POST['status'] ?? 'active',
            'description'        => trim($_POST['description'] ?? ''),
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

    private function notFound(): never {
        http_response_code(404);
        include __DIR__ . '/../../views/errors/404.php';
        exit;
    }
}
