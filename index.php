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
<title>Library Login</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    height: 100vh;
    background: #f4f6f9;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}

/* Decorative background shapes */
body::before {
    content: "";
    position: absolute;
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, #2f80ed, #56ccf2);
    border-radius: 50%;
    top: -150px;
    right: -150px;
    opacity: 0.15;
}

body::after {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    background: linear-gradient(135deg, #27ae60, #6fcf97);
    border-radius: 50%;
    bottom: -150px;
    left: -150px;
    opacity: 0.12;
}

/* Login Card */
.login-container {
    position: relative;
    background: white;
    width: 420px;
    padding: 45px;
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.12);
    z-index: 1;
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-container h2 {
    text-align: center;
    margin-bottom: 30px;
    font-weight: 600;
    color: #2c3e50;
}

.login-container h2::after {
    content: "";
    display: block;
    width: 70px;
    height: 4px;
    background: #2f80ed;
    margin: 12px auto 0;
    border-radius: 10px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #555;
}

input, select {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
    outline: none;
    transition: 0.3s;
    background: #f9fafc;
}

input:focus, select:focus {
    border-color: #2f80ed;
    box-shadow: 0 0 0 3px rgba(47,128,237,0.15);
    background: white;
}

button {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #2f80ed, #1f6ed4);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: 0.3s;
}

button:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(47,128,237,0.3);
}

.error {
    color: #e74c3c;
    text-align: center;
    margin-bottom: 15px;
}
.branding {
    position: absolute;
    left: 80px;
    top: 45%;
    transform: translateY(-50%);
    color: #2c3e50;
}

.branding h1 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.branding p {
    font-size: 14px;
    color: #6c757d;
}</style>
</head>

<body>

<div class="branding">
    <h1>Library Management</h1>
    <p>Academic Resource Portal</p>
</div>
<div class="right-panel">
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