<?php
session_start();
$page_title = "Additional Charge Form";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$edit = null;

if ($id > 0) {
    $q = mysqli_query($conn, "SELECT * FROM additional_charges WHERE id=$id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $charge_name     = mysqli_real_escape_string($conn, trim($_POST['charge_name']));
    $amount          = floatval($_POST['amount']);
    $description     = mysqli_real_escape_string($conn, trim($_POST['description']));
    $frequency       = mysqli_real_escape_string($conn, $_POST['frequency']);
    $applicable_type = mysqli_real_escape_string($conn, $_POST['applicable_type']);
    $class_id        = !empty($_POST['class_id']) ? intval($_POST['class_id']) : 'NULL';
    $student_id      = !empty($_POST['student_id']) ? intval($_POST['student_id']) : 'NULL';
    $family_id       = !empty($_POST['family_id']) ? intval($_POST['family_id']) : 'NULL';
    $charge_month    = !empty($_POST['charge_month']) ? "'".mysqli_real_escape_string($conn, $_POST['charge_month'])."'" : 'NULL';
    $charge_date     = !empty($_POST['charge_date']) ? "'".mysqli_real_escape_string($conn, $_POST['charge_date'])."'" : 'NULL';
    $status          = intval($_POST['status']);
    $uid             = $_SESSION['user_id'] ?? 'NULL';

    // clean non-applicable fields
    if ($applicable_type !== 'Class') $class_id = 'NULL';
    if ($applicable_type !== 'Student') $student_id = 'NULL';
    if ($applicable_type !== 'Family') $family_id = 'NULL';

    if ($charge_name === '' || $amount < 0) {
        $error = "Charge name and valid amount required!";
    } else {
        if (!empty($_POST['charge_id'])) {
            $cid = intval($_POST['charge_id']);
            mysqli_query($conn, "UPDATE additional_charges SET
                charge_name='$charge_name',
                amount=$amount,
                description='$description',
                frequency='$frequency',
                applicable_type='$applicable_type',
                class_id=$class_id,
                student_id=$student_id,
                family_id=$family_id,
                charge_month=$charge_month,
                charge_date=$charge_date,
                status=$status
                WHERE id=$cid");
            $success = "Charge updated successfully!";
            $id = $cid;
        } else {
            mysqli_query($conn, "INSERT INTO additional_charges
                (charge_name, amount, description, frequency, applicable_type, class_id, student_id, family_id, charge_month, charge_date, status, created_by)
                VALUES
                ('$charge_name', $amount, '$description', '$frequency', '$applicable_type', $class_id, $student_id, $family_id, $charge_month, $charge_date, $status, $uid)");
            $success = "Charge added successfully!";
            $id = mysqli_insert_id($conn);
        }

        // reload
        $q = mysqli_query($conn, "SELECT * FROM additional_charges WHERE id=$id LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
    }
}

// Apply charge to students (create student_charges Pending)
if (isset($_POST['apply_now']) && $edit) {
    $cid = intval($edit['id']);
    $name = mysqli_real_escape_string($conn, $edit['charge_name']);
    $amount = floatval($edit['amount']);
    $freq = mysqli_real_escape_string($conn, $edit['frequency']);
    $month = $edit['charge_month'] ? "'".mysqli_real_escape_string($conn, $edit['charge_month'])."'" : 'NULL';

    $students = [];

    if ($edit['applicable_type'] === 'School') {
        $sq = mysqli_query($conn, "SELECT id, family_id FROM students WHERE status='Active'");
        if ($sq) while ($r = mysqli_fetch_assoc($sq)) $students[] = $r;
    } elseif ($edit['applicable_type'] === 'Class' && $edit['class_id']) {
        $sq = mysqli_query($conn, "SELECT id, family_id FROM students WHERE status='Active' AND class_id=".intval($edit['class_id']));
        if ($sq) while ($r = mysqli_fetch_assoc($sq)) $students[] = $r;
    } elseif ($edit['applicable_type'] === 'Student' && $edit['student_id']) {
        $sq = mysqli_query($conn, "SELECT id, family_id FROM students WHERE id=".intval($edit['student_id'])." LIMIT 1");
        if ($sq) while ($r = mysqli_fetch_assoc($sq)) $students[] = $r;
    } elseif ($edit['applicable_type'] === 'Family' && $edit['family_id']) {
        $sq = mysqli_query($conn, "SELECT id, family_id FROM students WHERE status='Active' AND family_id=".intval($edit['family_id']));
        if ($sq) while ($r = mysqli_fetch_assoc($sq)) $students[] = $r;
    }

    $count = 0;
    foreach ($students as $st) {
        $sid = intval($st['id']);
        $fid = $st['family_id'] ? intval($st['family_id']) : 'NULL';

        // avoid duplicate pending same charge for same student
        $chk = mysqli_query($conn, "SELECT id FROM student_charges 
            WHERE additional_charge_id=$cid AND student_id=$sid AND status='Pending' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) continue;

        mysqli_query($conn, "INSERT INTO student_charges
            (additional_charge_id, student_id, family_id, charge_name, amount, frequency, charge_month, status)
            VALUES ($cid, $sid, $fid, '$name', $amount, '$freq', $month, 'Pending')");
        $count++;
    }
    $success = "Charge applied to $count student(s). Status: Pending until paid.";
}

$classes = [];
$cq = mysqli_query($conn, "SELECT id, class_name FROM classes WHERE status=1 ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="additional_charges.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <strong><?php echo $edit ? 'Edit Additional Charge' : 'Add Additional Charge'; ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?>
                <input type="hidden" name="charge_id" value="<?php echo $edit['id']; ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label>Charge Name *</label>
                    <input type="text" name="charge_name" class="form-control" required
                           placeholder="e.g. Paper Fee, Practical Fee"
                           value="<?php echo htmlspecialchars($edit['charge_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" required step="0.01" min="0"
                           value="<?php echo $edit['amount'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Frequency *</label>
                    <select name="frequency" class="form-control" required>
                        <?php
                        $freqs = ['One Time','Monthly','Yearly','Custom'];
                        $cur = $edit['frequency'] ?? 'One Time';
                        foreach ($freqs as $f) {
                            $sel = ($cur === $f) ? 'selected' : '';
                            echo "<option value='$f' $sel>$f</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Applicable To *</label>
                    <select name="applicable_type" id="applicable_type" class="form-control" required onchange="toggleTargets()">
                        <?php
                        $types = ['School','Class','Student','Family'];
                        $cur = $edit['applicable_type'] ?? 'Class';
                        foreach ($types as $t) {
                            $sel = ($cur === $t) ? 'selected' : '';
                            echo "<option value='$t' $sel>$t</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" id="wrap_class">
                    <label>Class</label>
                    <select name="class_id" class="form-control">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"
                                <?php echo (isset($edit['class_id']) && $edit['class_id']==$c['id'])?'selected':''; ?>>
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="wrap_student">
                    <label>Student ID (database id)</label>
                    <input type="number" name="student_id" class="form-control"
                           value="<?php echo $edit['student_id'] ?? ''; ?>" placeholder="e.g. 5">
                </div>

                <div class="form-group" id="wrap_family">
                    <label>Family ID (database id)</label>
                    <input type="number" name="family_id" class="form-control"
                           value="<?php echo $edit['family_id'] ?? ''; ?>" placeholder="e.g. 1">
                </div>

                <div class="form-group">
                    <label>Month (for Custom/Monthly)</label>
                    <input type="month" name="charge_month" class="form-control"
                           value="<?php echo $edit['charge_month'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Date (optional)</label>
                    <input type="date" name="charge_date" class="form-control"
                           value="<?php echo $edit['charge_date'] ?? ''; ?>">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control"
                           value="<?php echo htmlspecialchars($edit['description'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo (!isset($edit) || $edit['status']==1)?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo (isset($edit) && $edit['status']==0)?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit ? 'Update Charge' : 'Save Charge'; ?>
                </button>
                <?php if ($edit): ?>
                <button type="submit" name="apply_now" value="1" class="btn btn-outline"
                        onclick="return confirm('Apply this charge to matching students as Pending?')">
                    Apply to Students (Pending)
                </button>
                <?php endif; ?>
                <a href="additional_charges.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTargets() {
    const t = document.getElementById('applicable_type').value;
    document.getElementById('wrap_class').style.display = (t === 'Class') ? 'block' : 'none';
    document.getElementById('wrap_student').style.display = (t === 'Student') ? 'block' : 'none';
    document.getElementById('wrap_family').style.display = (t === 'Family') ? 'block' : 'none';
}
toggleTargets();
</script>

<?php require_once '../../includes/footer.php'; ?>