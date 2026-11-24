<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

require_once '../../includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Standard: Prepared Statement - SQL Injection se safe
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if ((int)$user['status'] === 0) {
            $error = "Your account is pending Principal approval. Please wait.";
        } elseif ((int)$user['status'] !== 1) {
            $error = "Your account is inactive. Contact Principal.";
        } elseif (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            header("Location: ../dashboard/index.php");
            exit;
        } else {
            $error = "Incorrect email or password.";
        }
    } else {
        $error = "Incorrect email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Login | Great Chancellor School - Staff Portal</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            width: 1100px;
            max-width: 95%;
            min-height: 620px;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 20px 50px rgba(15,28,63,0.12);
        }
        .left-panel {
            width: 45%;
            background: var(--sidebar-bg);
            color: #fff;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .left-panel img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin-bottom: 25px;
        }
        .left-panel h1 { font-size: 26px; font-weight: 700; margin-bottom: 8px; }
        .left-panel p { color: rgba(255,255,255,0.65); font-size: 15px; }
        .left-panel .tagline {
            margin-top: 40px;
            font-size: 14px;
            color: rgba(255,255,255,0.5);
            line-height: 1.7;
        }
        .right-panel {
            width: 55%;
            padding: 50px 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box { width: 100%; max-width: 400px; }
        .login-box h2 { font-size: 28px; color: var(--text); margin-bottom: 8px; }
        .login-box .subtitle { color: var(--text-muted); font-size: 14px; margin-bottom: 30px; }
        .staff-notice {
            background:#FFF7ED;border:1px solid #FDBA74;color:#9A3412;
            padding:12px 14px;border-radius:10px;margin-bottom:20px;
            font-size:13px;line-height:1.6;text-align:center;
        }
        .error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 7px;
            color: var(--text);
        }
        .form-group input {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }
        .pass-wrap { position: relative; }
        .pass-wrap input { padding-right: 45px; }
        .eye-btn {
            position: absolute;
            right: 12px;
            top: 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
        }
        .forgot { text-align: right; margin-bottom: 20px; }
        .forgot a { color: var(--primary); font-size: 13px; font-weight: 600; text-decoration: none; }
        .btn-login {
            width: 100%;
            height: 50px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-login:hover { background: var(--primary-hover); }
        .bottom-links {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .bottom-links a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .security {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="left-panel">
            <img src="../../assets/images/logo.png" alt="Logo">
            <h1>Great Chancellor School</h1>
            <p>School Management Portal - Staff Only</p>
            <div class="tagline">
                Manage fees, expenses and family accounts with ease.<br>
                Secure • Reliable • Easy to Use
            </div>
        </div>

        <div class="right-panel">
            <div class="login-box">
                <h2>Welcome Back!</h2>
                <p class="subtitle">Great Chancellor School Staff Login</p>

                <div class="staff-notice">
                  <strong>🔒 Staff Only Portal</strong><br>
                  This portal is for Great Chancellor School staff only.<br>
                  Unauthorized access is not allowed.
                </div>

                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Staff Email</label>
                        <input type="email" name="username" placeholder="yourname@greatcancellor.edu" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="pass-wrap">
                            <input type="password" name="password" id="password" placeholder="Enter password" required>
                            <button type="button" class="eye-btn" onclick="togglePass()">👁</button>
                        </div>
                    </div>

                    <div class="forgot">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-login">Login</button>
                </form>

                <div class="bottom-links">
                    New staff? Contact Principal for account approval.
                </div>
                <div class="security">Secure • Staff Only • Great Chancellor School</div>
            </div>
        </div>
    </div>

    <script>
        function togglePass() {
            const p = document.getElementById('password');
            p.type = (p.type === 'password') ? 'text' : 'password';
        }
    </script>
</body>
</html>