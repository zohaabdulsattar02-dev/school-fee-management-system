<?php
session_start();
$page_title = "Class Subjects";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Delete assignment
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM class_subjects WHERE id=$id");
    $success = "Subject removed from class!";
    if (isset($_GET['class_id'])) $class_id = intval($_GET['class_id']);
}

// Add / Update assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subject'])) {
    $class_id = intval($_POST['class_id']);
    $subject_id = intval($_POST['subject_id']);
    $total_marks = floatval($_POST['total_marks']);
    $passing_marks = floatval($_POST['passing_marks']);
    $order = intval($_POST['display_order']);

    if ($class_id && $subject_id) {
        $check = mysqli_query($conn, "SELECT id FROM class_subjects WHERE class_id=$class_id AND subject_id=$subject_id");
        if ($check && mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            mysqli_query($conn, "UPDATE class_subjects SET total_marks=$total_marks, passing_marks=$passing_marks, display_order=$order, status=1 WHERE id={$row['id']}");
            $success = "Class subject updated!";
        } else {
            mysqli_query($conn, "INSERT INTO class_subjects (class_id, subject_id, total_marks, passing_marks, display_order, status)
                VALUES ($class_id, $subject_id, $total_marks, $passing_marks, $order, 1)");
            $success = "Subject assigned to class!";
        }
    } else {
        $error = "Select class and subject!";
    }
}

$classes = [];
$cq = mysqli_query($conn, "SELECT * FROM classes WHERE status=1 ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

$all_subjects = [];
$sq = mysqli_query($conn, "SELECT * FROM exam_subjects WHERE status=1 ORDER BY subject_name ASC");
if ($sq) while ($r = mysqli_fetch_assoc($sq)) $all_subjects[] = $r;

$assigned = [];
if ($class_id > 0) {
    $aq = mysqli_query($conn, "SELECT cs.*, s.subject_name, s.subject_code 
        FROM class_subjects cs 
        JOIN exam_subjects s ON cs.subject_id = s.id 
        WHERE cs.class_id=$class_id 
        ORDER BY cs.display_order ASC, s.subject_name ASC");
    if ($aq) while ($r = mysqli_fetch_assoc($aq)) $assigned[] = $r;
}
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

<!-- Select Class -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="margin:0;">
            <select name="class_id" class="form-control" required style="min-width:220px;" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $class_id==$c['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($class_id > 0): ?>

<!-- Assign Subject Form -->
<div class="card mb-4">
    <div class="card-header"><strong>Assign Subject to Class</strong></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <div style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:12px; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($all_subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Total Marks</label>
                    <input type="number" name="total_marks" class="form-control" value="100" step="0.01" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Passing Marks</label>
                    <input type="number" name="passing_marks" class="form-control" value="40" step="0.01" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Order</label>
                    <input type="number" name="display_order" class="form-control" value="0">
                </div>
                <button type="submit" name="assign_subject" class="btn btn-primary">Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Assigned list -->
<div class="card">
    <div class="card-header"><strong>Subjects for this Class</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Subject</th>
                        <th>Code</th>
                        <th>Total Marks</th>
                        <th>Passing Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($assigned)>0): foreach ($assigned as $a): ?>
                    <tr>
                        <td><?php echo $a['display_order']; ?></td>
                        <td><strong><?php echo htmlspecialchars($a['subject_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($a['subject_code'] ?? '-'); ?></td>
                        <td><?php echo number_format($a['total_marks'], 0); ?></td>
                        <td><?php echo number_format($a['passing_marks'], 0); ?></td>
                        <td>
                            <a href="?class_id=<?php echo $class_id; ?>&delete=<?php echo $a['id']; ?>"
                               class="btn btn-sm btn-outline" style="color:var(--danger);"
                               onclick="return confirm('Remove this subject from class?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;padding:30px;">No subjects assigned. Assign above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>