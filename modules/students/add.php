<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Add New Student";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error   = "";

// Fetch all active families for dropdown
$families = [];
$fam_result = mysqli_query($conn, "SELECT id, family_code, father_name FROM families WHERE status = 'Active' ORDER BY family_code ASC");
if ($fam_result) {
    while ($row = mysqli_fetch_assoc($fam_result)) {
        $families[] = $row;
    }
}

// Fetch classes
$classes = [];
$class_result = mysqli_query($conn, "SELECT id, class_name FROM classes WHERE status = 1 ORDER BY id ASC");
if ($class_result) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $classes[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $family_id      = intval($_POST['family_id'] ?? 0);
    $student_name   = trim($_POST['student_name'] ?? '');
    $father_name    = trim($_POST['father_name'] ?? '');
    $gender         = trim($_POST['gender'] ?? 'Male');
    $class_id       = intval($_POST['class_id'] ?? 0);
    $section_id     = !empty($_POST['section_id']) ? intval($_POST['section_id']) : null;
    $roll_number    = trim($_POST['roll_number'] ?? '');
    $admission_date = trim($_POST['admission_date'] ?? date('Y-m-d'));
    $monthly_fee    = floatval($_POST['monthly_fee'] ?? 0);
    $discount       = floatval($_POST['discount'] ?? 0);
    $status         = trim($_POST['status'] ?? 'Active');

    // DOB optional
    $dob_raw = trim($_POST['dob'] ?? '');
    $dob = ($dob_raw === '') ? null : $dob_raw;

    if (empty($student_name) || $family_id === 0 || $class_id === 0) {
        $error = "Please fill all required fields.";
    } else {
        // Auto generate Student Code (S-0001, S-0002...)
        $code_result = mysqli_query($conn, "SELECT student_code FROM students ORDER BY id DESC LIMIT 1");
        if ($code_result && mysqli_num_rows($code_result) > 0) {
            $last = mysqli_fetch_assoc($code_result);
            $last_num = intval(str_replace('S-', '', $last['student_code']));
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        $student_code = 'S-' . str_pad($new_num, 4, '0', STR_PAD_LEFT);

        // Standard: Prepared Statement
        $stmt = mysqli_prepare($conn, "INSERT INTO students (student_code, family_id, student_name, father_name, gender, dob, class_id, section_id, roll_number, admission_date, monthly_fee, discount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sissssiissdds", $student_code, $family_id, $student_name, $father_name, $gender, $dob, $class_id, $section_id, $roll_number, $admission_date, $monthly_fee, $discount, $status);

        if (mysqli_stmt_execute($stmt)) {
            // Update total_children in families table
            $upd = mysqli_prepare($conn, "UPDATE families SET total_children = total_children + 1 WHERE id = ?");
            mysqli_stmt_bind_param($upd, "i", $family_id);
            mysqli_stmt_execute($upd);

            $success = "Student added successfully! Student ID: " . $student_code;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px; border:1px solid #B2E0DB;">
        <?php echo htmlspecialchars($success); ?>
        <a href="index.php" style="margin-left:15px; color:var(--primary); font-weight:600;">View All Students</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px; border:1px solid #FECACA;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Add New Student</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                <div class="form-group">
                    <label>Select Family *</label>
                    <select name="family_id" class="form-control" required>
                        <option value="">-- Select Family --</option>
                        <?php foreach ($families as $fam): ?>
                            <option value="<?php echo (int)$fam['id']; ?>">
                                <?php echo htmlspecialchars($fam['family_code']); ?> — <?php echo htmlspecialchars($fam['father_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Student Name *</label>
                    <input type="text" name="student_name" class="form-control" required placeholder="Enter student name">
                </div>

                <div class="form-group">
                    <label>Father Name</label>
                    <input type="text" name="father_name" class="form-control" placeholder="Enter father name">
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth <span class="text-muted">(optional)</span></label>
                    <input type="date" name="dob" class="form-control">
                </div>

                <div class="form-group">
                    <label>Class *</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo (int)$cls['id']; ?>"><?php echo htmlspecialchars($cls['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" name="roll_number" class="form-control" placeholder="e.g. 15">
                </div>

                <div class="form-group">
                    <label>Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Monthly Fee (Rs.) *</label>
                    <input type="number" name="monthly_fee" class="form-control" required placeholder="e.g. 5000" step="0.01">
                </div>

                <div class="form-group">
                    <label>Discount (Rs.)</label>
                    <input type="number" name="discount" class="form-control" value="0" step="0.01">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Student
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>