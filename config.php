<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'emploitic_connect');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Email configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'killerbidk@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your_app_password');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'killerbidk@gmail.com');
define('SMTP_FROM_NAME', 'Emploitic Connect');

// Site configuration
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/EMPLOITIC_CONNECT_2026');
define('SITE_NAME', 'Emploitic Connect 2026');

// Security
define('ENCRYPTION_KEY', 'killerbidk0810');
define('TOKEN_EXPIRY', 24 * 60 * 60); // 24 hours

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Enable in production with HTTPS
session_start();

// Timezone
date_default_timezone_set('Africa/Algiers');
?>