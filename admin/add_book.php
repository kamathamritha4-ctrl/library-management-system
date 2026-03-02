<?php
require_once("../vendor/autoload.php");
include_once("../config/config.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

/* =====================================
   BULK IMPORT (Excel + CSV)
===================================== */
if(isset($_POST['import'])) {

    if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {

        try {

            $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            foreach($rows as $index => $row) {

                if($index == 0) continue; // Skip header row

                $accession_no = $row[0];
                $category     = $row[1];
                $author       = $row[2];
                $title        = $row[3];
                $publisher    = $row[4];
                $price        = $row[5];
                $quantity     = $row[6];
                $edition      = $row[7];
                $date         = date("Y-m-d");

                // Check duplicate accession
                $check = $conn->prepare("SELECT id FROM books WHERE accession_no = ?");
                $check->bind_param("i", $accession_no);
                $check->execute();
                $check->store_result();

                if($check->num_rows > 0) continue;

                $stmt = $conn->prepare("INSERT INTO books 
                (accession_no, category, author, title, publisher, year, price, total_copies, quantity, edition, supplier, remarks, date_of_accession)
                VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, NULL, ?)");

                $stmt->bind_param(
                    "issssdiis",
                    $accession_no,
                    $category,
                    $author,
                    $title,
                    $publisher,
                    $price,
                    $quantity,
                    $quantity,
                    $edition,
                    $date
                );

                $stmt->execute();
            }

            $success = "Bulk import completed successfully!";

        } catch(Exception $e) {
            $error = "Import failed: " . $e->getMessage();
        }
    }
}

/* =====================================
   SINGLE BOOK INSERT
===================================== */
if(isset($_POST['save'])) {

    $accession_no = $_POST['book_id'];
    $category     = $_POST['category'];
    $author       = $_POST['author'];
    $title        = $_POST['title'];
    $publisher    = $_POST['publisher'];
    $price        = $_POST['price'];
    $quantity     = $_POST['quantity'];
    $edition      = $_POST['edition'];
    $date         = date("Y-m-d");

    // Check duplicate accession
    $check = $conn->prepare("SELECT id FROM books WHERE accession_no = ?");
    $check->bind_param("i", $accession_no);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0) {
        $error = "Accession number already exists!";
    } else {

        $stmt = $conn->prepare("INSERT INTO books 
        (accession_no, category, author, title, publisher, year, price, total_copies, quantity, edition, supplier, remarks, date_of_accession)
        VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, NULL, NULL, ?)");

        $stmt->bind_param(
            "issssdiis",
            $accession_no,
            $category,
            $author,
            $title,
            $publisher,
            $price,
            $quantity,
            $quantity,
            $edition,
            $date
        );

        if($stmt->execute()) {
            $success = "Book Added Successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
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
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
body { background:#f4f6f9; }

.wrapper { display:flex; min-height:100vh; }

.sidebar {
    width:240px;
    background:#2c3e50;
    padding:25px 15px;
    color:white;
}

.sidebar h3 { text-align:center; margin-bottom:30px; }

.sidebar a {
    display:block;
    padding:12px 15px;
    margin-bottom:10px;
    text-decoration:none;
    color:#ecf0f1;
    border-radius:8px;
    transition:0.3s;
}

.sidebar a:hover { background:#34495e; }

.main { flex:1; padding:30px; }

.topbar {
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:30px;
}

.toggle-btn {
    background:#2c3e50;
    border:none;
    color:white;
    padding:10px 14px;
    border-radius:8px;
    cursor:pointer;
}

.page-title {
    font-size:22px;
    font-weight:600;
    color:#2c3e50;
}

.card {
    background:white;
    border-radius:18px;
    padding:35px;
    max-width:900px;
    margin:auto;
    box-shadow:0 15px 40px rgba(0,0,0,0.08);
}

.form-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}

.form-group { margin-bottom:18px; }

.form-group label {
    display:block;
    margin-bottom:6px;
    font-weight:500;
    font-size:14px;
}

.form-group input {
    width:100%;
    padding:11px 14px;
    border-radius:10px;
    border:1px solid #ddd;
    background:#f9fafc;
}

.form-group input:focus {
    border-color:#4f46e5;
    outline:none;
    box-shadow:0 0 0 3px rgba(79,70,229,0.15);
}

.button-group {
    margin-top:25px;
    display:flex;
    gap:15px;
}

.btn {
    padding:11px 22px;
    border-radius:10px;
    border:none;
    cursor:pointer;
    font-weight:600;
}

.btn-save {
    background:linear-gradient(135deg,#4f46e5,#6366f1);
    color:white;
}

.btn-reset {
    background:#bdc3c7;
}

.alert-success {
    background:#d4edda;
    color:#155724;
    padding:10px 15px;
    border-radius:8px;
    margin-bottom:20px;
}

.alert-error {
    background:#f8d7da;
    color:#721c24;
    padding:10px 15px;
    border-radius:8px;
    margin-bottom:20px;
}
</style>
</head>

<body>

<div class="wrapper">

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

<div class="topbar">
    <button class="toggle-btn">
        <i class="fas fa-bars"></i>
    </button>
    <span class="page-title">Add New Book</span>
</div>

<div class="card">

<h2>📘 Add New Book</h2>

<?php if($success) echo "<div class='alert-success'>$success</div>"; ?>
<?php if($error) echo "<div class='alert-error'>$error</div>"; ?>

<!-- BULK IMPORT -->
<hr style="margin:25px 0;">
<h3>📂 Bulk Import (Excel / CSV)</h3>

<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
    </div>
    <button type="submit" name="import" class="btn btn-save">
        Import File
    </button>
</form>

<hr style="margin:25px 0;">

<!-- SINGLE ADD FORM -->
<form method="post">

<div class="form-row">
    <div class="form-group">
        <label>Accession Number</label>
        <input type="number" name="book_id" required>
    </div>
    <div class="form-group">
        <label>Edition</label>
        <input type="text" name="edition">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" required>
    </div>
    <div class="form-group">
        <label>Author</label>
        <input type="text" name="author" required>
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Publisher</label>
        <input type="text" name="publisher" required>
    </div>
    <div class="form-group">
        <label>Category</label>
        <input type="text" name="category" required>
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Price (₹)</label>
        <input type="number" step="0.01" name="price">
    </div>
    <div class="form-group">
        <label>Quantity</label>
        <input type="number" name="quantity" required>
    </div>
</div>

<div class="button-group">
    <button type="reset" class="btn btn-reset">Reset</button>
    <button type="submit" name="save" class="btn btn-save">Save Book</button>
</div>

</form>

</div>
</div>
</div>

</body>
</html>