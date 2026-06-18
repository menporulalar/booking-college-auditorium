<?php

// Application
define('APP_NAME', 'Auditorium Booking');
$appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8081');
define('APP_URL', $appUrl);
define('APP_ENV', 'development'); // 'production' in prod

// Session
define('SESSION_TIMEOUT', 3600);       // 60 minutes inactivity
define('SESSION_NAME', 'abs_session');

// Auth
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);
define('OTP_EXPIRY_MINUTES', 15);
define('MIN_PASSWORD_LENGTH', 8);

// Mail (PHPMailer SMTP — update with real values)
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'your-email@college.edu');
define('MAIL_PASSWORD',   'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@college.edu');
define('MAIL_FROM_NAME',  'Auditorium Booking System');
define('MAIL_ENCRYPTION', 'tls');

// Notifications — admin recipient (defaults to MAIL_USERNAME if not set)
define('ADMIN_NOTIFY_EMAIL', 'admin@college.edu');
