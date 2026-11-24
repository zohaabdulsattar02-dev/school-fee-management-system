<?php
session_start();
require_once '../../includes/db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name'] ?? "");
    $email    = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm  = $_POST['confirm_password'] ?? "";
    // Standard: Role user se nahi lenge - hamesha staff hi rahega, Admin kabhi nahi!
    $role = "accountant";

    if (empty($name) || empty($email)) {
        $error = "Please fill all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters!";
    } else {
        // Check - Prepared Statement
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $check = mysqli_stmt_get_result($stmt);

        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Email already registered!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // status = 0 means Pending Approval - Principal approve karega tab hi login hoga
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt2, "ssss", $name, $email, $hashed, $role);

            if (mysqli_stmt_execute($stmt2)) {
                $success = "Request sent! Please wait for Principal approval before login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
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
    <title>Request Account | Great Chancellor School - Staff Portal</title>
    <style>
        :root {
            --primary: #E85D04;
            --primary-hover: #D94E03;
            --sidebar-bg: #0F1C3F;
            --bg: #F7F8FC;
            --text: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .box {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(15,28,63,0.1);
        }
        h2 { font-size: 26px; color: var(--text); margin-bottom: 6px; }
        .subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 25px; }
        .staff-notice {
            background:#FFF7ED;border:1px solid #FDBA74;color:#9A3412;
            padding:12px 14px;border-radius:10px;margin-bottom:20px;
            font-size:13px;line-height:1.6;text-align:center;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
            color: var(--text);
        }
        .form-group input, .form-group select {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(232,93,4,0.12);
        }
        .btn {
            width: 100%;
            height: 48px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn:hover { background: var(--primary-hover); }
        .msg-success {
            background: #E6F7F5;
            color: #2A9D8F;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .msg-error {
            background: #FEF2F2;
            color: #DC2626;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Request Staff Account</h2>
        <p class="subtitle">Great Chancellor School - Staff Only</p>

        <div class="staff-notice">
          <strong>🔒 Staff Only</strong><br>
          Only Great Chancellor School staff can request an account.<br>
          Principal approval is required.
        </div>

        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required placeholder="Enter your name">
            </div>
            <div class="form-group">
                <label>Staff Email *</label>
                <input type="email" name="email" required placeholder="yourname@greatcancellor.edu">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
            </div>
            <button type="submit" class="btn">Request Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>