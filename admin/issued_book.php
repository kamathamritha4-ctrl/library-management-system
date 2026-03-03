<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Return
if(isset($_GET['return_id'])) {

    $id = $_GET['return_id'];

    $result = $conn->query("SELECT * FROM issued_books WHERE id = $id");
    $row = $result->fetch_assoc();

    $due_date = $row['due_date'];
    $today = date("Y-m-d");

    $fine = 0;

    if($today > $due_date) {
        $days = floor((strtotime($today) - strtotime($due_date)) / (60 * 60 * 24));
        $fine = $days * 5;
    }

    $conn->query("UPDATE issued_books 
                  SET return_date='$today', fine='$fine' 
                  WHERE id=$id");

    header("Location: issued_book.php");
    exit();
}

// Fetch only active issued books
$issues = $conn->query("
    SELECT issued_books.*, books.title 
    FROM issued_books
    JOIN books ON issued_books.accession_no = books.accession_no
    WHERE issued_books.return_date IS NULL
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Issued Books</title>
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
    background: #f4f6f9;
}

.wrapper {
    display: flex;
    min-height: 100vh;
}

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #2c3e50;
    padding: 25px 15px;
    color: white;
    transition: 0.3s ease;
    overflow: hidden;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.collapse-btn {
    background: transparent;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 10px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #ecf0f1;
    border-radius: 8px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: #34495e;
}

/* Collapsed */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed h3 {
    display: none;
}

.sidebar.collapsed a span {
    display: none;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
}

/* ===== Main ===== */
.main {
    flex: 1;
    padding: 40px;
}

.main h2 {
    margin-bottom: 25px;
    color: #333;
}

/* Card */
.table-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 12px;
    text-align: left;
    font-size: 14px;
}

table th {
    background: #f8f9fa;
    font-weight: 600;
}

table tr {
    border-bottom: 1px solid #eee;
}

table tr:hover {
    background: #f9f9f9;
}

/* Return Button */
.return-btn {
    padding: 6px 12px;
    font-size: 12px;
    background: #e67e22;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    transition: 0.3s;
}

.return-btn:hover {
    background: #d35400;
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include("../includes/sidebar1.php"); ?>

    <!-- Main -->
    <div class="main">

        <h2>📚 Issued Books</h2>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Accession No</th>
                        <th>Book Name</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Fine</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                if($issues && $issues->num_rows > 0) {
                    while($row = $issues->fetch_assoc()) {

                        $status = "<a href='?return_id={$row['id']}' 
                                    class='return-btn'
                                    onclick=\"return confirm('Mark this book as returned?')\">
                                    Return
                                   </a>";

                        echo "<tr>
                                <td>{$row['accession_no']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['issue_date']}</td>
                                <td>{$row['due_date']}</td>
                                <td>₹ {$row['fine']}</td>
                                <td>$status</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No issued books found</td></tr>";
                }
                ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>

</body>
</html>