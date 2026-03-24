<?php
// Database config
define('DB_HOST', 'sql307.infinityfree.com');
define('DB_USER', 'if0_39931409');
define('DB_PASS', 'MoTtgPkWeiE'); 
define('DB_NAME', 'if0_39931409_exam_portal');

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