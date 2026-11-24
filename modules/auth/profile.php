<?php
session_start();
$page_title = "My Profile";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$user_id = $_SESSION['user_id'] ?? 0;
$success = "";
$error = "";

$res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
$user = mysqli_fetch_assoc($res);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'] ?? '';

    // Email duplicate check
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if ($check && mysqli_num_rows($check) > 0) {
        $error = "This email is already used by another account!";
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET name='$name', email='$email', password='$hashed' WHERE id=$user_id";
        } else {
            $query = "UPDATE users SET name='$name', email='$email' WHERE id=$user_id";
        }

        if (mysqli_query($conn, $query)) {
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;
            $success = "Profile updated successfully!";
            $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
            $user = mysqli_fetch_assoc($res);
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="users.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back
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

<div class="card" style="max-width:600px;">
    <div class="card-header">
        <strong>Edit My Profile</strong>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required
                       value="<?php echo htmlspecialchars($user['name']); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required
                       value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="form-group">
                <label>New Password <span class="text-muted">(leave blank if no change)</span></label>
                <input type="password" name="password" class="form-control" placeholder="Enter new password">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Update Profile
            </button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>