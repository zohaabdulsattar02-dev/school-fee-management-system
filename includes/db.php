<?php
// Database Connection

$host = "localhost";
$user = "root";
$pass = "";                  // XAMPP default password empty hota hai
$dbname = "school_fee";

$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>

