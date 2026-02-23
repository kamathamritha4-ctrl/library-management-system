<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['issue'])) {

    $user_id = $_POST['user_id'];
    $accession_no = $_POST['accession_no'];
    $issue_date = date("Y-m-d");
    $due_date = date("Y-m-d", strtotime($issue_date . " +15 days"));

    $sql = "INSERT INTO issued_books 
            (user_id, accession_no, issue_date, due_date)
            VALUES
            ('$user_id', '$accession_no', '$issue_date', '$due_date')";

    if($conn->query($sql)) {
        $success = "Book Issued Successfully!";
    } else {
        $error = "SQL Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Issue Book</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    display: flex;
    min-height: 100vh;
    background: #f4f6f9;
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: #2c3e50;
    padding: 25px 15px;
    color: white;
}

.sidebar h3 {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    padding: 12px 15px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #ecf0f1;
    border-radius: 8px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: #34495e;
}

/* Main */
.main {
    flex: 1;
    padding: 40px;
}

.card {
    background: white;
    border-radius: 15px;
    padding: 35px;
    max-width: 650px;
    margin: auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.card h2 {
    margin-bottom: 25px;
    color: #333;
}

/* Form */
.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-size: 14px;
    background: #fafafa;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    background: white;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
}

.readonly {
    background: #f1f3f5;
    color: #555;
}

/* Button */
.btn {
    margin-top: 10px;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    background: #3498db;
    color: white;
    transition: 0.3s;
}

.btn:hover {
    background: #2980b9;
}

/* Alerts */
.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>
</head>

<body>

<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
    <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
    <a href="issue_book.php"><i class="fas fa-hand-holding"></i> Issue Book</a>
    <a href="issued_book.php"><i class="fas fa-clipboard-list"></i> Issued Books</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="card">
        <h2>📖 Issue Book</h2>

        <?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

        <form method="post">

            <div class="form-group">
                <label>User ID</label>
                <input type="number" name="user_id" required>
            </div>

            <div class="form-group">
                <label>Accession Number</label>
                <input type="number" name="accession_no" required>
            </div>

            <div class="form-group">
                <label>Issue Date</label>
                <input type="text" value="<?php echo date('Y-m-d'); ?>" class="readonly" readonly>
            </div>

            <div class="form-group">
                <label>Due Date (15 Days)</label>
                <input type="text" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" class="readonly" readonly>
            </div>

            <button type="submit" name="issue" class="btn">
                <i class="fas fa-check-circle"></i> Issue Book
            </button>

        </form>
    </div>
</div>

</body>
</html>