<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Staff';
$user_role = $_SESSION['user_role'] ?? 'Staff';
$initials  = strtoupper(substr($user_name, 0, 1));
$header_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($header_title); ?> | Great Chancellors School - Staff Only</title>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1><?php echo htmlspecialchars($header_title); ?></h1>
        </div>
        <div class="topbar-right">
            <div class="academic-year">
                <i class="bi bi-calendar3"></i> 2026-27
            </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($user_name); ?></div>
                    <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($user_role); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content Start -->
    <div class="page-content">