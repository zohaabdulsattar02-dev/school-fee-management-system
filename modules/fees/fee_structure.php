<?php
session_start();
$page_title = "Fee Structure";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Save amounts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_structure'])) {
    $class_id = intval($_POST['class_id']);
    if ($class_id && isset($_POST['amount']) && is_array($_POST['amount'])) {
        foreach ($_POST['amount'] as $component_id => $amt) {
            $component_id = intval($component_id);
            $amount = floatval($amt);

            // Standard: Prepared Statement for check
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM class_fee_structure WHERE class_id=? AND component_id=? AND (academic_year_id IS NULL OR academic_year_id=0) LIMIT 1");
            mysqli_stmt_bind_param($check_stmt, "ii", $class_id, $component_id);
            mysqli_stmt_execute($check_stmt);
            $check = mysqli_stmt_get_result($check_stmt);

            if ($check && mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                $upd = mysqli_prepare($conn, "UPDATE class_fee_structure SET amount=?, status=1 WHERE id=?");
                mysqli_stmt_bind_param($upd, "di", $amount, $row['id']);
                mysqli_stmt_execute($upd);
            } else {
                $ins = mysqli_prepare($conn, "INSERT INTO class_fee_structure (class_id, component_id, amount, status) VALUES (?, ?, ?, 1)");
                mysqli_stmt_bind_param($ins, "iid", $class_id, $component_id, $amount);
                mysqli_stmt_execute($ins);
            }
        }

        // Optional: sync Monthly Tuition to students.monthly_fee of this class
        $tq_stmt = mysqli_prepare($conn, "SELECT cfs.amount FROM class_fee_structure cfs JOIN fee_components fc ON cfs.component_id = fc.id WHERE cfs.class_id=? AND fc.component_code='TUITION' LIMIT 1");
        mysqli_stmt_bind_param($tq_stmt, "i", $class_id);
        mysqli_stmt_execute($tq_stmt);
        $tq = mysqli_stmt_get_result($tq_stmt);
        if ($tq && mysqli_num_rows($tq) > 0) {
            $tuition = floatval(mysqli_fetch_assoc($tq)['amount']);
        }

        $success = "Fee structure saved for this class!";
    }
}

$classes = [];
$cq = mysqli_query($conn, "SELECT * FROM classes WHERE status=1 ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

$components = [];
$compq = mysqli_query($conn, "SELECT * FROM fee_components WHERE is_active=1 ORDER BY id ASC");
if ($compq) while ($r = mysqli_fetch_assoc($compq)) $components[] = $r;

$amounts = [];
if ($class_id > 0) {
    $aq_stmt = mysqli_prepare($conn, "SELECT component_id, amount FROM class_fee_structure WHERE class_id=?");
    mysqli_stmt_bind_param($aq_stmt, "i", $class_id);
    mysqli_stmt_execute($aq_stmt);
    $aq = mysqli_stmt_get_result($aq_stmt);
    if ($aq) while ($r = mysqli_fetch_assoc($aq)) $amounts[$r['component_id']] = $r['amount'];
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="fee_components.php" class="btn btn-outline btn-sm">Manage Components</a>
    <a href="additional_charges.php" class="btn btn-outline btn-sm">Additional Charges →</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="margin:0;">
            <select name="class_id" class="form-control" required style="min-width:220px;" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>" <?php echo $class_id==$c['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($class_id > 0): ?>
<form method="POST">
    <input type="hidden" name="class_id" value="<?php echo (int)$class_id; ?>">
    <div class="card">
        <div class="card-header">
            <strong>Fee Structure — Class selected</strong>
            <button type="submit" name="save_structure" class="btn btn-primary btn-sm">Save Structure</button>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fee Component</th>
                            <th>Frequency</th>
                            <th>Amount (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($components)>0): foreach ($components as $comp): 
                            $val = $amounts[$comp['id']] ?? 0;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($comp['component_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($comp['frequency']); ?></td>
                            <td style="max-width:180px;">
                                <input type="number" name="amount[<?php echo (int)$comp['id']; ?>]" 
                                       class="form-control" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($val); ?>">
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center;padding:30px;">
                                No components. <a href="fee_components.php">Add components first</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="padding:16px; border-top:1px solid var(--border); text-align:right;">
            <button type="submit" name="save_structure" class="btn btn-primary">Save Fee Structure</button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>