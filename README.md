# Module 10 — Reports & Analytics

## What's included

| File | Purpose |
|---|---|
| `database_m10.sql` | Adds performance indexes for report queries |
| `app/Models/Report.php` | All 6 analytics queries: booking summary, utilization rate, peak-time heatmap, equipment usage, override log, notification stats |
| `app/Controllers/ReportController.php` | Dashboard view + PDF/Excel export dispatch |
| `app/Helpers/ReportPdfExport.php` | TCPDF-based PDF generator for all 6 report types |
| `app/Helpers/ReportExcelExport.php` | PhpSpreadsheet .xlsx generator for all 6 report types |
| `views/admin/reports/index.php` | Report UI — 6 tabs, filter bar, quick presets, stat cards, tables, heatmap grid |

---

## Setup

```bash
mysql -u root -p auditorium_booking < database_m10.sql
composer install   # ensures tcpdf + phpspreadsheet are installed
```

---

## 6 Report types

| Report | Tab | Data | Export |
|---|---|---|---|
| Booking Summary | Summary | All bookings with stats (total, approved hrs, conflicts) | PDF, Excel |
| Utilization Rate | Utilization | Per-hall % utilization, booked vs available hrs, bar visual | PDF, Excel |
| Peak Booking Times | Peak Times | 7×24 heatmap of booking frequency by day/hour | PDF, Excel |
| Equipment Usage | Equipment Usage | Per-item quantity, booking count, unavailability count | PDF, Excel |
| Admin Override Log | Override Log | All conflict overrides with approver and reason | PDF, Excel |
| Notification Delivery | Notification Log | Per-event type counts and success rates | PDF, Excel |

---

## Filters

- **Date range** — from / to (defaults to current month)
- **Auditorium** — filter by specific hall (Summary, Utilization, Peak Times, Equipment)
- **Status** — booking status filter (Summary only)
- **Quick presets** — This Month / Last Month / This Year buttons

---

## Utilization calculation

```
Utilization % = (approved booked hours / total available hours) × 100

Total available hours = (operational_end - operational_start) × days_in_range - blackout_day_hours
```

Blackout dates (from `blackout_dates` table) are subtracted from available hours automatically.

---

## Routes added

| Route | Purpose |
|---|---|
| `GET /admin/reports` | Report dashboard (tab + filters → rendered table/chart) |
| `GET /admin/reports/export/pdf` | Stream PDF export for active report + filters |
| `GET /admin/reports/export/excel` | Stream .xlsx export for active report + filters |

---

## Completed system — all modules now built

| # | Module | Status |
|---|---|---|
| 1 | Auth (login, OTP, RBAC) | ✅ |
| 2 | Auditorium management + blackout dates | ✅ |
| 3 | Availability calendar + booking form | ✅ |
| 4 | Recurrence (daily/weekly/monthly/custom) | ✅ (in M3) |
| 5 | Conflict detection | ✅ (in M3) |
| 6 | Admin approval workflow + override | ✅ |
| 8 | iCal / Google Calendar export | ✅ |
| 9 | Email notifications (11 triggers + cron) | ✅ |
| 10 | Reports & analytics + PDF/Excel export | ✅ |
