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
:root{--bg:#eef2ff;--card:#ffffff;--text:#1f2937;--muted:#6b7280;--primary:#4f46e5;--primary2:#4338ca;--ring:rgba(79,70,229,.22)}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
body{min-height:100vh;background:radial-gradient(circle at top right,#bfdbfe 0%,#e0e7ff 28%,#f8fafc 72%);display:flex;justify-content:center;align-items:center;position:relative;overflow:hidden}
body:before,body:after{content:"";position:absolute;border-radius:50%;filter:blur(2px)}
body:before{width:540px;height:540px;background:linear-gradient(135deg,#6366f1,#22d3ee);top:-180px;right:-140px;opacity:.18}
body:after{width:460px;height:460px;background:linear-gradient(135deg,#34d399,#3b82f6);bottom:-170px;left:-130px;opacity:.14}
.shell{width:min(1040px,94vw);display:grid;grid-template-columns:1.1fr .9fr;gap:28px;align-items:center;position:relative;z-index:1}
.branding{color:var(--text);padding:10px}
.branding .pill{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(79,70,229,.1);border:1px solid rgba(79,70,229,.14);border-radius:999px;color:#3730a3;font-size:12px;font-weight:600;margin-bottom:16px}
.branding h1{font-size:40px;line-height:1.1;margin-bottom:10px}
.branding p{color:var(--muted);font-size:15px;max-width:460px}
.login-container{background:var(--card);padding:34px;border-radius:22px;box-shadow:0 20px 45px rgba(15,23,42,.14);border:1px solid rgba(255,255,255,.55)}
.login-container h2{text-align:center;margin-bottom:24px;font-weight:700;color:var(--text)}
.form-group{margin-bottom:16px}
label{display:block;margin-bottom:7px;font-size:13px;color:#4b5563;font-weight:500}
input,select{width:100%;padding:12px 13px;border-radius:11px;border:1px solid #dbe3f3;background:#f8fafc;font-size:14px;outline:none;transition:.2s}
input:focus,select:focus{border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px var(--ring)}
button{width:100%;padding:13px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff;border:none;border-radius:12px;cursor:pointer;font-size:15px;font-weight:600;transition:.22s}
button:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(79,70,229,.28)}
.error{color:#dc2626;text-align:center;margin-bottom:13px;font-size:13px}
@media(max-width:900px){.shell{grid-template-columns:1fr;gap:14px}.branding{text-align:center}.branding p{margin:auto}.branding h1{font-size:32px}}
</style>
</head>

<body>
<div class="shell">
<div class="branding">
    <span class="pill">📚 Smart Library Suite</span>
    <h1>Library Management</h1>
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