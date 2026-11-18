<?php
session_start();
$page_title = "Edit Family";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$family_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";

if ($family_id === 0) {
    echo "<div class='card'><div class='card-body'>Invalid Family ID</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch family
$family = null;
$res = mysqli_query($conn, "SELECT * FROM families WHERE id = $family_id LIMIT 1");
if ($res && mysqli_num_rows($res) > 0) {
    $family = mysqli_fetch_assoc($res);
} else {
    echo "<div class='card'><div class='card-body'>Family not found</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Update Family
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $father_name = mysqli_real_escape_string($conn, trim($_POST['father_name']));
    $mother_name = mysqli_real_escape_string($conn, trim($_POST['mother_name']));
    $contact     = mysqli_real_escape_string($conn, trim($_POST['contact']));
    $whatsapp    = mysqli_real_escape_string($conn, trim($_POST['whatsapp']));
    $address     = mysqli_real_escape_string($conn, trim($_POST['address']));
    $remarks     = mysqli_real_escape_string($conn, trim($_POST['remarks']));
    $status      = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE families SET 
                father_name = '$father_name',
                mother_name = '$mother_name',
                contact = '$contact',
                whatsapp = '$whatsapp',
                address = '$address',
                remarks = '$remarks',
                status = '$status'
              WHERE id = $family_id";

    if (mysqli_query($conn, $query)) {
        $success = "Family updated successfully!";
        // Refresh data
        $res = mysqli_query($conn, "SELECT * FROM families WHERE id = $family_id LIMIT 1");
        $family = mysqli_fetch_assoc($res);
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
    <a href="details.php?id=<?php echo $family_id; ?>" class="btn btn-outline btn-sm">
        <i class="bi bi-eye"></i> View Details
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Edit Family — <?php echo htmlspecialchars($family['family_code']); ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                <div class="form-group">
                    <label>Father / Guardian Name *</label>
                    <input type="text" name="father_name" class="form-control" required
                           value="<?php echo htmlspecialchars($family['father_name']); ?>">
                </div>

                <div class="form-group">
                    <label>Mother / Guardian Name</label>
                    <input type="text" name="mother_name" class="form-control"
                           value="<?php echo htmlspecialchars($family['mother_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="contact" class="form-control" required
                           value="<?php echo htmlspecialchars($family['contact'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp" class="form-control"
                           value="<?php echo htmlspecialchars($family['whatsapp'] ?? ''); ?>">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($family['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" <?php echo $family['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $family['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="<?php echo htmlspecialchars($family['remarks'] ?? ''); ?>">
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Family
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>