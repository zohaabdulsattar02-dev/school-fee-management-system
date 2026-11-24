<?php
session_start();

require_once '../../includes/db.php';
require_once '../../includes/mailer.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($res && mysqli_num_rows($res) === 1) {
                $user = mysqli_fetch_assoc($res);
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $update = mysqli_prepare($conn, "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
                if ($update) {
                    mysqli_stmt_bind_param($update, "ssi", $otp, $expiry, $user['id']);
                    if (mysqli_stmt_execute($update)) {
                        $mail_sent = sendOTPEmail($user['email'], $otp);
                        if ($mail_sent) {
                            $_SESSION['reset_email'] = $user['email'];
                        } else {
                            $clear = mysqli_prepare($conn, "UPDATE users SET otp = NULL, otp_expiry = NULL WHERE id = ?");
                            if ($clear) {
                                mysqli_stmt_bind_param($clear, "i", $user['id']);
                                mysqli_stmt_execute($clear);
                                mysqli_stmt_close($clear);
                            }
                        }
                    }
                    mysqli_stmt_close($update);
                }
            }
            mysqli_stmt_close($stmt);
            // Standard: Hamesha ek hi msg - chahe email ho ya na ho - taake hacker ko pata na chale!
            $success = "If this email is registered, an OTP has been sent to it. Please check your inbox.";
        } else {
            $error = "Unable to process your request. Please try again.";
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
    <title>Forgot Password | Great Chancellor School - Staff Portal</title>
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
        <h2>Forgot Password</h2>
        <p>Great Chancellor School - Staff Only</p>
        <div class="staff-notice">
          <strong>🔒 Staff Only</strong><br>
          Only registered staff can reset password.
        </div>
        <?php if ($success): ?>
            <div class="ok"><?php echo htmlspecialchars($success); ?></div>
            <p style="text-align:center;">
                <a href="reset_password.php">Continue to Reset Password →</a>
            </p>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!$success): ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Enter your staff email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                <button type="submit">Send OTP</button>
            </form>
        <?php endif; ?>
        <p style="margin-top:18px; text-align:center;">
            <a href="login.php">← Back to Login</a>
        </p>
    </div>
</body>
</html>