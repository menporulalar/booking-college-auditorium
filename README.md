# booking-college-auditorium

Now the views — login page and forgot password:Now zip it all up:Module 1 is complete. Here's everything delivered:

**Files inside the zip:**

| File | What it does |
|---|---|
| `database.sql` | Run once — creates all tables, seeds 3 auditoriums + admin account |
| `config/database.php` | PDO singleton, update DB credentials here |
| `config/app.php` | All constants — session timeout, lockout limits, SMTP, OTP expiry |
| `app/Models/User.php` | bcrypt verify, lockout counter, OTP create/verify/consume |
| `app/Helpers/Auth.php` | Session, CSRF tokens, role checks, auto-logout on inactivity |
| `app/Helpers/Mailer.php` | PHPMailer wrapper + branded HTML email template |
| `app/Controllers/AuthController.php` | Login, logout, 3-step forgot-password flow |
| `public/index.php` | Front controller + router |
| `views/auth/login.php` | Login UI — mobile responsive, show/hide password |
| `views/auth/forgot-password.php` | 3-step UI — email → OTP entry → new password + strength meter |
| `views/errors/403.php` + `404.php` | Error pages |
| `.htaccess` | URL rewriting + security headers + blocks `.sql`/`.env` access |
| `README.md` | Setup instructions, default credentials, security summary |

**Quick setup:**
1. Run `composer install`
2. Update `config/database.php` with your DB credentials
3. Run `mysql < database.sql`
4. Update SMTP config in `config/app.php`
5. Set `APP_URL` to your local path

Default logins: `admin@college.edu` / `Admin@1234` and `staff@college.edu` / `Admin@1234` — change both immediately.

