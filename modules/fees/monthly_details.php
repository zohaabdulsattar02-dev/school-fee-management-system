<?php
session_start();
$page_title = "Monthly Fee Details";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

// Filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Fetch all active students with family & class
$records = [];

$query = "SELECT s.*, f.family_code, f.father_name, c.class_name
          FROM students s
          LEFT JOIN families f ON s.family_id = f.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE s.status = 'Active'";

if (!empty($search)) {
    $query .= " AND (s.student_name LIKE '%$search%' 
                OR s.student_code LIKE '%$search%' 
                OR f.family_code LIKE '%$search%' 
                OR f.father_name LIKE '%$search%')";
}

$query .= " ORDER BY f.family_code ASC, s.id ASC";

$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
}

// Totals
$total_fee = 0;
$total_discount = 0;
$total_payable = 0;

foreach ($records as $r) {
    $total_fee += $r['monthly_fee'];
    $total_discount += $r['discount'];
    $total_payable += ($r['monthly_fee'] - $r['discount']);
}
?>

<!-- Page Header -->
<div class="d-flex justify-between align-center mb-4">
    <div>
        <h2 style="font-size: 20px; margin-bottom: 4px;">Monthly Fee Details — <?php echo date('F Y'); ?></h2>
        <div class="text-muted">Academic Year: 2026-27</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; width:100%;">
        <input type="text" name="search" class="form-control" 
               placeholder="Search Family / Student / Father Name" 
               style="min-width:260px;"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="status" class="form-control">
            <option value="">All Status</option>
            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
        </select>

        <button type="submit" class="btn btn-outline">
            <i class="bi bi-funnel"></i> Filter
        </button>

        <a href="collection.php" class="btn btn-primary" style="margin-left:auto;">
            <i class="bi bi-cash-coin"></i> Collect Fee
        </a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Family</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Monthly Fee</th>
                        <th>Discount</th>
                        <th>Payable</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): 
                            $payable = $row['monthly_fee'] - $row['discount'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['family_code']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($row['father_name']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['student_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($row['student_code']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['class_name'] ?? '-'); ?></td>
                            <td>Rs. <?php echo number_format($row['monthly_fee'], 0); ?></td>
                            <td>Rs. <?php echo number_format($row['discount'], 0); ?></td>
                            <td><strong>Rs. <?php echo number_format($payable, 0); ?></strong></td>
                            <td>
                                <div class="d-flex gap-2">
 grossa href="../families/details.php?id=<?php echo $row['family_id']; ?>" class="btn btn-sm btn-outline">
                                        View Family
                                    </a>
                                    <a href="collection.php?search=<?php echo urlencode($row['family_code']); ?>" class="btn btn-sm btn-primary">
                                        Collect
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; gap:40px; background:#FAFBFC;">
            <div>
                <span class="text-muted">Total Students:</span>
                <strong> <?php echo count($records); ?></strong>
            </div>
            <div>
                <span class="text-muted">Monthly Fee Total:</span>
                <strong> Rs. <?php echo number_format($total_fee, 0); ?></strong>
            </div>
            <div>
                <span class="text-muted">Discount Total:</span>
                <strong> Rs. <?php echo number_format($total_discount, 0); ?></strong>
            </div>
            <div>
                <span class="text-muted">Payable Total:</span>
                <strong style="color:var(--primary); font-size:16px;"> Rs. <?php echo number_format($total_payable, 0); ?></strong>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>