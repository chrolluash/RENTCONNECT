<?php
// functions/config.php - Database Configuration (FIXED)

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change this to your database username
define('DB_PASS', ''); // Change this to your database password
define('DB_NAME', 'rentconnect2'); // Change this to your database name

// Optional: Set timezone
date_default_timezone_set('Asia/Manila');

// IMPORTANT: Disable error display in production, enable only for debugging
// Comment out these lines in production:
error_reporting(E_ALL);
ini_set('display_errors', 0); // Changed to 0 - errors will go to error log instead
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log'); // Log errors to file

// Session configuration
ini_set('session.cookie_httponly', 1); // Changed to 1 for security
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Don't output anything from this file
?>
