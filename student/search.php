<?php
include("../config/config.php");

if(!isset($_SESSION['role']) || 
   ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty')) {
    header("Location: ../index.php");
    exit();
}

$search = "";

if(isset($_GET['search'])) {
    $search = $_GET['search'];
    $books = $conn->query("
        SELECT * FROM books 
        WHERE title LIKE '%$search%' 
        OR author LIKE '%$search%' 
        OR category LIKE '%$search%'
    ");
} else {
    $books = null;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Search Book</title>

<style>
body {
    margin:0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: url('https://www.shutterstock.com/image-vector/artificial-intelligence-digital-education-visualized-600nw-2653874705.jpg') no-repeat center center fixed;
    background-size: cover;
}

.overlay {
    min-height:100vh;
    background: rgba(0,0,0,0.65);
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:60px 20px;
    color:white;
}

.college-title {
    font-size:28px;
    font-weight:600;
    margin-bottom:30px;
    letter-spacing:1px;
    text-align:center;
}

.search-box {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(8px);
    padding:25px 30px;
    border-radius:12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    margin-bottom:40px;
}

.search-box input {
    padding:12px 15px;
    width:300px;
    border:none;
    border-radius:8px;
    font-size:15px;
    outline:none;
}

.search-box button {
    padding:12px 18px;
    border:none;
    border-radius:8px;
    background:#0d6efd;
    color:white;
    font-weight:500;
    cursor:pointer;
    margin-left:10px;
    transition:0.3s;
}

.search-box button:hover {
    background:#0b5ed7;
}

.container {
    width:100%;
    max-width:1000px;
    background:white;
    color:black;
    padding:25px;
    border-radius:12px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
}

.container h3 {
    margin-top:0;
    margin-bottom:20px;
}

table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:12px;
    border-bottom:1px solid #eee;
}

th {
    background:#f8f9fa;
    font-weight:600;
}

tr:hover {
    background:#f2f2f2;
}

.badge {
    padding:5px 10px;
    border-radius:20px;
    color:white;
    font-size:12px;
    font-weight:500;
}

.available {
    background:#198754;
}

.not-available {
    background:#dc3545;
}
</style>
</head>

<body>

<div class="overlay">

<h2>Trisha Vidya College of Commerce & Management</h2>

<div class="search-box">
    <form method="get">
        <input type="text" name="search" placeholder="Search by Title, Author, Category" required>
        <button type="submit">Search</button>
    </form>
</div>

<?php if($books) { ?>

<div class="container">
    <h3>Search Results</h3>

    <table>
        <thead>
            <tr>
                <th>Book Name</th>
                <th>Accession No</th>
                <th>Category</th>
                <th>Author</th>
                <th>Available Copies</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

        <?php
        if($books->num_rows > 0) {
            while($row = $books->fetch_assoc()) {

                $statusClass = ($row['quantity'] > 0) ? "available" : "not-available";
                $statusText = ($row['quantity'] > 0) ? "Available" : "Not Available";

                echo "<tr>
                        <td>{$row['title']}</td>
                        <td>{$row['accession_no']}</td>
                        <td>{$row['category']}</td>
                        <td>{$row['author']}</td>
                        <td>{$row['quantity']}</td>
                        <td><span class='badge $statusClass'>$statusText</span></td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No books found</td></tr>";
        }
        ?>

        </tbody>
    </table>
</div>

<?php } ?>

</div>

</body>
</html>