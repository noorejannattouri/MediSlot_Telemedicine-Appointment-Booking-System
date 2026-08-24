<?php
/**
 * MediSlot - Database Configuration
 * Place this file in the project root (e.g. htdocs/medislot/config.php)
 */

// -------------------------------------------------
// Database credentials (XAMPP default)
// -------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // empty password is default in XAMPP
define('DB_NAME', 'medislot');  // change if you used a different database name

// -------------------------------------------------
// Create connection
// -------------------------------------------------
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 (supports all characters including emojis)
$conn->set_charset("utf8mb4");

// Optional: make error reporting stricter (good for development)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// -------------------------------------------------
// Timezone (Bangladesh)
// -------------------------------------------------
date_default_timezone_set('Asia/Dhaka');

// -------------------------------------------------
// Google OAuth Credentials
// -------------------------------------------------
define('GOOGLE_CLIENT_ID', '36306467463-s4hej78m0k857nurl48j941em3o1spk4.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/medislot/google_callback.php');