# College Auditorium Booking System

A PHP + MySQL web app for booking, managing, and reporting on college auditorium usage.

## Tech stack

- PHP 8.1+
- MySQL
- Composer dependencies: PHPMailer, TCPDF, PhpSpreadsheet

## Local development setup

1. Clone this repository.
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Import the schema and seed data (all parts are required):
   ```bash
   mysql -u root -p auditorium_booking < database.sql
   mysql -u root -p auditorium_booking < database_m2.sql
   mysql -u root -p auditorium_booking < database_m3.sql
   mysql -u root -p auditorium_booking < database_m9.sql
   mysql -u root -p auditorium_booking < database_m10.sql
   ```
4. Adjust app settings as needed in `config/app.php` and `config/database.php`.
5. Start the built-in PHP server:
   ```bash
   php -S localhost:8000 -t public
   ```
6. Open `http://localhost:8000`.

## App URLs

- Staff login/booking: `/login`, `/dashboard`, `/bookings/new`, `/calendar`
- Admin area: `/admin` (requires admin/superadmin login)
- Reports: `/admin/reports`

## Notes for developers

- Port 8000 may already be in use on some machines; `php -S localhost:8081 -t public` works as an alternate.
- Entry point is `public/index.php`.
- Views are in `views/`; shared layout files are in `views/layouts/`.
- Report exports require TCPDF and PhpSpreadsheet. Keep Composer lock/`vendor/` in sync via `composer install --no-dev`.

## Test accounts

- `admin@college.edu` — `Admin@1234`
- `staff@college.edu` — `Staff@1234`
