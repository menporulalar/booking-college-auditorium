<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/Auth.php';
require_once __DIR__ . '/../Models/Report.php';
require_once __DIR__ . '/../Models/Auditorium.php';
require_once __DIR__ . '/../Models/Booking.php';

class ReportController {

    // ── GET /admin/reports ────────────────────────────────────

    public function index(): void {
        Auth::requireRole('admin', 'superadmin');

        $auditoriums = Auditorium::all();
        $filters = [
            'from'          => $_GET['from'] ?? date('Y-m-01'),
            'to'            => $_GET['to']   ?? date('Y-m-t'),
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'status'        => $_GET['status'] ?? '',
        ];
        $report = $_GET['report'] ?? 'summary';

        $data = $this->buildReportData($report, $filters);

        include __DIR__ . '/../../views/admin/reports/index.php';
    }

    // ── GET /admin/reports/export/pdf ─────────────────────────

    public function exportPdf(): void {
        Auth::requireRole('admin', 'superadmin');

        $report = $_GET['report'] ?? 'summary';
        $filters = [
            'from'          => $_GET['from'] ?? date('Y-m-01'),
            'to'            => $_GET['to']   ?? date('Y-m-t'),
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'status'        => $_GET['status'] ?? '',
        ];
        $data = $this->buildReportData($report, $filters);

        require_once __DIR__ . '/../Helpers/ReportPdfExport.php';
        $pdf = ReportPdfExport::generate($report, $data, $filters);

        $filename = "report-{$report}-{$filters['from']}-to-{$filters['to']}.pdf";
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
        exit;
    }

    // ── GET /admin/reports/export/excel ───────────────────────

    public function exportExcel(): void {
        Auth::requireRole('admin', 'superadmin');

        $report = $_GET['report'] ?? 'summary';
        $filters = [
            'from'          => $_GET['from'] ?? date('Y-m-01'),
            'to'            => $_GET['to']   ?? date('Y-m-t'),
            'auditorium_id' => $_GET['auditorium_id'] ?? '',
            'status'        => $_GET['status'] ?? '',
        ];
        $data = $this->buildReportData($report, $filters);

        require_once __DIR__ . '/../Helpers/ReportExcelExport.php';
        $filename = "report-{$report}-{$filters['from']}-to-{$filters['to']}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        ReportExcelExport::generate($report, $data, $filters);
        exit;
    }

    // ── Build data for any report type ────────────────────────

    private function buildReportData(string $report, array $filters): array {
        $from = $filters['from'];
        $to   = $filters['to'];
        $hallId = $filters['auditorium_id'] ? (int)$filters['auditorium_id'] : null;

        return match ($report) {
            'summary'      => [
                'rows'  => Report::bookingSummary($filters),
                'stats' => Report::bookingSummaryStats($filters),
            ],
            'utilization'  => [
                'rows' => Report::utilization($from, $to, $hallId),
            ],
            'heatmap'      => [
                'grid' => Report::peakTimeHeatmap($from, $to, $hallId),
            ],
            'equipment'    => [
                'rows' => Report::equipmentUsage($from, $to, $hallId),
            ],
            'overrides'    => [
                'rows' => Report::overrideLog($from, $to),
            ],
            'notifications'=> [
                'rows' => Report::notificationDeliveryStats($from, $to),
            ],
            default => ['rows' => []],
        };
    }
}
