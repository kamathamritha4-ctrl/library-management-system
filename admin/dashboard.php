<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// ===== Fetch Dashboard Data =====

$total_books = $conn->query("SELECT COUNT(*) as total FROM books")
                    ->fetch_assoc()['total'] ?? 0;

$returned_books = $conn->query("
    SELECT COUNT(*) as total 
    FROM issued_books 
    WHERE return_date IS NOT NULL
")->fetch_assoc()['total'] ?? 0;

$not_returned = $conn->query("
    SELECT COUNT(*) as total 
    FROM issued_books 
    WHERE return_date IS NULL
")->fetch_assoc()['total'] ?? 0;

$overdue = $conn->query("
    SELECT COUNT(*) as total 
    FROM issued_books 
    WHERE return_date IS NULL 
    AND due_date < CURDATE()
")->fetch_assoc()['total'] ?? 0;

$students = $conn->query("
    SELECT COUNT(*) as total 
    FROM users 
    WHERE role = 'student'
")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Library Dashboard</title>
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

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #2c3e50;
    color: white;
    padding: 25px 15px;
}

.sidebar h3 {
    margin-bottom: 30px;
    text-align: center;
    font-weight: 600;
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

/* ===== Main Content ===== */
.main {
    flex: 1;
    padding: 40px;
}

.main h2 {
    margin-bottom: 30px;
    color: #333;
}

.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
}

.card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card-content h3 {
    font-size: 28px;
}

.card-content p {
    font-size: 14px;
    color: #666;
}

.icon {
    font-size: 30px;
}

.blue { border-left: 5px solid #2f80ed; }
.green { border-left: 5px solid #27ae60; }
.yellow { border-left: 5px solid #f2c94c; }
.red { border-left: 5px solid #eb5757; }

</style>
</head>

<body>

<!-- ===== Sidebar ===== -->
<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
    <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
    <a href="issue_book.php"><i class="fas fa-hand-holding"></i> Issue Book</a>
    <a href="issued_book.php"><i class="fas fa-clipboard-list"></i> Issued Books</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- ===== Main Content ===== -->
<div class="main">
    <h2>📊 Library Dashboard</h2>

    <div class="dashboard">

        <div class="card blue">
            <div class="card-content">
                <h3><?php echo $students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="icon"><i class="fas fa-user-graduate"></i></div>
        </div>

        <div class="card green">
            <div class="card-content">
                <h3><?php echo $returned_books; ?></h3>
                <p>Returned Books</p>
            </div>
            <div class="icon"><i class="fas fa-book"></i></div>
        </div>

        <div class="card blue">
            <div class="card-content">
                <h3><?php echo $total_books; ?></h3>
                <p>Total Books</p>
            </div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
        </div>

        <div class="card yellow">
            <div class="card-content">
                <h3><?php echo $not_returned; ?></h3>
                <p>Books Not Returned</p>
            </div>
            <div class="icon"><i class="fas fa-undo"></i></div>
        </div>

        <div class="card red">
            <div class="card-content">
                <h3><?php echo $overdue; ?></h3>
                <p>Books Overdue</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>

    </div>
</div>

</body>
</html>