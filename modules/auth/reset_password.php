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
    $otp      = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters!";
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } else {
        // Standard: Prepared Statement
        $stmt = mysqli_prepare($conn, "SELECT id, otp, otp_expiry FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($res && mysqli_num_rows($res) === 1) {
            $user = mysqli_fetch_assoc($res);

            if (!hash_equals((string)$user['otp'], (string)$otp)) {
                $error = "Invalid OTP!";
            } elseif (strtotime($user['otp_expiry']) < time()) {
                $error = "OTP expired! Please request again.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd = mysqli_prepare($conn, "UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?");
                mysqli_stmt_bind_param($upd, "si", $hashed, $user['id']);
                mysqli_stmt_execute($upd);
                unset($_SESSION['reset_email']);
                $success = "Password updated successfully! You can now login.";
            }
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Reset Password | Great Chancellor School - Staff Portal</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background:#F7F8FC; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:16px; width:100%; max-width:420px; box-shadow:0 15px 40px rgba(0,0,0,0.08); }
        h2 { margin:0 0 8px; color:#1E293B; }
        p { color:#64748B; font-size:14px; margin:0 0 20px; }
        .staff-notice { background:#FFF7ED;border:1px solid #FDBA74;color:#9A3412;padding:12px 14px;border-radius:10px;margin-bottom:20px;font-size:13px;line-height:1.6;text-align:center; }
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
        <p>Great Chancellor School - Staff Only</p>
        <div class="staff-notice">
          <strong>🔒 Staff Only</strong><br>
          Reset password for your staff account only.
        </div>
        <p>Email: <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if ($success): ?>
            <div class="ok"><?php echo htmlspecialchars($success); ?></div>
            <p style="text-align:center;"><a href="login.php">Go to Login</a></p>
        <?php else: ?>
            <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST">
                <input type="text" name="otp" placeholder="Enter 6-digit OTP" required>
                <input type="password" name="password" placeholder="New Password (Min 8 characters)" required>
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