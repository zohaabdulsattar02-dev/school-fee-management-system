<?php
session_start();
$page_title = "Additional Charges";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "DELETE FROM additional_charges WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $success = "Charge deleted!";
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE additional_charges SET status = IF(status=1,0,1) WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $success = "Status updated!";
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$freq_filter = isset($_GET['frequency']) ? trim($_GET['frequency']) : '';

$sql = "SELECT ac.*, c.class_name 
        FROM additional_charges ac
        LEFT JOIN classes c ON ac.class_id = c.id
        WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND ac.charge_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}
if ($freq_filter !== '') {
    $sql .= " AND ac.frequency = ?";
    $params[] = $freq_filter;
    $types .= "s";
}
$sql .= " ORDER BY ac.id DESC";

$charges = [];
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$q = mysqli_stmt_get_result($stmt);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $charges[] = $r;
    }
}
?>

<?php if ($success): ?>
<div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:16px;">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:16px;">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Filter + ONE Add button -->
<div class="filter-bar">
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
        <input type="text" name="search" class="form-control" 
               placeholder="Search charge name..." 
               style="min-width:220px;"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="frequency" class="form-control">
            <option value="">All Frequency</option>
            <?php foreach (['One Time', 'Monthly', 'Yearly', 'Custom'] as $f): ?>
                <option value="<?php echo htmlspecialchars($f); ?>" <?php echo $freq_filter === $f ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($f); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-outline">
            <i class="bi bi-funnel"></i> Filter
        </button>

        <a href="fee_structure.php" class="btn btn-outline">
            Fee Structure
        </a>

        <a href="fee_components.php" class="btn btn-outline">
            Fee Components
        </a>

        <a href="additional_charge_form.php" class="btn btn-primary" style="margin-left:auto;">
            <i class="bi bi-plus-lg"></i> Add New Charge
        </a>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <strong>Additional Charges List</strong>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Charge Name</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Applicable To</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($charges) > 0): ?>
                        <?php foreach ($charges as $ch): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($ch['charge_name']); ?></strong>
                                <?php if (!empty($ch['description'])): ?>
                                    <div class="text-muted" style="font-size:12px;">
                                        <?php echo htmlspecialchars($ch['description']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Rs. <?php echo number_format($ch['amount'], 0); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($ch['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($ch['applicable_type']); ?></td>
                            <td>
                                <?php
                                if ($ch['applicable_type'] === 'School') {
                                    echo 'Entire School';
                                } elseif ($ch['applicable_type'] === 'Class') {
                                    echo htmlspecialchars($ch['class_name'] ?? 'Class');
                                } elseif ($ch['applicable_type'] === 'Student') {
                                    echo 'Student #' . intval($ch['student_id']);
                                } elseif ($ch['applicable_type'] === 'Family') {
                                    echo 'Family #' . intval($ch['family_id']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ((int)$ch['status'] === 1): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="additional_charge_form.php?id=<?php echo (int)$ch['id']; ?>" 
                                       class="btn btn-sm btn-outline">
                                        Edit
                                    </a>
                                    <a href="?toggle=<?php echo (int)$ch['id']; ?>" 
                                       class="btn btn-sm btn-outline">
                                        Toggle
                                    </a>
                                    <a href="?delete=<?php echo (int)$ch['id']; ?>" 
                                       class="btn btn-sm btn-outline" 
                                       style="color:var(--danger);"
                                       onclick="return confirm('Delete this charge?')">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No additional charges yet.
                                <a href="additional_charge_form.php" style="color:var(--primary);">Add first charge</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>