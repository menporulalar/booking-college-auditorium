# Module 3 — Availability Calendar & Booking Engine

## What's included

| File | Purpose |
|---|---|
| `database_m3.sql` | Run after M2 — creates `bookings`, `recurrence_groups`, `equipment_requests` tables |
| `app/Models/Booking.php` | CRUD, conflict detection, recurrence generation (daily/weekly/monthly/custom), calendar JSON formatting |
| `app/Controllers/BookingController.php` | Calendar page, booking form, my bookings, cancel, AJAX conflict check, FullCalendar JSON API |
| `views/layouts/staff-header.php` / `staff-footer.php` | Staff sidebar layout (Dashboard, Calendar, New Booking, My Bookings) |
| `views/staff/calendar.php` | FullCalendar.js month/week/agenda view with hall filter, color-coded statuses, blackout overlay |
| `views/staff/booking-form.php` | Booking form — date/time pickers, recurrence options, equipment checklist, live AJAX conflict check, summary panel |
| `views/staff/my-bookings.php` | Paginated bookings list with status tabs and cancel action |
| `views/staff/booking-detail.php` | Full booking detail, status banner, cancel single/series actions |
| `views/staff/dashboard.php` | Updated dashboard with stats and upcoming approved bookings |
| `views/admin/bookings-stub.php` | Placeholder for admin bookings nav links (full build in Module 6) |
| `public/index.php` | Router updated with calendar, booking, and API routes |

---

## Setup

```bash
mysql -u root -p auditorium_booking < database_m3.sql
```

No new Composer packages — FullCalendar.js is loaded via CDN.

---

## Key features

### Availability calendar (`/calendar`)
- FullCalendar month / week / agenda views (agenda on mobile by default)
- Color legend: Pending (amber), Conflict (red), Approved (green), Blackout (grey background)
- Hall filter dropdown — refetches events via `/api/calendar-events`
- Click an event → modal with details
- Click an empty date → pre-fills the new booking form with that date

### Booking form (`/bookings/new`)
- Auditorium dropdown shows capacity & operational hours
- Live capacity warning if attendees exceed hall capacity
- **AJAX conflict check** — debounced call to `/api/check-conflict` shows overlapping bookings without blocking submission
- **Recurrence**: Daily / Weekly / Monthly (with interval) or Custom comma-separated dates
- Each recurrence pattern dynamically updates the UI (interval unit label, custom date field)
- Equipment checklist with quantity inputs
- Sticky summary panel reflects form state live

### Conflict handling
- `Booking::hasConflict()` checks for overlapping `start_datetime`/`end_datetime` on the same auditorium, excluding cancelled/rejected
- If conflict found → booking status = `pending_conflict` (flagged for admin, handled in Module 6)
- Staff are informed a conflict *may* exist but can still submit — final decision is admin's

### Recurring bookings
- `Booking::generateOccurrences()` produces all occurrence datetimes
- Each occurrence = independent row in `bookings`, linked via `recurrence_group_id`
- Blackout dates are checked **per occurrence** — blacked-out dates are skipped silently
- Staff can cancel a single occurrence or the entire series (only pending/conflict ones)

### My Bookings (`/bookings`)
- Status filter tabs (All / Pending / Conflict Review / Approved / Rejected / Cancelled)
- Pagination (15 per page)
- Series badge for recurring bookings

---

## API endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/calendar-events?start=&end=&hall=` | GET | Returns FullCalendar-formatted JSON (bookings + blackout overlays) |
| `/api/check-conflict?auditorium_id=&start=&end=&exclude=` | GET | Returns `{conflict: bool, conflicts: [...]}` |

---

## Still pending (future modules)
- Module 6: Admin approval workflow (approve/reject, conflict resolution, override)
- Module 8: iCal export (`.ics` download button is wired in the UI but route not yet built)
- Module 9: Email notifications on booking events
