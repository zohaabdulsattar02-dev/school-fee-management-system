<?php
session_start();
$page_title = "Edit Expense";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";

if ($expense_id === 0) {
    echo "<div class='card'><div class='card-body'>Invalid Expense ID</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch expense
$expense = null;
$res = mysqli_query($conn, "SELECT * FROM expenses WHERE id = $expense_id LIMIT 1");
if ($res && mysqli_num_rows($res) > 0) {
    $expense = mysqli_fetch_assoc($res);
} else {
    echo "<div class='card'><div class='card-body'>Expense not found</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch categories
$categories = [];
$cat_q = mysqli_query($conn, "SELECT * FROM expense_categories WHERE status = 1 ORDER BY category_name");
if ($cat_q) {
    while ($row = mysqli_fetch_assoc($cat_q)) {
        $categories[] = $row;
    }
}

// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $category_id    = intval($_POST['category_id']);
    $expense_date   = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $description    = mysqli_real_escape_string($conn, trim($_POST['description']));
    $amount         = floatval($_POST['amount']);
    $paid_to        = mysqli_real_escape_string($conn, trim($_POST['paid_to']));
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $remarks        = mysqli_real_escape_string($conn, trim($_POST['remarks']));

    $query = "UPDATE expenses SET 
                category_id = $category_id,
                expense_date = '$expense_date',
                description = '$description',
                amount = $amount,
                paid_to = '$paid_to',
                payment_method = '$payment_method',
                remarks = '$remarks'
              WHERE id = $expense_id";

    if (mysqli_query($conn, $query)) {
        $success = "Expense updated successfully! Code: <strong>$expense[expense_code]</strong>";
        $res = mysqli_query($conn, "SELECT * FROM expenses WHERE id = $expense_id LIMIT 1");
        $expense = mysqli_fetch_assoc($res);
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
        <strong>Edit Expense</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="expense_date" class="form-control" value="<?php echo $expense['expense_date']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $expense['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Enter description" value="<?php echo htmlspecialchars($expense['description'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" required step="0.01" placeholder="e.g. 5000" value="<?php echo $expense['amount']; ?>">
                </div>

                <div class="form-group">
                    <label>Paid To</label>
                    <input type="text" name="paid_to" class="form-control" placeholder="Person / Company name" value="<?php echo htmlspecialchars($expense['paid_to'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash" <?php echo ($expense['payment_method'] ?? '') === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="Bank" <?php echo ($expense['payment_method'] ?? '') === 'Bank' ? 'selected' : ''; ?>>Bank</option>
                        <option value="Online" <?php echo ($expense['payment_method'] ?? '') === 'Online' ? 'selected' : ''; ?>>Online</option>
                        <option value="Other" <?php echo ($expense['payment_method'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional" value="<?php echo htmlspecialchars($expense['remarks'] ?? ''); ?>">
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