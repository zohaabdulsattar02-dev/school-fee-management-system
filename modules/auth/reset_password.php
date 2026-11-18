<?php
session_start();
require_once '../../includes/db.php';

$success = "";
$error = "";
$email = $_SESSION['reset_email'] ?? '';

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp      = mysqli_real_escape_string($conn, trim($_POST['otp']));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $res = mysqli_query($conn, "SELECT id, otp, otp_expiry FROM users WHERE email = '$email' LIMIT 1");
        if ($res && mysqli_num_rows($res) === 1) {
            $user = mysqli_fetch_assoc($res);

            if ($user['otp'] !== $otp) {
                $error = "Invalid OTP!";
            } elseif (strtotime($user['otp_expiry']) < time()) {
                $error = "OTP expired! Please request again.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password='$hashed', otp=NULL, otp_expiry=NULL WHERE id={$user['id']}");
                unset($_SESSION['reset_email']);
                $success = "Password updated successfully! You can now login.";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | Great Chancellors School</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background:#F7F8FC; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:16px; width:100%; max-width:420px; box-shadow:0 15px 40px rgba(0,0,0,0.08); }
        h2 { margin:0 0 8px; color:#1E293B; }
        p { color:#64748B; font-size:14px; margin:0 0 20px; }
        input { width:100%; height:46px; padding:0 14px; border:1px solid #E2E8F0; border-radius:10px; margin-bottom:14px; box-sizing:border-box; }
        button { width:100%; height:46px; background:#E85D04; color:#fff; border:none; border-radius:10px; font-weight:700; cursor:pointer; }
        .ok { background:#E6F7F5; color:#2A9D8F; padding:12px; border-radius:10px; margin-bottom:14px; font-size:14px; }
        .err { background:#FEF2F2; color:#DC2626; padding:12px; border-radius:10px; margin-bottom:14px; font-size:14px; }
        a { color:#E85D04; font-weight:600; text-decoration:none; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reset Password</h2>
        <p>Email: <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if ($success): ?>
            <div class="ok"><?php echo $success; ?></div>
            <p style="text-align:center;"><a href="login.php">Go to Login</a></p>
        <?php else: ?>
            <?php if ($error): ?><div class="err"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST">
                <input type="text" name="otp" placeholder="Enter 6-digit OTP" required>
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Update Password</button>
            </form>
            <p style="margin-top:18px; text-align:center;">
                <a href="forgot_password.php">Resend OTP</a> · <a href="login.php">Login</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>