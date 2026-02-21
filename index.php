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
body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da') no-repeat center center fixed;
    background-size: cover;
    display: flex;
    justify-content: flex-end; /* Move to right side */
    align-items: center;
    height: 100vh;
    padding-right: 120px;
}

body::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        to right,
        rgba(0,0,0,0.75) 0%,
        rgba(0,0,0,0.65) 40%,
        rgba(0,0,0,0.4) 100%
    );
    z-index: 0;
}

.login-container {
    position: relative;
    z-index: 1;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(18px);
    padding: 50px;
    width: 420px;
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
    color: white;
    animation: fadeIn 0.8s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    text-align:center;
    margin-bottom:35px;
    font-weight:600;
    letter-spacing:1px;
}

.form-group {
    margin-bottom:20px;
}

label {
    display:block;
    margin-bottom:8px;
    font-weight:500;
}

input, select {
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
    font-size:14px;
    outline:none;
}

button {
    width:100%;
    padding:14px;
    background:#0d6efd;
    color:white;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-size:15px;
    font-weight:500;
    transition:0.3s;
}

button:hover {
    background:#0b5ed7;
    transform: translateY(-2px);
}

.error {
    color:#ff6b6b;
    text-align:center;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="login-container">
    <h2>Library Login</h2>

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

</body>
</html>