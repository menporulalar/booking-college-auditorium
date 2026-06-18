<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Models/Booking.php';

/**
 * ReportPdfExport — generates PDF reports using TCPDF.
 */
class ReportPdfExport {

    public static function generate(string $report, array $data, array $filters): string {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator(APP_NAME);
        $pdf->SetAuthor(APP_NAME);
        $pdf->SetMargins(12, 18, 12);
        $pdf->SetHeaderMargin(8);
        $pdf->SetFooterMargin(10);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetAutoPageBreak(true, 15);

        $title = self::reportTitle($report);
        $pdf->SetTitle($title);

        // Custom header
        $pdf->setHeaderData('', 0, $title, self::dateRangeLabel($filters));
        $pdf->setHeaderFont(['helvetica', '', 9]);
        $pdf->setFooterFont(['helvetica', 'I', 8]);

        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 9);

        match ($report) {
            'summary'       => self::renderSummary($pdf, $data),
            'utilization'   => self::renderUtilization($pdf, $data),
            'heatmap'       => self::renderHeatmap($pdf, $data),
            'equipment'     => self::renderEquipment($pdf, $data),
            'overrides'     => self::renderOverrides($pdf, $data),
            'notifications' => self::renderNotifications($pdf, $data),
            default         => $pdf->Write(0, 'No data available.'),
        };

        return $pdf->Output('report.pdf', 'S');
    }

    // ── Report renderers ────────────────────────────────────────

    private static function renderSummary(TCPDF $pdf, array $data): void {
        $stats = $data['stats'];

        // Stats row
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Summary', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6,
            "Total: {$stats['total']}   |   Approved: {$stats['approved']}   |   Pending: {$stats['pending']}   |   " .
            "Rejected: {$stats['rejected']}   |   Cancelled: {$stats['cancelled']}   |   Conflicts flagged: {$stats['conflict']}   |   " .
            "Total approved hours: " . round($stats['total_hours'], 1),
            0, 1
        );
        $pdf->Ln(3);

        $header = ['Event', 'Auditorium', 'Requested By', 'Department', 'Date', 'Time', 'Status'];
        $widths = [55, 35, 35, 35, 25, 35, 25];

        self::tableHeader($pdf, $header, $widths);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($data['rows'] as $r) {
            $statusLabel = Booking::STATUSES[$r['status']]['label'] ?? $r['status'];
            $row = [
                self::truncate($r['event_name'], 40),
                self::truncate($r['auditorium_name'], 25),
                self::truncate($r['user_name'], 25),
                self::truncate($r['user_department'] ?? '—', 25),
                date('d M Y', strtotime($r['start_datetime'])),
                date('g:i A', strtotime($r['start_datetime'])) . '-' . date('g:i A', strtotime($r['end_datetime'])),
                $statusLabel,
            ];
            self::tableRow($pdf, $row, $widths);
        }

        if (empty($data['rows'])) {
            $pdf->Cell(0, 8, 'No bookings found for the selected filters.', 0, 1);
        }
    }

    private static function renderUtilization(TCPDF $pdf, array $data): void {
        $header = ['Auditorium', 'Capacity', 'Available Hours', 'Booked Hours', 'Bookings', 'Utilization %'];
        $widths = [60, 30, 40, 40, 30, 40];

        self::tableHeader($pdf, $header, $widths);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['rows'] as $r) {
            $row = [
                $r['auditorium_name'],
                number_format($r['capacity']),
                number_format($r['available_hours'], 1),
                number_format($r['booked_hours'], 1),
                (string)$r['booking_count'],
                $r['utilization_pct'] . '%',
            ];
            self::tableRow($pdf, $row, $widths);
        }

        if (empty($data['rows'])) {
            $pdf->Cell(0, 8, 'No data available.', 0, 1);
        }
    }

    private static function renderHeatmap(TCPDF $pdf, array $data): void {
        $grid = $data['grid'];
        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Peak Booking Times (approved bookings, count per hour)', 0, 1);
        $pdf->Ln(2);

        $colWidth = 9.5;
        $labelWidth = 18;

        // Header row: hours 0-23
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($labelWidth, 6, 'Day', 1, 0, 'C', true);
        for ($h = 0; $h < 24; $h++) {
            $pdf->Cell($colWidth, 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Find max for color scaling
        $max = 1;
        foreach ($grid as $row) {
            foreach ($row as $v) { if ($v > $max) $max = $v; }
        }

        $pdf->SetFont('helvetica', '', 6);
        for ($d = 0; $d < 7; $d++) {
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell($labelWidth, 6, $days[$d], 1, 0, 'C', true);
            for ($h = 0; $h < 24; $h++) {
                $count = $grid[$d][$h];
                if ($count === 0) {
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->SetTextColor(200, 200, 200);
                } else {
                    $intensity = $count / $max;
                    $pdf->SetFillColor(255, (int)(247 - $intensity * 100), (int)(200 - $intensity * 130));
                    $pdf->SetTextColor(50, 50, 50);
                }
                $pdf->Cell($colWidth, 6, $count ?: '·', 1, 0, 'C', true);
            }
            $pdf->Ln();
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 6, 'Numbers represent the count of approved bookings overlapping each hour.', 0, 1);
    }

    private static function renderEquipment(TCPDF $pdf, array $data): void {
        $header = ['Equipment', 'Total Quantity Requested', 'Number of Bookings', 'Marked Unavailable'];
        $widths = [80, 50, 50, 50];

        self::tableHeader($pdf, $header, $widths);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['rows'] as $r) {
            $row = [
                Booking::EQUIPMENT_OPTIONS[$r['equipment_name']] ?? $r['equipment_name'],
                (string)$r['total_qty'],
                (string)$r['booking_count'],
                (string)$r['unavailable_count'],
            ];
            self::tableRow($pdf, $row, $widths);
        }

        if (empty($data['rows'])) {
            $pdf->Cell(0, 8, 'No equipment requests found for the selected filters.', 0, 1);
        }
    }

    private static function renderOverrides(TCPDF $pdf, array $data): void {
        $header = ['Event', 'Auditorium', 'Requested By', 'Date', 'Time', 'Approved By', 'Override Reason'];
        $widths = [45, 30, 30, 25, 30, 30, 80];

        self::tableHeader($pdf, $header, $widths);

        $pdf->SetFont('helvetica', '', 8);
        foreach ($data['rows'] as $r) {
            $row = [
                self::truncate($r['event_name'], 30),
                self::truncate($r['auditorium_name'], 20),
                self::truncate($r['user_name'], 20),
                date('d M Y', strtotime($r['start_datetime'])),
                date('g:i A', strtotime($r['start_datetime'])) . '-' . date('g:i A', strtotime($r['end_datetime'])),
                self::truncate($r['override_by_name'] ?? '—', 20),
                self::truncate($r['override_reason'] ?? '—', 80),
            ];
            self::tableRow($pdf, $row, $widths);
        }

        if (empty($data['rows'])) {
            $pdf->Cell(0, 8, 'No conflict overrides found for the selected date range.', 0, 1);
        }
    }

    private static function renderNotifications(TCPDF $pdf, array $data): void {
        $header = ['Event Type', 'Total', 'Sent', 'Failed'];
        $widths = [100, 40, 40, 40];

        self::tableHeader($pdf, $header, $widths);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['rows'] as $r) {
            $row = [
                ucwords(str_replace('_', ' ', $r['trigger_event'])),
                (string)$r['total'],
                (string)$r['sent'],
                (string)$r['failed'],
            ];
            self::tableRow($pdf, $row, $widths);
        }

        if (empty($data['rows'])) {
            $pdf->Cell(0, 8, 'No notification activity found for the selected date range.', 0, 1);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────

    private static function tableHeader(TCPDF $pdf, array $headers, array $widths): void {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(30, 58, 95);
        $pdf->SetTextColor(255, 255, 255);
        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, $h, 1, 0, 'L', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
    }

    private static function tableRow(TCPDF $pdf, array $cells, array $widths, bool $fill = false): void {
        static $alt = false;
        $pdf->SetFillColor($alt ? 245 : 255, $alt ? 245 : 255, $alt ? 245 : 255);
        foreach ($cells as $i => $c) {
            $pdf->Cell($widths[$i], 6, $c, 1, 0, 'L', true);
        }
        $pdf->Ln();
        $alt = !$alt;
    }

    private static function truncate(string $text, int $len): string {
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
    }

    private static function reportTitle(string $report): string {
        return match ($report) {
            'summary'       => 'Booking Summary Report',
            'utilization'   => 'Auditorium Utilization Report',
            'heatmap'       => 'Peak Booking Times Report',
            'equipment'     => 'Equipment Usage Report',
            'overrides'     => 'Admin Override Log',
            'notifications' => 'Notification Delivery Report',
            default         => 'Report',
        };
    }

    private static function dateRangeLabel(array $filters): string {
        return date('d M Y', strtotime($filters['from'])) . ' – ' . date('d M Y', strtotime($filters['to']));
    }
}
