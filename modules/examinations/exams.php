<?php
session_start();
$page_title = "Exam Management";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$edit = null;

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM exams WHERE id=$id");
    $success = "Exam deleted!";
}

// Edit load
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = mysqli_query($conn, "SELECT * FROM exams WHERE id=$id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
}

// Save / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_name = mysqli_real_escape_string($conn, trim($_POST['exam_name']));
    $exam_type = mysqli_real_escape_string($conn, trim($_POST['exam_type']));
    $year_id   = !empty($_POST['academic_year_id']) ? intval($_POST['academic_year_id']) : 'NULL';
    $start     = !empty($_POST['start_date']) ? "'".mysqli_real_escape_string($conn, $_POST['start_date'])."'" : 'NULL';
    $end       = !empty($_POST['end_date']) ? "'".mysqli_real_escape_string($conn, $_POST['end_date'])."'" : 'NULL';
    $status    = intval($_POST['status']);
    $uid       = $_SESSION['user_id'] ?? 'NULL';

    if (isset($_POST['exam_id']) && intval($_POST['exam_id']) > 0) {
        $id = intval($_POST['exam_id']);
        mysqli_query($conn, "UPDATE exams SET 
            exam_name='$exam_name', exam_type='$exam_type',
            academic_year_id=$year_id, start_date=$start, end_date=$end, status=$status
            WHERE id=$id");
        $success = "Exam updated!";
    } else {
        mysqli_query($conn, "INSERT INTO exams (exam_name, exam_type, academic_year_id, start_date, end_date, status, created_by)
            VALUES ('$exam_name', '$exam_type', $year_id, $start, $end, $status, $uid)");
        $success = "Exam added!";
    }
    $edit = null;
}

$years = [];
$yq = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY id DESC");
if ($yq) while ($r = mysqli_fetch_assoc($yq)) $years[] = $r;

$exams = [];
$eq = mysqli_query($conn, "SELECT e.*, a.year_name FROM exams e 
    LEFT JOIN academic_years a ON e.academic_year_id = a.id 
    ORDER BY e.id DESC");
if ($eq) while ($r = mysqli_fetch_assoc($eq)) $exams[] = $r;
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <strong><?php echo $edit ? 'Edit Exam' : 'Add Exam'; ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?>
                <input type="hidden" name="exam_id" value="<?php echo $edit['id']; ?>">
            <?php endif; ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Exam Name *</label>
                    <input type="text" name="exam_name" class="form-control" required
                           placeholder="First Term 2026"
                           value="<?php echo htmlspecialchars($edit['exam_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Exam Type</label>
                    <select name="exam_type" class="form-control">
                        <?php
                        $types = ['Term','Mid Term','Final Term','Monthly Test','Other'];
                        $cur = $edit['exam_type'] ?? 'Term';
                        foreach ($types as $t) {
                            $sel = ($cur === $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <select name="academic_year_id" class="form-control">
                        <option value="">-- Select --</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y['id']; ?>"
                                <?php echo (isset($edit['academic_year_id']) && $edit['academic_year_id']==$y['id'])?'selected':''; ?>>
                                <?php echo htmlspecialchars($y['year_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo (!isset($edit) || $edit['status']==1)?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo (isset($edit) && $edit['status']==0)?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?php echo $edit['start_date'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?php echo $edit['end_date'] ?? ''; ?>">
                </div>
            </div>
            <div style="margin-top:12px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update Exam' : 'Add Exam'; ?></button>
                <?php if ($edit): ?>
                    <a href="exams.php" class="btn btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>All Exams</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exam Name</th>
                        <th>Type</th>
                        <th>Year</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($exams)>0): foreach ($exams as $e): ?>
                    <tr>
                        <td><?php echo $e['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($e['exam_type']); ?></td>
                        <td><?php echo htmlspecialchars($e['year_name'] ?? '-'); ?></td>
                        <td>
                            <?php
                            echo $e['start_date'] ? date('d-M-Y', strtotime($e['start_date'])) : '-';
                            echo ' → ';
                            echo $e['end_date'] ? date('d-M-Y', strtotime($e['end_date'])) : '-';
                            ?>
                        </td>
                        <td>
                            <?php echo $e['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this exam?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:30px;">No exams yet. Add first exam above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>