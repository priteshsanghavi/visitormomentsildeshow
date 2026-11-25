
<?php

// ========================================
// DATABASE CONNECTION
// VISITOR PHOTO SLIDESHOW
// ========================================

$host = "localhost";
$username = "root";
$password = "";
$database = "visitor_slideshow";

// Create Connection
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Character Encoding
$conn->set_charset("utf8mb4");

?>
