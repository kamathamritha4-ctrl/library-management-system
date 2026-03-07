<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['issue'])) {

    $user_id = $_POST['user_id'];
    $accession_no = $_POST['accession_no'];
    $issue_date = $_POST['issue_date'];
    $due_date = $_POST['due_date'];

    // Check if book exists
    $check = $conn->query("SELECT quantity FROM books WHERE accession_no='$accession_no'");

    if($check && $check->num_rows > 0){

        $book = $check->fetch_assoc();

        // Check if copies available
        if($book['quantity'] > 0){

            // Insert issued book
            $sql = "INSERT INTO issued_books 
                    (user_id, accession_no, issue_date, due_date)
                    VALUES
                    ('$user_id', '$accession_no', '$issue_date', '$due_date')";

            if($conn->query($sql)){

                // Reduce available copies
                $conn->query("UPDATE books 
                              SET quantity = quantity - 1
                              WHERE accession_no='$accession_no'");

                $success = "Book Issued Successfully!";
            } else {
                $error = "SQL Error: " . $conn->error;
            }

        } else {
            $error = "No copies available for this book!";
        }

    } else {
        $error = "Book not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Issue Book</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#f4f6f9;
}

.wrapper{
display:flex;
min-height:100vh;
}

/* Sidebar */

.sidebar{
width:240px;
background:#2c3e50;
padding:25px 15px;
color:white;
transition:0.3s ease;
overflow:hidden;
}

.sidebar-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
}

.collapse-btn{
background:transparent;
border:none;
color:white;
font-size:18px;
cursor:pointer;
}

.sidebar a{
display:flex;
align-items:center;
gap:12px;
padding:12px 10px;
margin-bottom:10px;
text-decoration:none;
color:#ecf0f1;
border-radius:8px;
transition:0.3s;
}

.sidebar a:hover{
background:#34495e;
}

/* collapsed */

.sidebar.collapsed{
width:70px;
}

.sidebar.collapsed h3{
display:none;
}

.sidebar.collapsed a span{
display:none;
}

.sidebar.collapsed .sidebar-header{
justify-content:center;
}

/* main */

.main{
flex:1;
padding:40px;
}

.card{
background:white;
border-radius:15px;
padding:35px;
max-width:650px;
margin:auto;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.card h2{
margin-bottom:25px;
color:#333;
}

/* form */

.form-group{
margin-bottom:18px;
}

.form-group label{
display:block;
margin-bottom:6px;
font-weight:500;
font-size:14px;
}

.form-group input{
width:100%;
padding:10px 12px;
border-radius:8px;
border:1px solid #ddd;
font-size:14px;
background:#fafafa;
}

.form-group input:focus{
border-color:#3498db;
outline:none;
background:white;
box-shadow:0 0 0 2px rgba(52,152,219,0.2);
}

/* button */

.btn{
margin-top:10px;
padding:10px 20px;
border-radius:8px;
border:none;
cursor:pointer;
font-weight:500;
background:#3498db;
color:white;
transition:0.3s;
}

.btn:hover{
background:#2980b9;
}

/* alerts */

.alert-success{
background:#d4edda;
color:#155724;
padding:10px 15px;
border-radius:8px;
margin-bottom:20px;
}

.alert-error{
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

<!-- Sidebar -->
<?php include("../includes/sidebar1.php"); ?>

<!-- Main Content -->
<div class="main">

<div class="card">

<h2>📖 Issue Book</h2>

<?php if(isset($success)) echo "<div class='alert-success'>$success</div>"; ?>
<?php if(isset($error)) echo "<div class='alert-error'>$error</div>"; ?>

<form method="post">

<div class="form-group">
<label>User ID</label>
<input type="number" name="user_id" required>
</div>

<div class="form-group">
<label>Accession Number</label>
<input type="number" name="accession_no" required>
</div>

<div class="form-group">
<label>Issue Date</label>
<input type="date" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
</div>

<div class="form-group">
<label>Due Date</label>
<input type="date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
</div>

<button type="submit" name="issue" class="btn">
<i class="fas fa-check-circle"></i> Issue Book
</button>

</form>

</div>
</div>

</div>

<script>

function toggleSidebar(){
document.getElementById("sidebar").classList.toggle("collapsed");
}

// Auto update due date when issue date changes
document.querySelector("input[name='issue_date']").addEventListener("change", function(){

let issueDate = new Date(this.value);
issueDate.setDate(issueDate.getDate() + 15);

let due = issueDate.toISOString().split('T')[0];
document.querySelector("input[name='due_date']").value = due;

});

</script>

</body>
</html>