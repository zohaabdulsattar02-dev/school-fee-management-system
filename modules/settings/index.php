<?php
session_start();
$page_title = "Settings";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";

function get_setting($conn, $key, $default = '') {
    $key = mysqli_real_escape_string($conn, $key);
    $q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key='$key' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q)['setting_value'];
    }
    return $default;
}

function save_setting($conn, $key, $value) {
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    $check = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key='$key'");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET setting_value='$value' WHERE setting_key='$key'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
    }
}

// Save School Info
if (isset($_POST['save_school'])) {
    save_setting($conn, 'school_name', trim($_POST['school_name']));
    save_setting($conn, 'school_address', trim($_POST['school_address']));
    save_setting($conn, 'school_phone', trim($_POST['school_phone']));
    save_setting($conn, 'school_email', trim($_POST['school_email']));
    save_setting($conn, 'school_website', trim($_POST['school_website']));
    $success = "School information updated!";
}

// Add Academic Year
if (isset($_POST['add_year'])) {
    $year_name  = mysqli_real_escape_string($conn, trim($_POST['year_name']));
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date   = mysqli_real_escape_string($conn, $_POST['end_date']);
    $is_current = isset($_POST['is_current']) ? 1 : 0;

    if ($year_name !== '') {
        if ($is_current) {
            mysqli_query($conn, "UPDATE academic_years SET is_current = 0");
        }
        mysqli_query($conn, "INSERT INTO academic_years (year_name, start_date, end_date, is_current, status)
                             VALUES ('$year_name', '$start_date', '$end_date', $is_current, 1)");
        $success = "Academic year added!";
    }
}

// Update Academic Year
if (isset($_POST['update_year'])) {
    $id         = intval($_POST['year_id']);
    $year_name  = mysqli_real_escape_string($conn, trim($_POST['year_name']));
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date   = mysqli_real_escape_string($conn, $_POST['end_date']);
    $is_current = isset($_POST['is_current']) ? 1 : 0;

    if ($is_current) {
        mysqli_query($conn, "UPDATE academic_years SET is_current = 0");
    }

    mysqli_query($conn, "UPDATE academic_years SET 
        year_name='$year_name',
        start_date='$start_date',
        end_date='$end_date',
        is_current=$is_current
        WHERE id=$id");
    $success = "Academic year updated!";
}

// Set current year (only one)
if (isset($_GET['set_current_year'])) {
    $id = intval($_GET['set_current_year']);
    mysqli_query($conn, "UPDATE academic_years SET is_current = 0");
    mysqli_query($conn, "UPDATE academic_years SET is_current = 1 WHERE id = $id");
    $success = "Current academic year set!";
}

// Delete Academic Year
if (isset($_GET['delete_year'])) {
    $id = intval($_GET['delete_year']);
    // current year delete na ho
    $chk = mysqli_query($conn, "SELECT is_current FROM academic_years WHERE id=$id");
    $row = $chk ? mysqli_fetch_assoc($chk) : null;
    if ($row && (int)$row['is_current'] === 1) {
        $error = "Current year delete nahi ho sakti. Pehle koi aur year current set karo.";
    } else {
        mysqli_query($conn, "DELETE FROM academic_years WHERE id=$id");
        $success = "Academic year deleted!";
    }
}

// Classes
if (isset($_POST['add_class'])) {
    $class_name = mysqli_real_escape_string($conn, trim($_POST['class_name']));
    if ($class_name !== '') {
        mysqli_query($conn, "INSERT INTO classes (class_name, status) VALUES ('$class_name', 1)");
        $success = "Class added!";
    }
}
if (isset($_GET['delete_class'])) {
    mysqli_query($conn, "DELETE FROM classes WHERE id=" . intval($_GET['delete_class']));
    $success = "Class deleted!";
}

// Categories
if (isset($_POST['add_category'])) {
    $cat_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    if ($cat_name !== '') {
        mysqli_query($conn, "INSERT INTO expense_categories (category_name, status) VALUES ('$cat_name', 1)");
        $success = "Category added!";
    }
}
if (isset($_GET['delete_category'])) {
    mysqli_query($conn, "DELETE FROM expense_categories WHERE id=" . intval($_GET['delete_category']));
    $success = "Category deleted!";
}

// Load
$school_name    = get_setting($conn, 'school_name', 'Great Chancellors School');
$school_address = get_setting($conn, 'school_address', '');
$school_phone   = get_setting($conn, 'school_phone', '');
$school_email   = get_setting($conn, 'school_email', '');
$school_website = get_setting($conn, 'school_website', '');

$edit_year = null;
if (isset($_GET['edit_year'])) {
    $eid = intval($_GET['edit_year']);
    $eq = mysqli_query($conn, "SELECT * FROM academic_years WHERE id=$eid LIMIT 1");
    if ($eq && mysqli_num_rows($eq) > 0) {
        $edit_year = mysqli_fetch_assoc($eq);
    }
}

$years = [];
$yq = mysqli_query($conn, "SELECT * FROM academic_years ORDER BY id DESC");
if ($yq) while ($r = mysqli_fetch_assoc($yq)) $years[] = $r;

$classes = [];
$cq = mysqli_query($conn, "SELECT * FROM classes ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

$categories = [];
$catq = mysqli_query($conn, "SELECT * FROM expense_categories ORDER BY id ASC");
if ($catq) while ($r = mysqli_fetch_assoc($catq)) $categories[] = $r;
?>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

    <!-- School Info -->
    <div class="card">
        <div class="card-header"><strong>School Information</strong></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars($school_name); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="school_address" class="form-control" value="<?php echo htmlspecialchars($school_address); ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="school_phone" class="form-control" value="<?php echo htmlspecialchars($school_phone); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="school_email" class="form-control" value="<?php echo htmlspecialchars($school_email); ?>">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="text" name="school_website" class="form-control" value="<?php echo htmlspecialchars($school_website); ?>">
                </div>
                <button type="submit" name="save_school" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Academic Year -->
    <div class="card">
        <div class="card-header"><strong>Academic Year / Session</strong></div>
        <div class="card-body">

            <?php if ($edit_year): ?>
            <!-- EDIT FORM -->
            <form method="POST" style="margin-bottom:16px; background:#FFF7ED; padding:14px; border-radius:10px;">
                <input type="hidden" name="year_id" value="<?php echo $edit_year['id']; ?>">
                <div class="form-group">
                    <label>Year Name</label>
                    <input type="text" name="year_name" class="form-control" required
                           value="<?php echo htmlspecialchars($edit_year['year_name']); ?>">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required
                               value="<?php echo $edit_year['start_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required
                               value="<?php echo $edit_year['end_date']; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_current" value="1"
                            <?php echo $edit_year['is_current'] ? 'checked' : ''; ?>>
                        Set as Current Year
                    </label>
                </div>
                <button type="submit" name="update_year" class="btn btn-primary btn-sm">Update Year</button>
                <a href="index.php" class="btn btn-outline btn-sm">Cancel</a>
            </form>
            <?php else: ?>
            <!-- ADD FORM -->
            <form method="POST" style="margin-bottom:16px;">
                <div class="form-group">
                    <label>Year Name (e.g. 2026-27)</label>
                    <input type="text" name="year_name" class="form-control" required placeholder="2026-27">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_current" value="1"> Set as Current Year
                    </label>
                </div>
                <button type="submit" name="add_year" class="btn btn-primary btn-sm">Add Academic Year</button>
            </form>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Current</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($years) > 0): ?>
                            <?php foreach ($years as $y): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($y['year_name']); ?></strong></td>
                                <td><?php echo date('d-M-Y', strtotime($y['start_date'])); ?></td>
                                <td><?php echo date('d-M-Y', strtotime($y['end_date'])); ?></td>
                                <td>
                                    <?php if ($y['is_current']): ?>
                                        <span class="badge badge-success">Current</span>
                                    <?php else: ?>
                                        <a href="?set_current_year=<?php echo $y['id']; ?>" class="btn btn-sm btn-outline">Set Current</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="?edit_year=<?php echo $y['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <?php if (!$y['is_current']): ?>
                                        <a href="?delete_year=<?php echo $y['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                           onclick="return confirm('Delete this academic year?')">Delete</a>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">Protected</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;padding:20px;">No academic years</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Classes -->
    <div class="card">
        <div class="card-header"><strong>Classes</strong></div>
        <div class="card-body">
            <form method="POST" class="filter-bar" style="margin-bottom:16px;">
                <input type="text" name="class_name" class="form-control" placeholder="New class name" required style="min-width:180px;">
                <button type="submit" name="add_class" class="btn btn-primary btn-sm">Add</button>
            </form>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Class</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $cls): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cls['class_name']); ?></strong></td>
                            <td><?php echo $cls['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                            <td>
                                <a href="?delete_class=<?php echo $cls['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this class?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expense Categories -->
    <div class="card">
        <div class="card-header"><strong>Expense Categories</strong></div>
        <div class="card-body">
            <form method="POST" class="filter-bar" style="margin-bottom:16px;">
                <input type="text" name="category_name" class="form-control" placeholder="New category" required style="min-width:180px;">
                <button type="submit" name="add_category" class="btn btn-primary btn-sm">Add</button>
            </form>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Category</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($cat['category_name']); ?></strong></td>
                            <td><?php echo $cat['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                            <td>
                                <a href="?delete_category=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline" style="color:var(--danger);"
                                   onclick="return confirm('Delete this category?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>