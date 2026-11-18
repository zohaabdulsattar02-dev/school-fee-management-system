<?php
session_start();

// Agar already login hai to dashboard pe bhej do
if (isset($_SESSION['user_id'])) {
    header("Location: modules/dashboard/index.php");
    exit();
}

// Agar login nahi hai to login page pe bhej do
header("Location: modules/auth/login.php");
exit();
?>