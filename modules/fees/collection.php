<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Fee Collection";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$family = null;
$children = [];
$pending_charges = [];
$message = "";
$error = "";

// Search Family
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = trim($_GET['search']);
    $like = "%" . $search . "%";

    // Standard: Prepared Statement for search
    $stmt = mysqli_prepare($conn, "SELECT * FROM families WHERE family_code LIKE ? OR father_name LIKE ? OR id IN (SELECT family_id FROM students WHERE student_name LIKE ?) LIMIT 1");
    mysqli_stmt_bind_param($stmt, "sss", $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $family = mysqli_fetch_assoc($result);

        // Active children
        $stmt2 = mysqli_prepare($conn, "SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.family_id = ? AND s.status = 'Active' ORDER BY s.id ASC");
        mysqli_stmt_bind_param($stmt2, "i", $family['id']);
        mysqli_stmt_execute($stmt2);
        $child_result = mysqli_stmt_get_result($stmt2);
        if ($child_result) {
            while ($row = mysqli_fetch_assoc($child_result)) {
                $children[] = $row;
            }
        }

        // Pending additional charges for these students
        if (count($children) > 0) {
            $ids = array_map(function($c){ return intval($c['id']); }, $children);
            $id_list = implode(',', $ids);
            $cq = mysqli_query($conn, "SELECT * FROM student_charges WHERE student_id IN ($id_list) AND status='Pending' ORDER BY id ASC");
            if ($cq) {
                while ($r = mysqli_fetch_assoc($cq)) {
                    $pending_charges[$r['student_id']][] = $r;
                }
            }
        }
    } else {
        $error = "No family found for: " . htmlspecialchars($search);
    }
}

// Save Payment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_payment'])) {
    $family_id      = intval($_POST['family_id']);
    $paid_amount    = floatval($_POST['paid_amount']);
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $remarks        = trim($_POST['remarks'] ?? '');
    $payment_date   = date('Y-m-d');
    $received_by    = $_SESSION['user_id'];

    $selected_students = $_POST['student_id'] ?? [];
    $selected_charges  = $_POST['charge_id'] ?? [];

    if (empty($selected_students) && empty($selected_charges)) {
        $error = "Please select at least one student or charge.";
    } else {
        // Calculate totals
        $total_payable = 0;

        foreach ($selected_students as $sid) {
            $sid = intval($sid);
            $stmt = mysqli_prepare($conn, "SELECT monthly_fee, discount FROM students WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $sid);
            mysqli_stmt_execute($stmt);
            $s_res = mysqli_stmt_get_result($stmt);
            if ($s_res && $s_row = mysqli_fetch_assoc($s_res)) {
                $total_payable += ($s_row['monthly_fee'] - $s_row['discount']);
            }
        }

        foreach ($selected_charges as $chid) {
            $chid = intval($chid);
            $stmt = mysqli_prepare($conn, "SELECT amount FROM student_charges WHERE id = ? AND status='Pending'");
            mysqli_stmt_bind_param($stmt, "i", $chid);
            mysqli_stmt_execute($stmt);
            $c_res = mysqli_stmt_get_result($stmt);
            if ($c_res && $c_row = mysqli_fetch_assoc($c_res)) {
                $total_payable += floatval($c_row['amount']);
            }
        }

        $remaining = max(0, $total_payable - $paid_amount);

        // Receipt number
        $receipt_result = mysqli_query($conn, "SELECT receipt_no FROM fee_payments ORDER BY id DESC LIMIT 1");
        if ($receipt_result && mysqli_num_rows($receipt_result) > 0) {
            $last = mysqli_fetch_assoc($receipt_result);
            $last_num = intval(substr($last['receipt_no'], -5));
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        $receipt_no = 'REC-' . date('Y') . '-' . str_pad($new_num, 5, '0', STR_PAD_LEFT);

        // Standard: Prepared Statement for payment insert
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO fee_payments (receipt_no, family_id, payment_date, total_amount, paid_amount, remaining_amount, payment_method, received_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "sisdddsis", $receipt_no, $family_id, $payment_date, $total_payable, $paid_amount, $remaining, $payment_method, $received_by, $remarks);

        if (mysqli_stmt_execute($insert_stmt)) {
            $payment_id = mysqli_insert_id($conn);

            // Payment details - monthly fee part
            foreach ($selected_students as $sid) {
                $sid = intval($sid);
                $stmt = mysqli_prepare($conn, "SELECT monthly_fee, discount FROM students WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $sid);
                mysqli_stmt_execute($stmt);
                $s_res = mysqli_stmt_get_result($stmt);
                if ($s_res && $s_row = mysqli_fetch_assoc($s_res)) {
                    $amount = $s_row['monthly_fee'] - $s_row['discount'];
                    $dstmt = mysqli_prepare($conn, "INSERT INTO payment_details (payment_id, student_id, amount_paid) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($dstmt, "iid", $payment_id, $sid, $amount);
                    mysqli_stmt_execute($dstmt);
                }
            }

            // Mark selected additional charges as Paid
            foreach ($selected_charges as $chid) {
                $chid = intval($chid);
                $ustmt = mysqli_prepare($conn, "UPDATE student_charges SET status='Paid', payment_id=? WHERE id=? AND status='Pending'");
                mysqli_stmt_bind_param($ustmt, "ii", $payment_id, $chid);
                mysqli_stmt_execute($ustmt);
            }

            header("Location: receipt.php?id=" . $payment_id);
            exit;
        } else {
            $error = "Payment failed. Please try again.";
        }
    }
}
?>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="margin-bottom:0;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search Family ID / Student Name / Father Name" 
                   style="min-width:340px;"
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Search Family
            </button>
            <div style="margin-left:auto; color:var(--text-secondary); font-size:13px;">
                Monthly Fee + Extra Charges · One Family = One Bill
            </div>
        </form>
    </div>
</div>

<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($family): ?>

<div class="card mb-4">
    <div class="card-header">
        <div>
            <strong style="font-size:17px;">
                <?php echo htmlspecialchars($family['family_code']); ?> — 
                <?php echo htmlspecialchars($family['father_name']); ?> Family
            </strong>
            <div class="text-muted" style="font-size:13px; margin-top:3px;">
                Contact: <?php echo htmlspecialchars($family['contact'] ?? '-'); ?>
            </div>
        </div>
        <span class="badge badge-success"><?php echo count($children); ?> Active Children</span>
    </div>

    <?php if (count($children) > 0): ?>
    <form method="POST">
        <input type="hidden" name="family_id" value="<?php echo (int)$family['id']; ?>">

        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAllStudents" checked onclick="toggleStudents(this)"></th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Monthly Fee</th>
                            <th>Discount</th>
                            <th>Extra Charges (Pending)</th>
                            <th>Student Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_monthly = 0;
                        $grand_extra = 0;
                        foreach ($children as $child): 
                            $payable_fee = $child['monthly_fee'] - $child['discount'];
                            $grand_monthly += $payable_fee;
                            $extras = $pending_charges[$child['id']] ?? [];
                            $extra_sum = 0;
                            foreach ($extras as $ex) $extra_sum += floatval($ex['amount']);
                            $grand_extra += $extra_sum;
                            $student_total = $payable_fee + $extra_sum;
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="student_id[]" value="<?php echo (int)$child['id']; ?>" 
                                       class="student-check" checked>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($child['student_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($child['student_code']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($child['class_name'] ?? '-'); ?></td>
                            <td>Rs. <?php echo number_format($child['monthly_fee'], 0); ?></td>
                            <td>Rs. <?php echo number_format($child['discount'], 0); ?></td>
                            <td>
                                <?php if (count($extras) > 0): ?>
                                    <?php foreach ($extras as $ex): ?>
                                        <div style="margin-bottom:6px;">
                                            <label style="display:flex; gap:6px; align-items:center; font-size:13px;">
                                                <input type="checkbox" name="charge_id[]" value="<?php echo (int)$ex['id']; ?>" 
                                                       class="charge-check" checked>
                                                <?php echo htmlspecialchars($ex['charge_name']); ?>
                                                <strong>Rs. <?php echo number_format($ex['amount'], 0); ?></strong>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><strong>Rs. <?php echo number_format($student_total, 0); ?></strong></td>
                        </tr>
                        <?php endforeach; 
                        $grand_total = $grand_monthly + $grand_extra;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary + Payment -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; padding:20px; border-top:1px solid var(--border);">
            <div>
                <div style="margin-bottom:10px;">
                    <span class="text-muted">Total Monthly (selected approx)</span><br>
                    <strong>Rs. <?php echo number_format($grand_monthly, 0); ?></strong>
                </div>
                <div style="margin-bottom:10px;">
                    <span class="text-muted">Total Extra Charges</span><br>
                    <strong>Rs. <?php echo number_format($grand_extra, 0); ?></strong>
                </div>
                <div style="margin-top:14px;">
                    <span class="text-muted">Grand Total Payable</span><br>
                    <strong style="font-size:26px; color:var(--primary);">
                        Rs. <?php echo number_format($grand_total, 0); ?>
                    </strong>
                </div>
                <div class="text-muted" style="font-size:13px; margin-top:8px;">
                    Date: <strong><?php echo date('d-M-Y'); ?></strong> (Automatic)
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Amount Received *</label>
                    <input type="number" name="paid_amount" class="form-control" 
                           value="<?php echo $grand_total; ?>" required step="0.01"
                           style="font-size:18px; font-weight:700;">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank</option>
                        <option value="Online">Online</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional note...">
                </div>
                <div style="display:flex; gap:12px;">
                    <button type="submit" name="save_payment" class="btn btn-primary" style="flex:1;">
                        <i class="bi bi-check-lg"></i> Save Payment
                    </button>
                    <a href="collection.php" class="btn btn-outline">Cancel</a>
                </div>
            </div>
        </div>
    </form>
    <?php else: ?>
        <div class="card-body"><p class="text-muted">No active children in this family.</p></div>
    <?php endif; ?>
</div>

<script>
function toggleStudents(source) {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = source.checked);
}
</script>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>