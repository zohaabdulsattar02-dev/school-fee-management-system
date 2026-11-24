<?php
session_start();
$page_title = "Subjects";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$edit = null;

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM exam_subjects WHERE id=$id");
    $success = "Subject deleted!";
}

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = mysqli_query($conn, "SELECT * FROM exam_subjects WHERE id=$id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['subject_name']));
    $code = mysqli_real_escape_string($conn, trim($_POST['subject_code']));
    $status = intval($_POST['status']);

    if ($name === '') {
        $error = "Subject name required!";
    } else {
        if (isset($_POST['subject_id']) && intval($_POST['subject_id']) > 0) {
            $id = intval($_POST['subject_id']);
            mysqli_query($conn, "UPDATE exam_subjects SET subject_name='$name', subject_code='$code', status=$status WHERE id=$id");
            $success = "Subject updated!";
        } else {
            mysqli_query($conn, "INSERT INTO exam_subjects (subject_name, subject_code, status) VALUES ('$name', '$code', $status)");
            $success = "Subject added!";
        }
        $edit = null;
    }
}

$subjects = [];
$q = mysqli_query($conn, "SELECT * FROM exam_subjects ORDER BY subject_name ASC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $subjects[] = $r;
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
    <div class="card-header"><strong><?php echo $edit ? 'Edit Subject' : 'Add Subject'; ?></strong></div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="subject_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" class="form-control" required
                           placeholder="e.g. English, Urdu Oral, Math"
                           value="<?php echo htmlspecialchars($edit['subject_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Subject Code</label>
                    <input type="text" name="subject_code" class="form-control" placeholder="ENG, URD..."
                           value="<?php echo htmlspecialchars($edit['subject_code'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo (!isset($edit) || $edit['status']==1)?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo (isset($edit) && $edit['status']==0)?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:10px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update' : 'Add Subject'; ?></button>
                <?php if ($edit): ?><a href="subjects.php" class="btn btn-outline">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>All Subjects</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($subjects)>0): foreach ($subjects as $s): ?>
                    <tr>
                        <td><?php echo $s['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['subject_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['subject_code'] ?? '-'); ?></td>
                        <td><?php echo $s['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this subject?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;">No subjects. Add English, Urdu, Math etc.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>