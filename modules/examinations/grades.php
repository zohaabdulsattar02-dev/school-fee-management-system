<?php
session_start();
$page_title = "Grade Rules";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$edit = null;

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM grade_rules WHERE id=$id");
    $success = "Grade deleted!";
}

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = mysqli_query($conn, "SELECT * FROM grade_rules WHERE id=$id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = mysqli_real_escape_string($conn, trim($_POST['grade_name']));
    $min = floatval($_POST['min_percentage']);
    $max = floatval($_POST['max_percentage']);
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    $status = intval($_POST['status']);

    if ($grade === '') {
        $error = "Grade name required!";
    } else {
        if (isset($_POST['grade_id']) && intval($_POST['grade_id']) > 0) {
            $id = intval($_POST['grade_id']);
            mysqli_query($conn, "UPDATE grade_rules SET grade_name='$grade', min_percentage=$min, max_percentage=$max, remarks='$remarks', status=$status WHERE id=$id");
            $success = "Grade updated!";
        } else {
            mysqli_query($conn, "INSERT INTO grade_rules (grade_name, min_percentage, max_percentage, remarks, status)
                VALUES ('$grade', $min, $max, '$remarks', $status)");
            $success = "Grade added!";
        }
        $edit = null;
    }
}

$grades = [];
$q = mysqli_query($conn, "SELECT * FROM grade_rules ORDER BY min_percentage DESC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $grades[] = $r;
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><strong><?php echo $edit ? 'Edit Grade' : 'Add Grade'; ?></strong></div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="grade_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Grade *</label>
                    <input type="text" name="grade_name" class="form-control" required placeholder="A+, A, B..."
                           value="<?php echo htmlspecialchars($edit['grade_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Min %</label>
                    <input type="number" name="min_percentage" class="form-control" step="0.01" required
                           value="<?php echo $edit['min_percentage'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Max %</label>
                    <input type="number" name="max_percentage" class="form-control" step="0.01" required
                           value="<?php echo $edit['max_percentage'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo (!isset($edit) || $edit['status']==1)?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo (isset($edit) && $edit['status']==0)?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Excellent / Pass / Fail"
                           value="<?php echo htmlspecialchars($edit['remarks'] ?? ''); ?>">
                </div>
            </div>
            <div style="margin-top:10px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update Grade' : 'Add Grade'; ?></button>
                <?php if ($edit): ?><a href="grades.php" class="btn btn-outline">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Grade Rules</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Min %</th>
                        <th>Max %</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $g): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($g['grade_name']); ?></strong></td>
                        <td><?php echo $g['min_percentage']; ?></td>
                        <td><?php echo $g['max_percentage']; ?></td>
                        <td><?php echo htmlspecialchars($g['remarks'] ?? '-'); ?></td>
                        <td><?php echo $g['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?delete=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this grade?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>