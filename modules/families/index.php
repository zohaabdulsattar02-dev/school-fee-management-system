<?php
session_start();
$page_title = "Family List";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$msg = "";

if (isset($_GET['inactive'])) {
    $id = intval($_GET['inactive']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE families SET status='Inactive' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Family marked as Inactive.";
}

if (isset($_GET['active'])) {
    $id = intval($_GET['active']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE families SET status='Active' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Family marked as Active.";
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Standard: Prepared Statement
    $stmt1 = mysqli_prepare($conn, "UPDATE students SET status='Inactive' WHERE family_id=?");
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);

    $stmt2 = mysqli_prepare($conn, "DELETE FROM families WHERE id=?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
    $msg = "Family deleted successfully.";
}

$families = [];
$result = mysqli_query($conn, "SELECT * FROM families ORDER BY id DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $families[] = $row;
    }
}
?>

<?php if ($msg): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <input type="text" class="form-control" placeholder="Search Family ID / Father Name / Contact" style="min-width:280px;">
    <a href="add.php" class="btn btn-primary" style="margin-left:auto;">
        <i class="bi bi-plus-lg"></i> Add New Family
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Family ID</th>
                        <th>Father / Guardian</th>
                        <th>Mother / Guardian</th>
                        <th>Contact</th>
                        <th>Total Children</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($families) > 0): ?>
                        <?php foreach ($families as $family): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($family['family_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($family['father_name']); ?></td>
                            <td><?php echo htmlspecialchars($family['mother_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($family['contact'] ?? '-'); ?></td>
                            <td><?php echo (int)$family['total_children']; ?></td>
                            <td>
                                <?php if ($family['status'] === 'Active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="details.php?id=<?php echo (int)$family['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <a href="edit.php?id=<?php echo (int)$family['id']; ?>" class="btn btn-sm btn-outline">Edit</a>

                                    <?php if ($family['status'] === 'Active'): ?>
                                        <a href="?inactive=<?php echo (int)$family['id']; ?>" class="btn btn-sm btn-outline"
                                           onclick="return confirm('Mark as Inactive?')">Inactive</a>
                                    <?php else: ?>
                                        <a href="?active=<?php echo (int)$family['id']; ?>" class="btn btn-sm btn-outline">Active</a>
                                    <?php endif; ?>

                                    <a href="?delete=<?php echo (int)$family['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                       onclick="return confirm('Delete this family?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No families found. <a href="add.php" style="color:var(--primary);">Add first family</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>