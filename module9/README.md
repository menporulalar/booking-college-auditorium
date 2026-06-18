# Module 9 — Email Notifications

## What's included

| File | Purpose |
|---|---|
| `database_m9.sql` | Run after M3 — creates `notification_log`, `notification_settings` (seeded with 11 toggleable events), adds `reminder_sent` column to `bookings` |
| `app/Models/NotificationSetting.php` | Per-event enable/disable toggles, cached lookup |
| `app/Models/NotificationLog.php` | Records every send attempt (success/failure), stats, filterable history |
| `app/Helpers/NotificationService.php` | Builds all 11 email templates, checks settings, sends via `Mailer`, logs result |
| `app/Controllers/NotificationController.php` | Settings page, toggle save, test-email sender |
| `views/admin/notifications/index.php` | Settings UI + delivery log with filters + stats + test SMTP button |
| `cron/send-reminders.php` | Standalone script for 24-hour-before reminders — run via cron |
| `app/Controllers/BookingController.php` | Updated — triggers `booking_submitted`, `booking_conflict`, `booking_cancelled` |
| `app/Controllers/AdminBookingController.php` | Updated — triggers `booking_approved`, `booking_approved_override`, `booking_rejected`, `booking_alternate`, `series_approved`, `series_rejected`, `equipment_unavailable` |

---

## Setup

```bash
mysql -u root -p auditorium_booking < database_m9.sql
```

No new Composer packages — uses the PHPMailer wrapper from Module 1.

### Cron setup (reminders)
```bash
# Run hourly
0 * * * * php /path/to/auditorium-booking/cron/send-reminders.php >> /path/to/logs/reminders.log 2>&1
```

---

## All 11 email triggers

| Event | Trigger | Recipient |
|---|---|---|
| `booking_submitted` | Staff submits a new booking | Admin |
| `booking_conflict` | New booking overlaps an existing one | Admin |
| `booking_approved` | Admin approves (no conflict) | Staff |
| `booking_approved_override` | Admin approves despite conflict | Staff |
| `booking_rejected` | Admin rejects | Staff |
| `booking_alternate` | Admin suggests alternate time | Staff |
| `booking_reminder` | 24hrs before approved event (cron) | Staff |
| `booking_cancelled` | Staff cancels their booking | Admin |
| `equipment_unavailable` | Admin marks equipment unavailable | Staff |
| `series_approved` | Admin approves entire recurring series | Staff |
| `series_rejected` | Admin rejects entire recurring series | Staff |

Each is individually toggleable from `/admin/notifications`. If disabled, the email is skipped silently (not logged as failed).

---

## Admin Notifications page (`/admin/notifications`)

- **Stats**: total sent, successful, failed, sent today
- **Delivery log**: filterable by event type, status, date range — shows recipient, subject, status, timestamp
- **Email triggers**: checkbox list to enable/disable each of the 11 events
- **Test SMTP**: sends a test email to the logged-in admin's own address to verify `config/app.php` SMTP settings

---

## Email template design

All emails share a consistent branded wrapper (`Mailer::wrapTemplate`) with:
- Navy header with app name
- White content card with rounded corners
- Light grey footer with "automated message" disclaimer

`NotificationService` builds the body content for each event using shared helpers:
- `intro()` — opening sentence
- `table()` — key/value details table (event, hall, date, time, etc.)
- `button()` — call-to-action linking back to the relevant page

---

## Configuration reminder

SMTP settings live in `config/app.php` (from Module 1):
```php
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'your-email@college.edu');
define('MAIL_PASSWORD',   'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@college.edu');
define('MAIL_FROM_NAME',  'Auditorium Booking System');
```

Optionally add to `config/app.php` to direct admin notifications to a specific inbox (defaults to `MAIL_USERNAME` if not set):
```php
define('ADMIN_NOTIFY_EMAIL', 'auditorium-admin@college.edu');
```

---

## Still pending (future modules)
- Module 8: iCal export
- Module 10: Reports & analytics (notification delivery report is partially covered here)
