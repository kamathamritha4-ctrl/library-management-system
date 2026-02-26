<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: manage_books.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch book data
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    header("Location: manage_books.php");
    exit();
}

$book = $result->fetch_assoc();

// Update logic
if(isset($_POST['update'])) {

    $accession_no = $_POST['accession_no'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $publisher = $_POST['publisher'];
    $edition = $_POST['edition'];
    $price = $_POST['price'];
    $total = $_POST['total_copies'];
    $quantity = $_POST['quantity'];

    $update = $conn->prepare("
        UPDATE books SET
        accession_no=?,
        title=?,
        author=?,
        category=?,
        publisher=?,
        edition=?,
        price=?,
        total_copies=?,
        quantity=?
        WHERE id=?
    ");

    $update->bind_param(
        "isssssiiii",
        $accession_no,
        $title,
        $author,
        $category,
        $publisher,
        $edition,
        $price,
        $total,
        $quantity,
        $id
    );

    $update->execute();

    header("Location: manage_books.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Book</title>
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

/* Button */
.btn {
    margin-top: 15px;
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    background: #f39c12;
    color: white;
    transition: 0.3s;
}

.btn:hover {
    background: #d68910;
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
        <h2>✏️ Edit Book</h2>

        <form method="post">

            <div class="form-group">
                <label>Accession No</label>
                <input type="number" name="accession_no" value="<?php echo $book['accession_no']; ?>" required>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo $book['title']; ?>" required>
            </div>

            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" value="<?php echo $book['author']; ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?php echo $book['category']; ?>" required>
            </div>

            <div class="form-group">
                <label>Publisher</label>
                <input type="text" name="publisher" value="<?php echo $book['publisher']; ?>" required>
            </div>

            <div class="form-group">
                <label>Edition</label>
                <input type="text" name="edition" value="<?php echo $book['edition']; ?>">
            </div>

            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?php echo $book['price']; ?>">
            </div>

            <div class="form-group">
                <label>Total Copies</label>
                <input type="number" name="total_copies" value="<?php echo $book['total_copies']; ?>">
            </div>

            <div class="form-group">
                <label>Available Quantity</label>
                <input type="number" name="quantity" value="<?php echo $book['quantity']; ?>">
            </div>

            <button type="submit" name="update" class="btn">
                <i class="fas fa-save"></i> Update Book
            </button>

        </form>
    </div>
</div>

</body>
</html>