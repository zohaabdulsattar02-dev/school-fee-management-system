<?php
session_start();
$page_title = "Student List";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$msg = "";

if (isset($_GET['inactive'])) {
    $id = intval($_GET['inactive']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE students SET status='Inactive' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Student marked as Inactive.";
}

if (isset($_GET['active'])) {
    $id = intval($_GET['active']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "UPDATE students SET status='Active' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Student marked as Active.";
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Standard: Prepared Statement
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Student deleted successfully.";
}

$students = [];
$query = "SELECT s.*, f.family_code, f.father_name as family_father, c.class_name 
          FROM students s
          LEFT JOIN families f ON s.family_id = f.id
          LEFT JOIN classes c ON s.class_id = c.id
          ORDER BY s.id DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}
?>

<?php if ($msg): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <input type="text" class="form-control" placeholder="Search Student Name / ID" style="min-width:200px;">
    <a href="add.php" class="btn btn-primary" style="margin-left:auto;">
        <i class="bi bi-plus-lg"></i> Add New Student
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Class</th>
                        <th>Family ID</th>
                        <th>Monthly Fee</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['student_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['father_name'] ?? $student['family_father'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></td>
                            <td>
                                <a href="../families/details.php?id=<?php echo (int)$student['family_id']; ?>"
                                   style="color:var(--primary); font-weight:600; text-decoration:none;">
                                    <?php echo htmlspecialchars($student['family_code'] ?? '-'); ?>
                                </a>
                            </td>
                            <td>Rs. <?php echo number_format($student['monthly_fee'], 0); ?></td>
                            <td>
                                <?php if ($student['status'] === 'Active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo htmlspecialchars($student['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="details.php?id=<?php echo (int)$student['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <a href="edit.php?id=<?php echo (int)$student['id']; ?>" class="btn btn-sm btn-outline">Edit</a>

                                    <?php if ($student['status'] === 'Active'): ?>
                                        <a href="?inactive=<?php echo (int)$student['id']; ?>" class="btn btn-sm btn-outline"
                                           onclick="return confirm('Mark as Inactive?')">Inactive</a>
                                    <?php else: ?>
                                        <a href="?active=<?php echo (int)$student['id']; ?>" class="btn btn-sm btn-outline">Active</a>
                                    <?php endif; ?>

                                    <a href="?delete=<?php echo (int)$student['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                       onclick="return confirm('Delete this student?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No students found. <a href="add.php" style="color:var(--primary);">Add first student</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>