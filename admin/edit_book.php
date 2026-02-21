<?php
include("../config/config.php");


if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch book data
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    header("Location: dashboard.php");
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

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Book</title>

<style>
body {
    font-family: Arial;
    background:#f5f6fa;
    padding:40px;
}

.container {
    width:600px;
    margin:auto;
    background:white;
    padding:30px;
    border-radius:8px;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
}

h2 {
    text-align:center;
    margin-bottom:20px;
}

.form-group {
    margin-bottom:15px;
}

label {
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

input {
    width:100%;
    padding:8px;
    border:1px solid #ccc;
    border-radius:5px;
}

button {
    width:100%;
    padding:10px;
    background:#f39c12;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

button:hover {
    background:#d68910;
}
</style>
</head>

<body>

<div class="container">
    <h2>Edit Book</h2>

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

        <button type="submit" name="update">Update Book</button>

    </form>
</div>

</body>
</html>