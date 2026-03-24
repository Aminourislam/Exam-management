<?php
// Database config
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', ''); 
define('DB_NAME', '');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');
?>
