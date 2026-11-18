<?php
session_start();
require_once '../../includes/db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = mysqli_real_escape_string($conn, $_POST['role']);

    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        // Email already exists?
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Email already registered!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // status = 0 means Pending Approval
            $query = "INSERT INTO users (name, email, password, role, status) 
                      VALUES ('$name', '$email', '$hashed', '$role', 0)";

            if (mysqli_query($conn, $query)) {
                $success = "Account created successfully! Please wait for Principal approval before login.";
            } else {
                $error = "Error: " . mysqli_error($conn);
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
    <title>Sign Up | Great Chancellors School</title>
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
        <h2>Create Account</h2>
        <p class="subtitle">Sign up for School Management Portal</p>

        <?php if ($success): ?>
            <div class="msg-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="msg-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="name" required placeholder="Enter your name">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required placeholder="yourname@email.com">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Min 6 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="accountant">Accountant</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
