<?php
session_start();
$page_title = "Fee Components";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";
$edit = null;

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM fee_components WHERE id=$id");
    $success = "Component deleted!";
}

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = mysqli_query($conn, "SELECT * FROM fee_components WHERE id=$id LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) $edit = mysqli_fetch_assoc($q);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['component_name']));
    $code = mysqli_real_escape_string($conn, trim($_POST['component_code']));
    $freq = mysqli_real_escape_string($conn, $_POST['frequency']);
    $status = intval($_POST['is_active']);

    if ($name === '') {
        $error = "Component name required!";
    } else {
        if (!empty($_POST['component_id'])) {
            $id = intval($_POST['component_id']);
            mysqli_query($conn, "UPDATE fee_components SET 
                component_name='$name', component_code='$code', frequency='$freq', is_active=$status 
                WHERE id=$id");
            $success = "Component updated!";
        } else {
            mysqli_query($conn, "INSERT INTO fee_components (component_name, component_code, frequency, is_active)
                VALUES ('$name', '$code', '$freq', $status)");
            $success = "Component added!";
        }
        $edit = null;
    }
}

$list = [];
$q = mysqli_query($conn, "SELECT * FROM fee_components ORDER BY id ASC");
if ($q) while ($r = mysqli_fetch_assoc($q)) $list[] = $r;
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="fee_structure.php" class="btn btn-outline btn-sm">Fee Structure →</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><strong><?php echo $edit ? 'Edit Component' : 'Add Fee Component'; ?></strong></div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="component_id" value="<?php echo $edit['id']; ?>"><?php endif; ?>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:14px;">
                <div class="form-group">
                    <label>Component Name *</label>
                    <input type="text" name="component_name" class="form-control" required
                           value="<?php echo htmlspecialchars($edit['component_name'] ?? ''); ?>"
                           placeholder="e.g. Monthly Tuition Fee">
                </div>
                <div class="form-group">
                    <label>Code</label>
                    <input type="text" name="component_code" class="form-control"
                           value="<?php echo htmlspecialchars($edit['component_code'] ?? ''); ?>"
                           placeholder="TUITION">
                </div>
                <div class="form-group">
                    <label>Frequency</label>
                    <select name="frequency" class="form-control">
                        <?php
                        $freqs = ['Monthly','One Time','Yearly','Custom'];
                        $cur = $edit['frequency'] ?? 'Monthly';
                        foreach ($freqs as $f) {
                            $sel = ($cur === $f) ? 'selected' : '';
                            echo "<option value='$f' $sel>$f</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" <?php echo (!isset($edit) || $edit['is_active']==1)?'selected':''; ?>>Active</option>
                        <option value="0" <?php echo (isset($edit) && $edit['is_active']==0)?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:12px; display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $edit ? 'Update' : 'Add Component'; ?></button>
                <?php if ($edit): ?><a href="fee_components.php" class="btn btn-outline">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>All Fee Components</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['component_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['component_code'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['frequency']); ?></td>
                        <td>
                            <?php echo $item['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this component?')">Delete</a>
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