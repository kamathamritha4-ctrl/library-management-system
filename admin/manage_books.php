<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Delete logic
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM books WHERE id=$id");
    header("Location: manage_books.php");
    exit();
}

// Fetch books
$books = $conn->query("SELECT * FROM books ORDER BY id");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manage Books</title>
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

.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.main-header h2 {
    font-weight: 600;
    color: #333;
}

.add-btn {
    background: #27ae60;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.add-btn:hover {
    background: #219150;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

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

/* Buttons */
.action-btn {
    padding: 6px 10px;
    font-size: 12px;
    text-decoration: none;
    border-radius: 6px;
    color: white;
    margin-right: 5px;
}

.edit-btn {
    background: #f39c12;
}

.edit-btn:hover {
    background: #d68910;
}

.delete-btn {
    background: #e74c3c;
}

.delete-btn:hover {
    background: #c0392b;
}
</style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h3>Admin Panel</h3>
    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
    <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
    <a href="issue_book.php"><i class="fas fa-hand-holding"></i> Issue Book</a>
    <a href="issued_book.php"><i class="fas fa-clipboard-list"></i> Issued Books</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Main Content -->
<div class="main">

    <div class="main-header">
        <h2>📚 Manage Books</h2>
        <a href="add_book.php">
            <button class="add-btn"><i class="fas fa-plus"></i> Add Book</button>
        </a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Accession No</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Publisher</th>
                    <th>Edition</th>
                    <th>Price</th>
                    <th>Total</th>
                    <th>Available</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

            <?php
            if($books && $books->num_rows > 0) {
                while($row = $books->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['accession_no']}</td>
                            <td>{$row['title']}</td>
                            <td>{$row['author']}</td>
                            <td>{$row['category']}</td>
                            <td>{$row['publisher']}</td>
                            <td>{$row['edition']}</td>
                            <td>₹ {$row['price']}</td>
                            <td>{$row['total_copies']}</td>
                            <td>{$row['quantity']}</td>
                            <td>{$row['date_of_accession']}</td>
                            <td>
                                <a href='edit_book.php?id={$row['id']}' class='action-btn edit-btn'>Edit</a>
                                <a href='manage_books.php?delete={$row['id']}' 
                                   class='action-btn delete-btn'
                                   onclick=\"return confirm('Are you sure you want to delete this book?')\">
                                   Delete
                                </a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='11'>No books found</td></tr>";
            }
            ?>

            </tbody>
        </table>
    </div>

</div>

</body>
</html>