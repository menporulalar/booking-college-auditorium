# Module 1 — Authentication

## What's included

| File | Purpose |
|---|---|
| `config/database.php` | PDO connection singleton |
| `config/app.php` | App constants (session timeout, lockout, OTP expiry, SMTP) |
| `database.sql` | Run once — creates tables + seeds 3 auditoriums + admin account |
| `app/Models/User.php` | User fetch, bcrypt verify, lockout logic, OTP create/verify |
| `app/Helpers/Auth.php` | Session start, login, logout, CSRF, role checks |
| `app/Helpers/Mailer.php` | PHPMailer SMTP wrapper + OTP email template |
| `app/Controllers/AuthController.php` | Login, logout, 3-step forgot-password flow |
| `public/index.php` | Front controller + simple router |
| `views/auth/login.php` | Login page UI |
| `views/auth/forgot-password.php` | 3-step reset UI (email → OTP → new password) |
| `.htaccess` | URL rewriting + security headers |

---

## Setup

### 1. Install dependencies
```bash
composer install
```

### 2. Configure database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'auditorium_booking');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
```

### 3. Run migrations
```bash
mysql -u root -p < database.sql
```

### 4. Configure email (SMTP)
Edit `config/app.php`:
```php
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_USERNAME', 'your-email@college.edu');
define('MAIL_PASSWORD', 'your-app-password');
```
For Gmail: create an **App Password** in Google Account → Security.

### 5. Set APP_URL
Edit `config/app.php`:
```php
define('APP_URL', 'http://localhost/auditorium-booking/public');
```

### 6. Point Apache/Nginx document root
Apache: point to `/auditorium-booking/` (not `/public/`)
The `.htaccess` handles routing to `public/index.php`.

---

## Default accounts

| Role  | Email               | Password   |
|-------|---------------------|------------|
| Admin | admin@college.edu   | Admin@1234 |
| Staff | staff@college.edu   | Admin@1234 |

**Change both passwords immediately after setup.**

---

## Security features
- bcrypt (cost 12) password hashing
- 5 failed login → 15-min account lockout
- CSRF token on every form
- HttpOnly + SameSite session cookies
- Generic error messages (no account enumeration)
- OTP valid for 15 minutes, single-use, hashed in DB
- Role-based route protection (403 on unauthorized access)
- `.htaccess` blocks direct access to `.sql`, `.env`, `.json` files
