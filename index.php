<?php
include("config/config.php");

$error = "";
$success = "";
$mode = isset($_GET['action']) ? $_GET['action'] : 'login';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

function redirectByRole(string $role): void {
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'faculty') {
        header("Location: faculty/search.php");
    } else {
        header("Location: student/search.php");
    }
    exit();
}

if (isset($_POST['login'])) {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if ($identifier === '' || $password === '' || $role === '') {
        $error = "Role, username/email and password are required.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role FROM users WHERE (name = ? OR email = ?) AND role = ? LIMIT 1"
        );

        if ($stmt) {
$stmt->bind_param("sss", $identifier, $identifier, $role);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            $isPasswordValid = false;
            if ($user) {
                $isPasswordValid = password_verify($password, $user['password']);

                // Legacy md5 fallback for old records; rehash securely on successful login.
                if (!$isPasswordValid && strlen($user['password']) === 32 && hash_equals($user['password'], md5($password))) {
                    $isPasswordValid = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $rehashStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($rehashStmt) {
                        $rehashStmt->bind_param("si", $newHash, $user['id']);
                        $rehashStmt->execute();
                        $rehashStmt->close();
                    }
                }
            }

            if ($user && $isPasswordValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                redirectByRole($user['role']);
            } else {
                $error = "Invalid username/email or password";
            }
        } else {
            $error = "Unable to process login right now. Please try again.";
        }
    }
}

if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Please enter your registered email.";
        $mode = 'forgot';
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($user) {
                $plainToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $plainToken);
                $expiry = date('Y-m-d H:i:s', time() + 3600);

                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("ssi", $tokenHash, $expiry, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') .
                        '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                    $resetLink = rtrim($baseUrl, '/') . '/index.php?action=reset&token=' . urlencode($plainToken);

                    $subject = "Library Password Reset";
                    $message = "Hello " . $user['name'] . ",\n\n" .
                        "We received a password reset request for your account.\n" .
                        "Use this link to reset your password (valid for 1 hour):\n" .
                        $resetLink . "\n\n" .
                        "If you did not request this, ignore this email.";
                    $headers = "From: no-reply@library.local\r\n";

                    $mailSent = @mail($email, $subject, $message, $headers);
                    if ($mailSent) {
                        $success = "Password reset link sent to your email.";
                    } else {
                        $success = "Reset token created. Email delivery failed on this server, but your reset link is: " . $resetLink;
                    }
                    $mode = 'forgot';
                } else {
                    $error = "Could not generate reset token. Please try again.";
                    $mode = 'forgot';
                }
            } else {
                $error = "Email not found.";
                $mode = 'forgot';
            }
        } else {
            $error = "Unable to process request right now. Please try again.";
            $mode = 'forgot';
        }
    }
}

if (isset($_POST['reset_password'])) {
    $mode = 'reset';
    $token = trim($_POST['token'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        $error = "Invalid or missing reset token.";
    } elseif ($newPassword === '' || $confirmPassword === '') {
        $error = "Please fill in both password fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $tokenHash = hash('sha256', $token);
        $now = date('Y-m-d H:i:s');

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires_at IS NOT NULL AND reset_token_expires_at > ? LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("ss", $tokenHash, $now);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($user) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
                );
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $newPasswordHash, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    $success = "Password updated successfully. You can now log in.";
                    $mode = 'login';
                    $token = '';
                } else {
                    $error = "Unable to update password. Please try again.";
                }
            } else {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? LIMIT 1");
                if ($checkStmt) {
                    $checkStmt->bind_param("s", $tokenHash);
                    $checkStmt->execute();
                    $expiredResult = $checkStmt->get_result();
                    $tokenExists = $expiredResult && $expiredResult->num_rows > 0;
                    $checkStmt->close();

                    $error = $tokenExists ? "Reset link has expired." : "Invalid reset link.";
                } else {
                    $error = "Invalid or expired reset link.";
                }
            }
        } else {
            $error = "Unable to validate token right now. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Library Login</title>

<style>
:root{--primary:#E24C24;--primary2:#C93E18;--navy:#1F2940;--muted:#64748b}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{min-height:100vh;background:linear-gradient(90deg,#f1f3fa 0 63%,#f7e6dc 63% 100%);display:flex;justify-content:center;align-items:center;overflow:hidden;position:relative}
body:before{content:"";position:absolute;left:-120px;bottom:-140px;width:360px;height:360px;border-radius:50%;background:#c8cfde}
body:after{content:"";position:absolute;right:-80px;top:-80px;width:260px;height:260px;border-radius:50%;background:#f3d7ba;opacity:.95}
.shell{width:min(1200px,94vw);display:grid;grid-template-columns:1fr 480px;gap:44px;align-items:center;position:relative;z-index:1}
.branding{padding:10px 20px;color:#1f2937}
.pill{display:inline-block;padding:8px 14px;background:#d7dbef;color:#5255af;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:16px}
.logo-card{display:inline-flex;align-items:center;background:white;padding:10px 16px;border-radius:12px;box-shadow:0 6px 20px rgba(15,23,42,.08);margin-bottom:16px}
.logo-card img{height:54px;width:auto;display:block}
.branding h1{font-size:58px;line-height:1.05;color:#1f2a3f;margin-bottom:12px;max-width:480px}
.branding p{font-size:34px;color:var(--muted);max-width:560px}
.login-container{background:white;padding:34px;border-radius:20px;box-shadow:0 22px 34px rgba(15,23,42,.14)}
.login-container h2{text-align:center;font-size:42px;color:#1f2a3f;margin-bottom:22px}
.form-group{margin-bottom:15px}label{display:block;font-size:14px;color:#4b5563;margin-bottom:6px;font-weight:500}
input,select{width:100%;padding:13px 14px;border-radius:12px;border:1px solid #d9dfeb;background:#edf1fb;font-size:14px}
button{width:100%;padding:13px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:white;font-weight:700;font-size:18px;cursor:pointer;margin-top:4px}
.error{color:#dc2626;text-align:center;margin-bottom:12px;font-size:13px}
.success{color:#166534;text-align:center;margin-bottom:12px;font-size:13px;word-break:break-word}
.form-links{display:flex;justify-content:space-between;gap:16px;margin-top:10px;font-size:13px}
.form-links a{color:#1d4ed8;text-decoration:none;font-weight:500}
.form-links a:hover{text-decoration:underline}
@media(max-width:1024px){.shell{grid-template-columns:1fr;gap:18px}.branding h1{font-size:42px}.branding p{font-size:24px}.login-container h2{font-size:36px}.pill{font-size:16px}label,input,select,button{font-size:18px}}
</style>
</head>

<body>
<div class="shell">
<div class="branding">
    <span class="pill">📚 Trisha Library Suite</span>
    <div class="logo-card"><img src="https://trishaedu.com/Trisha-Logo.png" alt="Trisha Logo"></div>
    <h1>Trisha Library
Management</h1>
    <p>Manage catalog, issue/returns, fines, and student access from one modern dashboard.</p>
</div>
<div class="login-container">
        <h2>
            <?php if ($mode === 'forgot'): ?>Forgot Password<?php elseif ($mode === 'reset'): ?>Reset Password<?php else: ?>Login to Continue<?php endif; ?>
        </h2>

        <?php if ($error !== ""): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if ($mode === 'forgot'): ?>
            <form method="post">
                <div class="form-group">
                    <label>Registered Email</label>
                    <input type="email" name="email" required>
                </div>
                <button type="submit" name="forgot_password">Send Reset Link</button>
                <div class="form-links">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
        <?php elseif ($mode === 'reset' && $token !== ''): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_password">Update Password</button>
                <div class="form-links">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label>Login As</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="faculty">Faculty</option>
                        <option value="student">Student</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="identifier" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" name="login">Login</button>
                <div class="form-links">
                    <span></span>
                    <a href="index.php?action=forgot">Forgot Password?</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
