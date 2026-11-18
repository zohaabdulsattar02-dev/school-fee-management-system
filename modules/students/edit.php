<?php
session_start();
$page_title = "Edit Student";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";

if ($student_id === 0) {
    echo "<div class='card'><div class='card-body'>Invalid Student ID</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch student
$student = null;
$res = mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id LIMIT 1");
if ($res && mysqli_num_rows($res) > 0) {
    $student = mysqli_fetch_assoc($res);
} else {
    echo "<div class='card'><div class='card-body'>Student not found</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Families
$families = [];
$fam_q = mysqli_query($conn, "SELECT id, family_code, father_name FROM families WHERE status='Active' ORDER BY family_code");
if ($fam_q) {
    while ($row = mysqli_fetch_assoc($fam_q)) {
        $families[] = $row;
    }
}

// Classes
$classes = [];
$class_q = mysqli_query($conn, "SELECT id, class_name FROM classes WHERE status=1 ORDER BY id");
if ($class_q) {
    while ($row = mysqli_fetch_assoc($class_q)) {
        $classes[] = $row;
    }
}

// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $family_id      = intval($_POST['family_id']);
    $student_name   = mysqli_real_escape_string($conn, trim($_POST['student_name']));
    $father_name    = mysqli_real_escape_string($conn, trim($_POST['father_name']));
    $gender         = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob            = mysqli_real_escape_string($conn, $_POST['dob']);
    $class_id       = intval($_POST['class_id']);
    $roll_number    = mysqli_real_escape_string($conn, trim($_POST['roll_number']));
    $admission_date = mysqli_real_escape_string($conn, $_POST['admission_date']);
    $monthly_fee    = floatval($_POST['monthly_fee']);
    $discount       = floatval($_POST['discount'] ?? 0);
    $status         = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE students SET 
                family_id = $family_id,
                student_name = '$student_name',
                father_name = '$father_name',
                gender = '$gender',
                dob = '$dob',
                class_id = $class_id,
                roll_number = '$roll_number',
                admission_date = '$admission_date',
                monthly_fee = $monthly_fee,
                discount = $discount,
                status = '$status'
              WHERE id = $student_id";

    if (mysqli_query($conn, $query)) {
        $success = "Student updated successfully!";
        // Refresh data
        $res = mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id LIMIT 1");
        $student = mysqli_fetch_assoc($res);
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $success; ?>
        <a href="details.php?id=<?php echo $student_id; ?>" style="margin-left:15px; color:var(--primary); font-weight:600;">View Details</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Edit Student — <?php echo htmlspecialchars($student['student_code']); ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                <div class="form-group">
                    <label>Select Family *</label>
                    <select name="family_id" class="form-control" required>
                        <?php foreach ($families as $fam): ?>
                            <option value="<?php echo $fam['id']; ?>" <?php echo $fam['id'] == $student['family_id'] ? 'selected' : ''; ?>>
                                <?php echo $fam['family_code']; ?> — <?php echo $fam['father_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Student Name *</label>
                    <input type="text" name="student_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($student['student_name']); ?>">
                </div>

                <div class="form-group">
                    <label>Father Name</label>
                    <input type="text" name="father_name" class="form-control" 
                           value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control" 
                           value="<?php echo $student['dob'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Class *</label>
                    <select name="class_id" class="form-control" required>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>" <?php echo $cls['id'] == $student['class_id'] ? 'selected' : ''; ?>>
                                <?php echo $cls['class_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Roll Number</label>
                    <input type="text" name="roll_number" class="form-control" 
                           value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" 
                           value="<?php echo $student['admission_date'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label>Monthly Fee (Rs.) *</label>
                    <input type="number" name="monthly_fee" class="form-control" required step="0.01"
                           value="<?php echo $student['monthly_fee']; ?>">
                </div>

                <div class="form-group">
                    <label>Discount (Rs.)</label>
                    <input type="number" name="discount" class="form-control" step="0.01"
                           value="<?php echo $student['discount']; ?>">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" <?php echo $student['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $student['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Left" <?php echo $student['status'] === 'Left' ? 'selected' : ''; ?>>Left</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Student
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>