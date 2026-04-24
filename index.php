<?php
include("config/config.php");


$error = "";

if(isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = md5($_POST['password']); // keep if your DB uses md5
    $role = $_POST['role'];

    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE name = ? 
        AND password = ? 
        AND role = ?
    ");

    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['name'] = $row['name'];

        if($role == 'admin') {
            header("Location: admin/dashboard.php");
        } 
        elseif($role == 'faculty') {
            header("Location: faculty/search.php");
        } 
        else {
            header("Location: student/search.php");
        }
        exit();

    } else {
        $error = "Invalid Credentials";
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
        <h2>Login to Continue</h2>

        <?php 
        if(isset($error) && $error != "") {
            echo "<p class='error'>$error</p>";
        }
        ?>

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
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" name="login">Login</button>

        </form>
    </div>
</div>
</body>
</html>