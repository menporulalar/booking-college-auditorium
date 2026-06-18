<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Models/Booking.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * ReportExcelExport — generates .xlsx reports using PhpSpreadsheet.
 * Streams output directly (headers must already be set by caller).
 */
class ReportExcelExport {

    private const HEADER_FILL = '1E3A5F';

    public static function generate(string $report, array $data, array $filters): void {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        match ($report) {
            'summary'       => self::summary($sheet, $data, $filters),
            'utilization'   => self::utilization($sheet, $data, $filters),
            'heatmap'       => self::heatmap($sheet, $data, $filters),
            'equipment'     => self::equipment($sheet, $data, $filters),
            'overrides'     => self::overrides($sheet, $data, $filters),
            'notifications' => self::notifications($sheet, $data, $filters),
            default         => $sheet->setCellValue('A1', 'No data'),
        };

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // ── Report sheets ────────────────────────────────────────────

    private static function summary($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Booking Summary Report', $filters);

        $stats = $data['stats'];
        $sheet->setCellValue('A3', 'Total Bookings');     $sheet->setCellValue('B3', $stats['total']);
        $sheet->setCellValue('A4', 'Approved');            $sheet->setCellValue('B4', $stats['approved']);
        $sheet->setCellValue('A5', 'Pending');             $sheet->setCellValue('B5', $stats['pending']);
        $sheet->setCellValue('A6', 'Rejected');            $sheet->setCellValue('B6', $stats['rejected']);
        $sheet->setCellValue('A7', 'Cancelled');           $sheet->setCellValue('B7', $stats['cancelled']);
        $sheet->setCellValue('A8', 'Conflicts Flagged');   $sheet->setCellValue('B8', $stats['conflict']);
        $sheet->setCellValue('A9', 'Total Approved Hours'); $sheet->setCellValue('B9', round($stats['total_hours'], 1));
        self::boldRange($sheet, 'A3:A9');

        $row = 11;
        $headers = ['Event', 'Auditorium', 'Requested By', 'Department', 'Date', 'Start', 'End', 'Status', 'Attendees', 'Recurring?'];
        self::headerRow($sheet, $row, $headers);

        $row++;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$row}", $r['event_name']);
            $sheet->setCellValue("B{$row}", $r['auditorium_name']);
            $sheet->setCellValue("C{$row}", $r['user_name']);
            $sheet->setCellValue("D{$row}", $r['user_department'] ?? '—');
            $sheet->setCellValue("E{$row}", date('Y-m-d', strtotime($r['start_datetime'])));
            $sheet->setCellValue("F{$row}", date('H:i', strtotime($r['start_datetime'])));
            $sheet->setCellValue("G{$row}", date('H:i', strtotime($r['end_datetime'])));
            $sheet->setCellValue("H{$row}", Booking::STATUSES[$r['status']]['label'] ?? $r['status']);
            $sheet->setCellValue("I{$row}", $r['attendee_count'] ?? '');
            $sheet->setCellValue("J{$row}", $r['recurrence_group_id'] ? 'Yes' : 'No');
            $row++;
        }

        self::autoSize($sheet, 'A', 'J');
    }

    private static function utilization($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Auditorium Utilization Report', $filters);

        $row = 3;
        $headers = ['Auditorium', 'Capacity', 'Available Hours', 'Booked Hours', 'Bookings', 'Utilization %'];
        self::headerRow($sheet, $row, $headers);

        $row++;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$row}", $r['auditorium_name']);
            $sheet->setCellValue("B{$row}", $r['capacity']);
            $sheet->setCellValue("C{$row}", $r['available_hours']);
            $sheet->setCellValue("D{$row}", $r['booked_hours']);
            $sheet->setCellValue("E{$row}", $r['booking_count']);
            $sheet->setCellValue("F{$row}", $r['utilization_pct'] . '%');
            $row++;
        }

        self::autoSize($sheet, 'A', 'F');
    }

    private static function heatmap($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Peak Booking Times Report', $filters);

        $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        $grid = $data['grid'];

        $row = 3;
        $sheet->setCellValue("A{$row}", 'Day');
        for ($h = 0; $h < 24; $h++) {
            $col = self::colLetter($h + 1);
            $sheet->setCellValue("{$col}{$row}", $h . ':00');
        }
        self::styleHeaderRow($sheet, $row, 25);

        for ($d = 0; $d < 7; $d++) {
            $row++;
            $sheet->setCellValue("A{$row}", $days[$d]);
            for ($h = 0; $h < 24; $h++) {
                $col = self::colLetter($h + 1);
                $sheet->setCellValue("{$col}{$row}", $grid[$d][$h]);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(10);
        for ($h = 0; $h < 24; $h++) {
            $sheet->getColumnDimension(self::colLetter($h + 1))->setWidth(5);
        }
    }

    private static function equipment($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Equipment Usage Report', $filters);

        $row = 3;
        $headers = ['Equipment', 'Total Quantity Requested', 'Number of Bookings', 'Marked Unavailable'];
        self::headerRow($sheet, $row, $headers);

        $row++;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$row}", Booking::EQUIPMENT_OPTIONS[$r['equipment_name']] ?? $r['equipment_name']);
            $sheet->setCellValue("B{$row}", $r['total_qty']);
            $sheet->setCellValue("C{$row}", $r['booking_count']);
            $sheet->setCellValue("D{$row}", $r['unavailable_count']);
            $row++;
        }

        self::autoSize($sheet, 'A', 'D');
    }

    private static function overrides($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Admin Override Log', $filters);

        $row = 3;
        $headers = ['Event', 'Auditorium', 'Requested By', 'Date', 'Start', 'End', 'Approved By', 'Override Reason'];
        self::headerRow($sheet, $row, $headers);

        $row++;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$row}", $r['event_name']);
            $sheet->setCellValue("B{$row}", $r['auditorium_name']);
            $sheet->setCellValue("C{$row}", $r['user_name']);
            $sheet->setCellValue("D{$row}", date('Y-m-d', strtotime($r['start_datetime'])));
            $sheet->setCellValue("E{$row}", date('H:i', strtotime($r['start_datetime'])));
            $sheet->setCellValue("F{$row}", date('H:i', strtotime($r['end_datetime'])));
            $sheet->setCellValue("G{$row}", $r['override_by_name'] ?? '—');
            $sheet->setCellValue("H{$row}", $r['override_reason'] ?? '—');
            $row++;
        }

        self::autoSize($sheet, 'A', 'H');
    }

    private static function notifications($sheet, array $data, array $filters): void {
        self::titleBlock($sheet, 'Notification Delivery Report', $filters);

        $row = 3;
        $headers = ['Event Type', 'Total', 'Sent', 'Failed'];
        self::headerRow($sheet, $row, $headers);

        $row++;
        foreach ($data['rows'] as $r) {
            $sheet->setCellValue("A{$row}", ucwords(str_replace('_', ' ', $r['trigger_event'])));
            $sheet->setCellValue("B{$row}", $r['total']);
            $sheet->setCellValue("C{$row}", $r['sent']);
            $sheet->setCellValue("D{$row}", $r['failed']);
            $row++;
        }

        self::autoSize($sheet, 'A', 'D');
    }

    // ── Styling helpers ────────────────────────────────────────

    private static function titleBlock($sheet, string $title, array $filters): void {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'Date range: ' . date('d M Y', strtotime($filters['from'])) . ' – ' . date('d M Y', strtotime($filters['to'])));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
    }

    private static function headerRow($sheet, int $row, array $headers): void {
        foreach ($headers as $i => $h) {
            $col = self::colLetter($i);
            $sheet->setCellValue("{$col}{$row}", $h);
        }
        self::styleHeaderRow($sheet, $row, count($headers));
    }

    private static function styleHeaderRow($sheet, int $row, int $colCount): void {
        $range = "A{$row}:" . self::colLetter($colCount - 1) . $row;
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::HEADER_FILL);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private static function boldRange($sheet, string $range): void {
        $sheet->getStyle($range)->getFont()->setBold(true);
    }

    private static function autoSize($sheet, string $startCol, string $endCol): void {
        $start = ord($startCol);
        $end   = ord($endCol);
        for ($c = $start; $c <= $end; $c++) {
            $sheet->getColumnDimension(chr($c))->setAutoSize(true);
        }
    }

    private static function colLetter(int $index): string {
        // 0-indexed -> A, B, C, ...
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }
}
