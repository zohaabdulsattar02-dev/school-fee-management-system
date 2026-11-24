<?php
// Database Connection - Standard Version
// Local par config.local.php se, Live par usi file se alag values aayengi

$config = require __DIR__ . '/../config.local.php';

$host = $config['db_host'];
$user = $config['db_user'];
$pass = $config['db_pass'];
$dbname = $config['db_name'];

$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection - Standard: Detail hacker ko mat do, sirf log karo!
if (!$conn) {
    error_log("DB Connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>