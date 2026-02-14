<?php
include("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: /Library_Management_Project/index.php");
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Book</title>

<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
}

.container {
    width: 600px;
    margin: 40px auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 6px;
    box-shadow: 0 0 10px rgba(0,0,0,0.2);
}

h2 {
    text-align: center;
    margin-bottom: 25px;
}

.form-group {
    display: flex;
    margin-bottom: 15px;
    align-items: center;
}

.form-group label {
    width: 150px;
    font-weight: bold;
}

.form-group input {
    flex: 1;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.buttons {
    text-align: center;
    margin-top: 20px;
}

.buttons button {
    padding: 8px 20px;
    margin: 0 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.reset-btn {
    background-color: #ccc;
}

.save-btn {
    background-color: #4CAF50;
    color: white;
}

.save-btn:hover {
    background-color: #45a049;
}

.back-link {
    text-align:center;
    margin-top:20px;
}
</style>
</head>

<body>

<div class="container">
    <h2>ADD NEW BOOK</h2>

    <?php if(isset($success)) { ?>
        <p style="color:green; text-align:center;"><?php echo $success; ?></p>
    <?php } ?>

    <?php if(isset($error)) { ?>
        <p style="color:red; text-align:center;"><?php echo $error; ?></p>
    <?php } ?>

    <form method="post">

        <div class="form-group">
            <label>Book Id</label>
            <input type="text" name="book_id" required>
        </div>

        <div class="form-group">
            <label>Title/Name</label>
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
            <label>Price</label>
            <input type="number" name="price" step="0.01">
        </div>

        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" required>
        </div>

        <div class="buttons">
            <button type="reset" class="reset-btn">RESET</button>
            <button type="submit" name="save" class="save-btn">SAVE</button>
        </div>

    </form>

    <div class="back-link">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>

</div>

</body>
</html>
