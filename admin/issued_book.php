<?php
include("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: /Library_Management_Project/index.php");
    exit();
}

// Handle Return
if(isset($_GET['return_id'])) {

    $id = $_GET['return_id'];

    // Get issue record
    $result = $conn->query("SELECT * FROM issued_books WHERE id = $id");
    $row = $result->fetch_assoc();

    $due_date = $row['due_date'];
    $today = date("Y-m-d");

    $fine = 0;

    if($today > $due_date) {
        $days = (strtotime($today) - strtotime($due_date)) / (60 * 60 * 24);
        $fine = $days * 5;
    }

    $conn->query("UPDATE issued_books 
                  SET return_date='$today', fine='$fine' 
                  WHERE id=$id");

    header("Location: issue_book.php");
    exit();
}

// Fetch issued books
$issues = $conn->query("
    SELECT issued_books.*, books.title 
    FROM issued_books
    JOIN books ON issued_books.accession_no = books.accession_no
    WHERE issued_books.return_date IS NULL
");?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Issue Book Detail</title>
<style>
body { font-family: Arial; margin: 30px; background:#f8f9fa; }
.container { background:white; padding:20px; border-radius:5px; }
table { width:100%; border-collapse:collapse; }
table th, table td { border:1px solid #ddd; padding:10px; }
table th { background:#f1f1f1; }
.btn { padding:5px 10px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; }
.btn:hover { background:#0056b3; }
</style>
</head>
<body>

<h1>Issued Book Detail</h1>

<div class="container">
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
    if($issues->num_rows > 0) {
        while($row = $issues->fetch_assoc()) {

            $status = $row['return_date'] ? "Returned" : 
                "<a href='?return_id={$row['id']}'><button class='btn'>Return</button></a>";

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

</body>
</html>
