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
    background: #f4f6f9;
}

/* Layout Wrapper */
.wrapper {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: #2c3e50;
    padding: 25px 15px;
    color: white;
    transition: all 0.3s ease;
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

.sidebar.collapsed {
    margin-left: -240px;
}

/* Main */
.main {
    flex: 1;
    padding: 30px;
    transition: all 0.3s ease;
}
/* Topbar */
.topbar {
    display: flex;
    align-items: center;
    gap: 20px; /* clean spacing between toggle & title */
    margin-bottom: 30px;
}

.toggle-btn {
    background: #2c3e50;
    border: none;
    color: white;
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}.page-title {
    font-size: 22px;
    font-weight: 600;
    color: #2c3e50;
}

/* Card */
.card {
    background: white;
    border-radius: 18px;
    padding: 35px;
    max-width: 750px;
    margin: auto;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
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
    padding: 11px 14px;
    border-radius: 10px;
    border: 1px solid #ddd;
    font-size: 14px;
    background: #f9fafc;
    transition: 0.3s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
}

/* Buttons */
.button-group {
    margin-top: 25px;
    display: flex;
    gap: 15px;
}

.btn {
    padding: 11px 22px;
    border-radius: 10px;
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
    background: linear-gradient(135deg, #27ae60, #219150);
    color: white;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(39,174,96,0.3);
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
.left-controls {
    display: flex;
    align-items: center;
    gap: 18px;
}

.page-title {
    font-size: 22px;
    font-weight: 600;
    color: #2c3e50;
}

</style>
</head>

<body>

<div class="wrapper">

    <div class="sidebar" id="sidebar">
        <h3>Admin Panel</h3>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
        <a href="add_book.php"><i class="fas fa-plus"></i> Add Book</a>
        <a href="issue_book.php"><i class="fas fa-hand-holding"></i> Issue Book</a>
        <a href="issued_book.php"><i class="fas fa-clipboard-list"></i> Issued Books</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">

        <div class="topbar">
    <div class="left-controls">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <span class="page-title">Add New Book</span>
    </div>
</div>

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

</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>

</body>
</html>