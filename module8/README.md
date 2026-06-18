# Module 8 — Calendar Export (iCal)

## What's included

| File | Purpose |
|---|---|
| `app/Helpers/ICalExport.php` | RFC 5545 compliant .ics generator — single event, multiple events, line folding, escaping, Google Calendar deep link |
| `app/Controllers/BookingController.php` | Updated — `downloadIcal()` (single approved booking), `downloadSeriesIcal()` (all approved occurrences in a series) |
| `app/Controllers/CalendarExportController.php` | Admin-side export — filter by hall/date range, preview table, download |
| `views/admin/calendar-export.php` | Admin export UI — filters, preview, download, import instructions |
| `views/staff/booking-detail.php` | Updated — "Download .ics", "Add to Google Calendar", and "Download Entire Series" buttons (shown only for approved bookings) |
| `views/layouts/admin-header.php` | New "Calendar Export" sidebar link |
| `public/index.php` | Router updated with iCal routes |

No new database migration needed.

---

## Staff features (`/bookings/{id}`)

For **approved** bookings only:
- **Download .ics** → `GET /bookings/{id}/ical` — single VEVENT with 30-min reminder alarm
- **Add to Google Calendar** → opens Google Calendar's "add event" deep link in a new tab (pre-filled with title, time, location, description)
- **Download Entire Series (.ics)** → `GET /bookings/series/{groupId}/ical` — only if part of a recurring series; bundles all *approved* occurrences the user owns into one calendar file

---

## Admin features (`/admin/calendar-export`)

- Filter by auditorium (or all) and date range
- **Preview** button shows a table of matching approved bookings before downloading
- **Download .ics** → `GET /admin/calendar-export/download` — bundles all matching approved bookings into one file
- Import instructions shown for Outlook, Apple Calendar, and Google Calendar

---

## ICS file details

Each VEVENT includes:
- `UID` (uses the booking's `ical_uid`, generated at creation in Module 3)
- `DTSTART` / `DTEND` — converted from `Asia/Kolkata` to UTC (`Z` format) per RFC 5545
- `SUMMARY`, `LOCATION` (hall name), `DESCRIPTION` (event description, attendee count, special requirements, booking ID, status)
- `ORGANIZER` — the staff member who booked
- `STATUS` — mapped from booking status (`approved` → `CONFIRMED`, `pending*` → `TENTATIVE`, `rejected`/`cancelled` → `CANCELLED`)
- `VALARM` — 30-minute-before reminder, only for approved bookings
- All text fields properly escaped (commas, semicolons, backslashes, newlines) and long lines folded at 75 octets per spec

---

## Routes added

| Route | Method | Access | Purpose |
|---|---|---|---|
| `/bookings/{id}/ical` | GET | Staff (owner) | Download single approved booking |
| `/bookings/series/{groupId}/ical` | GET | Staff (owner) | Download all approved occurrences in series |
| `/admin/calendar-export` | GET | Admin | Export UI with filters + preview |
| `/admin/calendar-export/download` | GET | Admin | Download filtered .ics |

---

## Still pending (future modules)
- Module 10: Reports & analytics (utilization, equipment usage, override log)
