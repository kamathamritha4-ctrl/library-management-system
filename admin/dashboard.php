<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$total_books = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'] ?? 0;
$returned_books = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE return_date IS NOT NULL")->fetch_assoc()['total'] ?? 0;
$not_returned = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE return_date IS NULL")->fetch_assoc()['total'] ?? 0;
$overdue = $conn->query("SELECT COUNT(*) as total FROM issued_books WHERE return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['total'] ?? 0;
$students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Library Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper">
    <?php include("../includes/sidebar1.php"); ?>
    <div class="main">
        <div class="page-header">
            <h2>📊 Library Dashboard</h2>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div><h3><?php echo $students; ?></h3><p>Total Students</p></div>
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-card navy">
                <div><h3><?php echo $returned_books; ?></h3><p>Returned Books</p></div>
                <div class="icon"><i class="fas fa-book"></i></div>
            </div>
            <div class="stat-card">
                <div><h3><?php echo $total_books; ?></h3><p>Total Books</p></div>
                <div class="icon"><i class="fas fa-layer-group"></i></div>
            </div>
            <div class="stat-card gold">
                <div><h3><?php echo $not_returned; ?></h3><p>Books Not Returned</p></div>
                <div class="icon"><i class="fas fa-undo"></i></div>
            </div>
            <div class="stat-card red">
                <div><h3><?php echo $overdue; ?></h3><p>Books Overdue</p></div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>
<script>
function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("collapsed"); }
</script>
</body>
</html>
