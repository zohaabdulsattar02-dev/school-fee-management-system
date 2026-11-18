<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/images/logo.png" alt="Logo">
        <div class="sidebar-brand-text">
            <h2>Great Chancellors School</h2>
            <p>Fee Management</p>
        </div>
    </div>

    <nav class="sidebar-menu">
        <a href="../dashboard/index.php" class="<?php echo ($current_dir == 'dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="../families/index.php" class="<?php echo ($current_dir == 'families') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Families
        </a>
        <a href="../students/index.php" class="<?php echo ($current_dir == 'students') ? 'active' : ''; ?>">
            <i class="bi bi-person"></i> Students
        </a>
        <a href="../fees/collection.php" class="<?php echo ($current_dir == 'fees') ? 'active' : ''; ?>">
            <i class="bi bi-cash-coin"></i> Fees
        </a>
<a href="../fees/fee_structure.php">Fee Structure</a>
<a href="../fees/additional_charges.php">Additional Charges</a>
        <a href="../expenses/index.php" class="<?php echo ($current_dir == 'expenses') ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i> Expenses
        </a>
        <a href="../reports/index.php" class="<?php echo ($current_dir == 'reports') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> Reports
        </a>
<a href="../examinations/index.php" class="<?php echo ($current_dir == 'examinations') ? 'active' : ''; ?>">
    <i class="bi bi-mortarboard"></i> Examination & Results
</a>
        <a href="../auth/users.php" class="<?php echo ($current_dir == 'auth' && $current_page == 'users.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-gear"></i> Users
        </a>
        <a href="../settings/index.php" class="<?php echo ($current_dir == 'settings') ? 'active' : ''; ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a href="../auth/logout.php" style="margin-top: 20px; color: #f87171;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</aside>