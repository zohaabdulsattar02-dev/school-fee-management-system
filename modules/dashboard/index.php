<?php
session_start();
$page_title = "Dashboard";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$total_students = 0;
$total_families = 0;
$today_collection = 0;
$month_collection = 0;
$total_collection = 0;
$month_expenses = 0;
$total_expenses = 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE status='Active'");
if ($res) $total_students = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM families WHERE status='Active'");
if ($res) $total_families = mysqli_fetch_assoc($res)['total'] ?? 0;

$today = date('Y-m-d');
$res = mysqli_query($conn, "SELECT COALESCE(SUM(paid_amount),0) as total FROM fee_payments WHERE payment_date='$today'");
if ($res) $today_collection = mysqli_fetch_assoc($res)['total'] ?? 0;

$month_start = date('Y-m-01');
$res = mysqli_query($conn, "SELECT COALESCE(SUM(paid_amount),0) as total FROM fee_payments WHERE payment_date >= '$month_start'");
if ($res) $month_collection = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COALESCE(SUM(paid_amount),0) as total FROM fee_payments");
if ($res) $total_collection = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE expense_date >= '$month_start'");
if ($res) $month_expenses = mysqli_fetch_assoc($res)['total'] ?? 0;

$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM expenses");
if ($res) $total_expenses = mysqli_fetch_assoc($res)['total'] ?? 0;

$remaining_balance = $total_collection - $total_expenses;
?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="label">Total Students</div>
        <div class="value"><?php echo number_format($total_students); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Total Families</div>
        <div class="value"><?php echo number_format($total_families); ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="label">Today's Collection</div>
        <div class="value">Rs. <?php echo number_format($today_collection); ?></div>
    </div>
    <div class="kpi-card green">
        <div class="label">This Month Collection</div>
        <div class="value">Rs. <?php echo number_format($month_collection); ?></div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="label">Total Collection</div>
        <div class="value">Rs. <?php echo number_format($total_collection); ?></div>
    </div>
    <div class="kpi-card red">
        <div class="label">This Month Expenses</div>
        <div class="value">Rs. <?php echo number_format($month_expenses); ?></div>
    </div>
    <div class="kpi-card red">
        <div class="label">Total Expenses</div>
        <div class="value">Rs. <?php echo number_format($total_expenses); ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Net Balance</div>
        <div class="value" style="color:var(--primary);">Rs. <?php echo number_format($remaining_balance); ?></div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header"><strong>Quick Actions</strong></div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="../families/add.php" class="btn btn-outline" style="justify-content:flex-start;">Add Family</a>
                <a href="../students/add.php" class="btn btn-outline" style="justify-content:flex-start;">Add Student</a>
                <a href="../fees/collection.php" class="btn btn-primary" style="justify-content:flex-start;">Collect Fee</a>
                <a href="../fees/additional_charges.php" class="btn btn-outline" style="justify-content:flex-start;">Additional Charges</a>
                <a href="../fees/payment_history.php" class="btn btn-outline" style="justify-content:flex-start;">Payment History</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <strong>Recent Payments</strong>
            <a href="../fees/payment_history.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Date</th>
                            <th>Family</th>
                            <th>Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rq = mysqli_query($conn, "SELECT fp.*, f.family_code, f.father_name 
                            FROM fee_payments fp 
                            LEFT JOIN families f ON fp.family_id=f.id 
                            ORDER BY fp.id DESC LIMIT 5");
                        if ($rq && mysqli_num_rows($rq)>0):
                            while ($row = mysqli_fetch_assoc($rq)):
                        ?>
                        <tr>
                            <td><a href="../fees/receipt.php?id=<?php echo $row['id']; ?>" style="color:var(--primary); text-decoration:none; font-weight:600;">
                                <?php echo htmlspecialchars($row['receipt_no']); ?>
                            </a></td>
                            <td><?php echo date('d-M-Y', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars(($row['family_code'] ?? '') . ' ' . ($row['father_name'] ?? '')); ?></td>
                            <td><strong class="text-success">Rs. <?php echo number_format($row['paid_amount'], 0); ?></strong></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:20px;">No payments yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>