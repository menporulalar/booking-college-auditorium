# How to Run from htdocs (XAMPP)

Use these steps if you want to run the **College Auditorium Booking System** under XAMPP’s Apache instead of PHP’s built-in server.

## Prerequisites

- XAMPP installed with PHP 8.1+, MySQL, and Apache
- Composer installed (for dependency installs, if needed)

## 1. Copy the project into htdocs

Place the project folder inside XAMPP’s `htdocs` directory:

```bash
# macOS / Linux default
cp -R booking-college-auditorium /opt/lampp/htdocs/

# Windows default
# Copy the folder to C:\xampp\htdocs\
```

After copying, your project URL will typically be:
- `http://localhost/booking-college-auditorium/public`
- Or `http://localhost/auditorium-booking/public` if you renamed the folder

## 2. Enable URL rewriting

Apache must allow `.htaccess` overrides. Edit your XAMPP `httpd.conf`:

- **macOS/Linux:** `/opt/lampp/etc/httpd.conf`
- **Windows:** `C:\xampp\apache\conf\httpd.conf`

Find the `<Directory "/opt/lampp/htdocs">` block and change:

```apache
AllowOverride None
```
to:
```apache
AllowOverride All
```

Restart Apache from the XAMPP control panel after saving.

## 3. Start MySQL from XAMPP

Start MySQL and Apache in the XAMPP control panel before proceeding.

## 4. Create the database

**Option A: phpMyAdmin**
1. Open `http://localhost/phpmyadmin`
2. Create a new database named `auditorium_booking`
3. Select the database, go to the **Import** tab, and upload `database.sql`

**Option B: Command line**
```bash
/opt/lampp/bin/mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS auditorium_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root -p auditorium_booking < /opt/lampp/htdocs/booking-college-auditorium/database.sql
```

## 5. Configure the database connection

Edit `config/database.php` inside your project folder with your XAMPP MySQL credentials:

```php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'auditorium_booking',
    'username' => 'root',
    'password' => '',
    'options'  => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
];
```

Adjust `username` and `password` if your XAMPP MySQL user differs.

## 6. Install PHP dependencies

From the project’s `public` folder:

```bash
cd /opt/lampp/htdocs/booking-college-auditorium
composer install --no-dev
```

If Composer is not installed, install it first or make sure `vendor/` is present from the repository.

## 7. Open the app

Visit the project URL in your browser:
- `http://localhost/booking-college-auditorium/public/login`
- Or use the folder name you chose in step 1

## 8. Log in with test accounts

| Email                | Password  | Role   |
|----------------------|-----------|--------|
| admin@college.edu    | Admin@1234| admin  |
| staff@college.edu    | Staff@1234| staff  |

## Troubleshooting

- **Redirects to `/login` with 404:** Make sure `.htaccess` is being read (check `AllowOverride All` in `httpd.conf`) and Apache has been restarted.
- **Database connection error:** Verify MySQL is running in XAMPP and `config/database.php` credentials are correct.
- **Missing dependencies:** Run `composer install --no-dev` again if the `vendor/` folder is incomplete.
- **Login fails after multiple tries:** If you used the default `database.sql`, `staff@college.edu` is seeded with the correct password. If you edited the database manually, reset the password hash via PHP’s `password_hash('Staff@1234', PASSWORD_BCRYPT)`.
