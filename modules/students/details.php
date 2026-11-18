<?php
session_start();
$page_title = "Student Details";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id === 0) {
    echo "<div class='card'><div class='card-body'>Invalid Student ID</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

$query = "SELECT s.*, f.family_code, f.father_name as family_father, f.contact, c.class_name
          FROM students s
          LEFT JOIN families f ON s.family_id = f.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE s.id = $student_id LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<div class='card'><div class='card-body'>Student not found</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

$student = mysqli_fetch_assoc($result);
$payable = $student['monthly_fee'] - $student['discount'];

// Payment history (through payment_details)
$history = [];
$hq = mysqli_query($conn, "SELECT pd.amount_paid, fp.receipt_no, fp.payment_date, fp.payment_method, fp.id as payment_id
    FROM payment_details pd
    JOIN fee_payments fp ON pd.payment_id = fp.id
    WHERE pd.student_id = $student_id
    ORDER BY fp.payment_date DESC, fp.id DESC");
if ($hq) while ($r = mysqli_fetch_assoc($hq)) $history[] = $r;

// Extra charges history
$charges = [];
$cq = mysqli_query($conn, "SELECT * FROM student_charges WHERE student_id=$student_id ORDER BY id DESC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $charges[] = $r;
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <div class="d-flex gap-2">
        <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
        <a href="../fees/collection.php?search=<?php echo urlencode($student['family_code']); ?>" class="btn btn-primary btn-sm">Collect Fee</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <div>
            <strong style="font-size:18px;">
                <?php echo htmlspecialchars($student['student_code']); ?> — 
                <?php echo htmlspecialchars($student['student_name']); ?>
            </strong>
        </div>
        <span class="badge <?php echo $student['status']==='Active'?'badge-success':'badge-secondary'; ?>">
            <?php echo htmlspecialchars($student['status']); ?>
        </span>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:18px;">
            <div><div class="text-muted" style="font-size:12px;">Father</div>
                <strong><?php echo htmlspecialchars($student['father_name'] ?? $student['family_father'] ?? '-'); ?></strong></div>
            <div><div class="text-muted" style="font-size:12px;">Class</div>
                <strong><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></strong></div>
            <div><div class="text-muted" style="font-size:12px;">Family</div>
                <strong><a href="../families/details.php?id=<?php echo $student['family_id']; ?>" style="color:var(--primary);text-decoration:none;">
                    <?php echo htmlspecialchars($student['family_code'] ?? '-'); ?>
                </a></strong></div>
            <div><div class="text-muted" style="font-size:12px;">Monthly Payable</div>
                <strong style="color:var(--primary);">Rs. <?php echo number_format($payable, 0); ?></strong></div>
        </div>
    </div>
</div>

<!-- Fee Payment History -->
<div class="card mb-4">
    <div class="card-header"><strong>Fee Payment History</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history)>0): foreach ($history as $h): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($h['receipt_no']); ?></strong></td>
                        <td><?php echo date('d-M-Y', strtotime($h['payment_date'])); ?></td>
                        <td><strong class="text-success">Rs. <?php echo number_format($h['amount_paid'], 0); ?></strong></td>
                        <td><?php echo htmlspecialchars($h['payment_method']); ?></td>
                        <td>
                            <a href="../fees/receipt.php?id=<?php echo $h['payment_id']; ?>" class="btn btn-sm btn-outline">Receipt</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-secondary);">No payment history yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Extra Charges -->
<div class="card">
    <div class="card-header"><strong>Additional Charges</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Charge</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($charges)>0): foreach ($charges as $ch): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ch['charge_name']); ?></td>
                        <td>Rs. <?php echo number_format($ch['amount'], 0); ?></td>
                        <td><?php echo htmlspecialchars($ch['frequency']); ?></td>
                        <td>
                            <?php if ($ch['status']==='Paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php elseif ($ch['status']==='Waived'): ?>
                                <span class="badge badge-secondary">Waived</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-secondary);">No additional charges</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>