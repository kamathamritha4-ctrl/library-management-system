<?php
include("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: /Library_Management_Project/index.php");
    exit();
}

if(isset($_POST['issue'])) {

    $title = $_POST['title'];
    $user_id = $_POST['user_id'];
    $accession_no = $_POST['accession_no'];
    $issue_date = date("Y-m-d");

    // Calculate due date (15 days from issue)
    $due_date = date("Y-m-d", strtotime($issue_date . " +15 days"));

    // Insert into issued_books
    $sql = "INSERT INTO issued_books 
            (user_id, accession_no, issue_date, due_date)
            VALUES
            ('$user_id', '$accession_no', '$issue_date', '$due_date')";

    if($conn->query($sql)) {
        $success = "Book Issued Successfully!";
    } else {
        $error = "SQL Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Issue Book</title>
<style>
body { font-family: Arial; background:#f5f6fa; padding:40px; }
.container { width:500px; margin:auto; background:white; padding:25px; border-radius:6px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
h2 { text-align:center; margin-bottom:20px; }
.form-group { margin-bottom:15px; }
label { display:block; margin-bottom:5px; font-weight:bold; }
input { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; }
button { padding:8px 15px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; }
button:hover { background:#0056b3; }
.success { color:green; text-align:center; }
.error { color:red; text-align:center; }
</style>
</head>

<body>

<div class="container">
<h2>Issue Book</h2>

<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="post">

    <div class="form-group">
        <label>Book Title</label>
        <input type="text" name="title" required>
    </div>

    <div class="form-group">
        <label>User ID</label>
        <input type="number" name="user_id" required>
    </div>

    <div class="form-group">
        <label>Accession No</label>
        <input type="number" name="accession_no" required>
    </div>

    <div class="form-group">
        <label>Issue Date</label>
        <input type="text" value="<?php echo date('Y-m-d'); ?>" readonly>
    </div>

    <div class="form-group">
        <label>Due Date (Auto 15 Days)</label>
        <input type="text" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" readonly>
    </div>

    <button type="submit" name="issue">Issue Book</button>

</form>
</div>

</body>
</html>
