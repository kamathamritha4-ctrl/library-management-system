<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['save'])) {

    $accession_no = $_POST['book_id'];
    $category = $_POST['category'];
    $author = $_POST['author'];
    $title = $_POST['title'];
    $publisher = $_POST['publisher'];
    $year = NULL; 
    $price = $_POST['price'];
    $total_copies = $_POST['quantity'];
    $quantity = $_POST['quantity'];
    $edition = $_POST['edition'];
    $supplier = NULL;
    $remarks = NULL;
    $date_of_accession = date("Y-m-d");

    $sql = "INSERT INTO books 
    (accession_no, category, author, title, publisher, year, price, total_copies, quantity, edition, supplier, remarks, date_of_accession)
    VALUES
    ('$accession_no', '$category', '$author', '$title', '$publisher', '$year', '$price', '$total_copies', '$quantity', '$edition', '$supplier', '$remarks', '$date_of_accession')";

    if($conn->query($sql)) {
        $success = "Book Added Successfully!";
    } else {
        $error = "SQL Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add Book</title>
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
    max-width: 750px;
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
    transition: 0.3s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
}

/* Buttons */
.button-group {
    margin-top: 25px;
    display: flex;
    gap: 15px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: 0.3s;
}

.btn-reset {
    background: #bdc3c7;
}

.btn-reset:hover {
    background: #95a5a6;
}

.btn-save {
    background: #27ae60;
    color: white;
}

.btn-save:hover {
    background: #219150;
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
        <h2>📘 Add New Book</h2>

        <?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

        <form method="post">

            <div class="form-group">
                <label>Accession Number</label>
                <input type="text" name="book_id" required>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required>
            </div>

            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" required>
            </div>

            <div class="form-group">
                <label>Publisher</label>
                <input type="text" name="publisher" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" required>
            </div>

            <div class="form-group">
                <label>Edition</label>
                <input type="text" name="edition">
            </div>

            <div class="form-group">
                <label>Price (₹)</label>
                <input type="number" step="0.01" name="price">
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" required>
            </div>

            <div class="button-group">
                <button type="reset" class="btn btn-reset">Reset</button>
                <button type="submit" name="save" class="btn btn-save">Save Book</button>
            </div>

        </form>
    </div>
</div>

</body>
</html>