<?php
session_start();
$page_title = "User Form";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = "";
$error = "";
$user = null;

// Fetch for edit
if ($edit_id > 0) {
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $edit_id LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
    }
}

// Save
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $status   = intval($_POST['status']);
    $password = $_POST['password'] ?? '';

    if ($edit_id > 0) {
        // Update
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET name='$name', email='$email', role='$role', status=$status, password='$hashed' WHERE id=$edit_id";
        } else {
            $query = "UPDATE users SET name='$name', email='$email', role='$role', status=$status WHERE id=$edit_id";
        }

        if (mysqli_query($conn, $query)) {
            $success = "User updated successfully!";
            $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $edit_id LIMIT 1");
            $user = mysqli_fetch_assoc($res);
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    } else {
        // Insert
        if (empty($password)) {
            $error = "Password is required for new user!";
        } else {
            $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
            if ($check && mysqli_num_rows($check) > 0) {
                $error = "Email already exists!";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (name, email, password, role, status) 
                          VALUES ('$name', '$email', '$hashed', '$role', $status)";
                if (mysqli_query($conn, $query)) {
                    $success = "User added successfully!";
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="users.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Users
    </a>
</div>

<?php if ($success): ?>
    <div style="background:#E6F7F5; color:#2A9D8F; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $success; ?>
        <a href="users.php" style="margin-left:15px; color:var(--primary); font-weight:600;">View Users</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#FEF2F2; color:#DC2626; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong><?php echo $edit_id > 0 ? 'Edit User' : 'Add New User'; ?></strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" required
                           value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Password <?php echo $edit_id > 0 ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" class="form-control" <?php echo $edit_id > 0 ? '' : 'required'; ?>>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="accountant" <?php echo ($user['role'] ?? '') === 'accountant' ? 'selected' : ''; ?>>Accountant</option>
                        <option value="principal" <?php echo ($user['role'] ?? '') === 'principal' ? 'selected' : ''; ?>>Principal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" <?php echo ($user['status'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo ($user['status'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:20px; display:flex; gap:12px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?php echo $edit_id > 0 ? 'Update User' : 'Save User'; ?>
                </button>
                <a href="users.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>