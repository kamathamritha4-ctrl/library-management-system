<?php
include("config/config.php");

if(isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM users WHERE name='$username' AND password='$password'";
    $result = $conn->query($sql);

    if($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['name'] = $row['name'];

       header("Location: /Library_Management_Project/admin/dashboard.php");
exit();

    } else {
        $error = "Invalid Username or Password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Form</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #eaeaea;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-container {
    background-color: #ffffff;
    padding: 40px;
    width: 400px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.login-container h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 16px;
}

.form-group input {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background-color: #125ca1;
}
</style>
</head>

<body>

<div class="login-container">
    <h2>Library Login</h2>

    <?php if(isset($error)) { ?>
        <p style="color:red; text-align:center;"><?php echo $error; ?></p>
    <?php } ?>

    <form method="post">
        
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" name="login">Login</button>

    </form>
</div>

</body>
</html>
