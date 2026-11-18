<?php
session_start();
$page_title = "Expense Management";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$msg = "";

// Delete Expense
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM expenses WHERE id=$id");
    $msg = "Expense deleted successfully.";
}

// Fetch expenses
$expenses = [];
$query = "SELECT e.*, ec.category_name, u.name as created_by_name
          FROM expenses e
          LEFT JOIN expense_categories ec ON e.category_id = ec.id
          LEFT JOIN users u ON e.created_by = u.id
          ORDER BY e.expense_date DESC, e.id DESC";

$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $expenses[] = $row;
    }
}

$total_expenses = 0;
foreach ($expenses as $exp) {
    $total_expenses += $exp['amount'];
}
?>

<?php if ($msg): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="filter-bar">
    <input type="date" class="form-control" style="min-width:150px;">
    <input type="date" class="form-control" style="min-width:150px;">

    <select class="form-control">
        <option value="">All Categories</option>
        <?php
        $cat_q = mysqli_query($conn, "SELECT * FROM expense_categories WHERE status = 1 ORDER BY category_name");
        if ($cat_q) {
            while ($cat = mysqli_fetch_assoc($cat_q)) {
                echo '<option value="'.$cat['id'].'">'.htmlspecialchars($cat['category_name']).'</option>';
            }
        }
        ?>
    </select>

    <a href="add.php" class="btn btn-primary" style="margin-left:auto;">
        <i class="bi bi-plus-lg"></i> Add Expense
    </a>
</div>

<!-- Expense Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Expense Code</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Paid To</th>
                        <th>Method</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($expenses) > 0): ?>
                        <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($exp['expense_code'] ?? '-'); ?></strong></td>
                            <td><?php echo date('d-M-Y', strtotime($exp['expense_date'])); ?></td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?php echo htmlspecialchars($exp['category_name'] ?? '-'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($exp['description'] ?? '-'); ?></td>
                            <td>
                                <strong class="text-danger">
                                    Rs. <?php echo number_format($exp['amount'], 0); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($exp['paid_to'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($exp['payment_method'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($exp['created_by_name'] ?? '-'); ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit.php?id=<?php echo $exp['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?delete=<?php echo $exp['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                       onclick="return confirm('Delete this expense?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No expenses found. 
                                <a href="add.php" style="color:var(--primary);">Add first expense</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Total -->
        <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; background:#FAFBFC;">
            <div>
                <span class="text-muted">Total Expenses:</span>
                <strong style="font-size:18px; color:var(--danger); margin-left:10px;">
                    Rs. <?php echo number_format($total_expenses, 0); ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>