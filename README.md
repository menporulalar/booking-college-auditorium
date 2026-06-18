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
3. Import the base schema:
   ```bash
   mysql -u root -p auditorium_booking < database.sql
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

- Entry point is `public/index.php`.
- Views are in `views/`; shared layout files are in `views/layouts/`.
- Report exports require TCPDF and PhpSpreadsheet. Keep Composer lock/`vendor/` in sync via `composer install --no-dev`.
