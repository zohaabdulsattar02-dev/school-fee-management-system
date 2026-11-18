<?php
session_start();
$page_title = "Reports";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$msg = "";
$error = "";

// Delete Fee Payment
if (isset($_GET['delete_payment'])) {
    $pid = intval($_GET['delete_payment']);

    // pehle payment_details delete
    mysqli_query($conn, "DELETE FROM payment_details WHERE payment_id = $pid");
    // phir fee_payments delete
    if (mysqli_query($conn, "DELETE FROM fee_payments WHERE id = $pid")) {
        $msg = "Fee payment deleted successfully!";
    } else {
        $error = "Failed to delete payment.";
    }
}

// Quick Stats
$total_collection = 0;
$total_expenses = 0;
$total_students = 0;
$total_families = 0;

$res = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_payments");
if ($res) $total_collection = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses");
if ($res) $total_expenses = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM families WHERE status='Active'");
if ($res) $total_families = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE status='Active'");
if ($res) $total_students = mysqli_fetch_assoc($res)['total'] ?? 0;

$balance = $total_collection - $total_expenses;

// Today collection
$today = date('Y-m-d');
$today_collection = 0;
$res = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_payments WHERE payment_date = '$today'");
if ($res) $today_collection = mysqli_fetch_assoc($res)['total'] ?? 0;

// This month collection
$month_start = date('Y-m-01');
$month_collection = 0;
$res = mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fee_payments WHERE payment_date >= '$month_start'");
if ($res) $month_collection = mysqli_fetch_assoc($res)['total'] ?? 0;
?>

<?php if ($msg): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="kpi-grid" style="margin-bottom:24px;">
    <div class="kpi-card green">
        <div class="label">Total Collection</div>
        <div class="value">Rs. <?php echo number_format($total_collection, 0); ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="label">This Month Collection</div>
        <div class="value">Rs. <?php echo number_format($month_collection, 0); ?></div>
    </div>
    <div class="kpi-card red">
        <div class="label">Total Expenses</div>
        <div class="value">Rs. <?php echo number_format($total_expenses, 0); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Net Balance</div>
        <div class="value" style="color:var(--primary);">Rs. <?php echo number_format($balance, 0); ?></div>
    </div>
</div>

<div class="kpi-grid" style="margin-bottom:24px;">
    <div class="kpi-card">
        <div class="label">Today's Collection</div>
        <div class="value">Rs. <?php echo number_format($today_collection, 0); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Active Families</div>
        <div class="value"><?php echo number_format($total_families); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Active Students</div>
        <div class="value"><?php echo number_format($total_students); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Total Payments</div>
        <div class="value">
            <?php
            $pc = mysqli_query($conn, "SELECT COUNT(*) as total FROM fee_payments");
            echo $pc ? number_format(mysqli_fetch_assoc($pc)['total']) : 0;
            ?>
        </div>
    </div>
</div>

<!-- Report Links -->
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px;">
    <div class="card">
        <div class="card-header">
            <strong><i class="bi bi-cash-coin" style="color:var(--primary);"></i> Fee Reports</strong>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="../fees/payment_history.php" class="btn btn-outline" style="justify-content:flex-start;">Payment History</a>
                <a href="../fees/monthly_details.php" class="btn btn-outline" style="justify-content:flex-start;">Monthly Fee Details</a>
                <a href="../fees/collection.php" class="btn btn-outline" style="justify-content:flex-start;">Fee Collection</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong><i class="bi bi-wallet2" style="color:var(--danger);"></i> Expense Reports</strong>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="../expenses/index.php" class="btn btn-outline" style="justify-content:flex-start;">All Expenses</a>
                <a href="../expenses/add.php" class="btn btn-outline" style="justify-content:flex-start;">Add Expense</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong><i class="bi bi-people" style="color:var(--success);"></i> Other Reports</strong>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <a href="../families/index.php" class="btn btn-outline" style="justify-content:flex-start;">Family List</a>
                <a href="../students/index.php" class="btn btn-outline" style="justify-content:flex-start;">Student List</a>
                <a href="../auth/users.php" class="btn btn-outline" style="justify-content:flex-start;">Users List</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Fee Payments with Delete -->
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <strong>Recent Fee Payments</strong>
        <a href="../fees/payment_history.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Date</th>
                        <th>Family</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = mysqli_query($conn, "SELECT fp.*, f.family_code, f.father_name 
                                                   FROM fee_payments fp 
                                                   LEFT JOIN families f ON fp.family_id = f.id 
                                                   ORDER BY fp.id DESC LIMIT 10");
                    if ($recent && mysqli_num_rows($recent) > 0):
                        while ($row = mysqli_fetch_assoc($recent)):
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['receipt_no']); ?></strong></td>
                            <td><?php echo date('d-M-Y', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(($row['family_code'] ?? '-') . ' - ' . ($row['father_name'] ?? '-')); ?></td>
                            <td><strong class="text-success">Rs. <?php echo number_format($row['paid_amount'], 0); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="../fees/receipt.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline">
                                        Receipt
                                    </a>
                                    <a href="?delete_payment=<?php echo $row['id']; ?>"
                                       class="btn btn-sm btn-outline"
                                       style="color:var(--danger);"
                                       onclick="return confirm('Delete this fee payment permanently?')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px; color:var(--text-secondary);">
                                No payments yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>