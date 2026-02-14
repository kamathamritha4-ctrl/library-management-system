<?php
include("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: /Library_Management_Project/index.php");
    exit();
}

// Fetch books
$books = $conn->query("SELECT * FROM books ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management System</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #f5f6fa;
}

.header {
    background-color: #1976d2;
    color: white;
    padding: 20px;
    font-size: 26px;
    font-weight: bold;
}

.sidebar {
    width: 200px;
    background-color: white;
    position: fixed;
    top: 80px;
    bottom: 0;
    padding-top: 20px;
    border-right: 1px solid #ddd;
}

.sidebar a {
    display: block;
    padding: 12px 20px;
    text-decoration: none;
    color: #1976d2;
    font-weight: bold;
}

.sidebar a:hover {
    background-color: #f1f1f1;
}

.main {
    margin-left: 220px;
    padding: 30px;
}

.add-btn {
    float: right;
    background-color: #26a69a;
    color: white;
    padding: 8px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.add-btn:hover {
    background-color: #1e8e82;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: white;
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background-color: #f1f1f1;
}
</style>
</head>

<body>

<div class="header">
    Library Management System
</div>

<div class="sidebar">
    <a href="dashboard.php">Dashboard</a>
    <a href="add_book.php">Books</a>
    <a href="issue_book.php">Issue Books</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main">
    <h1>
        Manage Books
        <a href="add_book.php">
            <button class="add-btn">Add Book</button>
        </a>
    </h1>

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
                <th>Total Copies</th>
                <th>Quantity</th>
                <th>Date Added</th>
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
                        <td>{$row['price']}</td>
                        <td>{$row['total_copies']}</td>
                        <td>{$row['quantity']}</td>
                        <td>{$row['date_of_accession']}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='10'>No books found</td></tr>";
        }
        ?>

        </tbody>
    </table>

</div>

</body>
</html>
