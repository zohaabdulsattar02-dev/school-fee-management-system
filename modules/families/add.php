<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Add New Family";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $contact     = trim($_POST['contact'] ?? '');
    $whatsapp    = trim($_POST['whatsapp'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $remarks     = trim($_POST['remarks'] ?? '');
    $status      = trim($_POST['status'] ?? 'Active');

    if ($father_name === '' || $contact === '') {
        $error = "Please fill all required fields.";
    } else {
        // Auto generate Family Code (F-0001, F-0002...)
        $code_result = mysqli_query($conn, "SELECT family_code FROM families ORDER BY id DESC LIMIT 1");
        
        if ($code_result && mysqli_num_rows($code_result) > 0) {
            $last = mysqli_fetch_assoc($code_result);
            $last_num = intval(str_replace('F-', '', $last['family_code']));
            $new_num = $last_num + 1;
        } else {
            $new_num = 1;
        }
        $family_code = 'F-' . str_pad($new_num, 4, '0', STR_PAD_LEFT);

        // Standard: Prepared Statement
        $total_children = 0;
        $stmt = mysqli_prepare($conn, "INSERT INTO families (family_code, father_name, mother_name, contact, whatsapp, address, total_children, status, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssiss", $family_code, $father_name, $mother_name, $contact, $whatsapp, $address, $total_children, $status, $remarks);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Family added successfully! Family ID: " . $family_code;
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
        <a href="index.php" style="margin-left:15px; color:var(--primary); font-weight:600;">View All Families</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px; border:1px solid #FECACA;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Add New Family</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                <div class="form-group">
                    <label>Father / Guardian Name *</label>
                    <input type="text" name="father_name" class="form-control" required placeholder="Enter father name">
                </div>

                <div class="form-group">
                    <label>Mother / Guardian Name</label>
                    <input type="text" name="mother_name" class="form-control" placeholder="Enter mother name">
                </div>

                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="contact" class="form-control" required placeholder="0300-XXXXXXX">
                </div>

                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp" class="form-control" placeholder="0300-XXXXXXX">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Enter full address"></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Any note...">
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Family
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>