<?php
session_start();
$page_title = "Add Expense";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";

// Fetch categories
$categories = [];
$cat_q = mysqli_query($conn, "SELECT * FROM expense_categories WHERE status = 1 ORDER BY category_name");
if ($cat_q) {
    while ($row = mysqli_fetch_assoc($cat_q)) {
        $categories[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_id    = intval($_POST['category_id']);
    $expense_date   = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $description    = mysqli_real_escape_string($conn, trim($_POST['description']));
    $amount         = floatval($_POST['amount']);
    $paid_to        = mysqli_real_escape_string($conn, trim($_POST['paid_to']));
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $remarks        = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    $created_by     = $_SESSION['user_id'] ?? 1;

    // Auto Expense Code
    $code_q = mysqli_query($conn, "SELECT expense_code FROM expenses ORDER BY id DESC LIMIT 1");
    if ($code_q && mysqli_num_rows($code_q) > 0) {
        $last = mysqli_fetch_assoc($code_q);
        $last_num = intval(str_replace('EXP-', '', $last['expense_code']));
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    $expense_code = 'EXP-' . str_pad($new_num, 3, '0', STR_PAD_LEFT);

    $query = "INSERT INTO expenses 
        (expense_code, category_id, expense_date, description, amount, paid_to, payment_method, remarks, created_by)
        VALUES 
        ('$expense_code', $category_id, '$expense_date', '$description', $amount, '$paid_to', '$payment_method', '$remarks', $created_by)";

    if (mysqli_query($conn, $query)) {
        $success = "Expense added successfully! Code: <strong>$expense_code</strong>";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $success; ?>
        <a href="index.php" style="margin-left:15px; color:var(--primary); font-weight:600;">View All</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Add New Expense</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Enter description">
                </div>

                <div class="form-group">
                    <label>Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" required step="0.01" placeholder="e.g. 5000">
                </div>

                <div class="form-group">
                    <label>Paid To</label>
                    <input type="text" name="paid_to" class="form-control" placeholder="Person / Company name">
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
                    <input type="text" name="remarks" class="form-control" placeholder="Optional">
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Expense
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>