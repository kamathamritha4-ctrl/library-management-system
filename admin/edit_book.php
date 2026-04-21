<?php
include_once("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header("Location: manage_books.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: manage_books.php");
    exit();
}
$book = $result->fetch_assoc();
$error = "";

if (isset($_POST['update'])) {
    $dateOfAccession = $_POST['date_of_accession'];
    $accessionNo = (int) $_POST['accession_no'];
    $subject = trim($_POST['subject']);
    $author = trim($_POST['author']);
    $title = trim($_POST['title']);
    $publisher = trim($_POST['publisher']);
    $year = $_POST['year'] !== '' ? (int) $_POST['year'] : null;
    $price = $_POST['price'] !== '' ? (float) $_POST['price'] : 0;
    $total = (int) $_POST['total_copies'];
    $quantity = (int) $_POST['quantity'];
    $billNo = trim($_POST['bill_no']);
    $billDate = $_POST['bill_date'] ?: null;
    $supplier = trim($_POST['supplier']);
    $edition = trim($_POST['edition']);
    $remarks = trim($_POST['remarks']);

    $dup = $conn->prepare("SELECT id FROM books WHERE accession_no = ? AND id <> ?");
    $dup->bind_param("ii", $accessionNo, $id);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        $error = "Accession number already used by another book.";
    } else {
        $update = $conn->prepare("UPDATE books SET date_of_accession=?, accession_no=?, category=?, author=?, title=?, publisher=?, year=?, price=?, total_copies=?, quantity=?, bill_no=?, bill_date=?, supplier=?, edition=?, remarks=? WHERE id=?");
        $update->bind_param("sisssssdiiissssi", $dateOfAccession, $accessionNo, $subject, $author, $title, $publisher, $year, $price, $total, $quantity, $billNo, $billDate, $supplier, $edition, $remarks, $id);
        $update->execute();
        header("Location: manage_books.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Book</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}body{background:#f4f6f9}.wrapper{display:flex;min-height:100vh}.main{flex:1;padding:30px}.card{background:#fff;border-radius:14px;padding:25px;max-width:980px;margin:auto;box-shadow:0 10px 24px rgba(0,0,0,.08)}.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-group{margin-bottom:11px}.form-group label{display:block;margin-bottom:6px;font-size:14px}.form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}.btn{margin-top:10px;padding:10px 16px;border:none;background:#f39c12;color:white;border-radius:8px;cursor:pointer}.alert{background:#f8d7da;color:#721c24;padding:10px;border-radius:8px;margin-bottom:12px}@media(max-width:760px){.row{grid-template-columns:1fr}}</style>
</head><body><div class="wrapper"><?php include("../includes/sidebar1.php"); ?><div class="main"><div class="card"><h2 style="margin-bottom:15px;">✏️ Edit Book</h2><?php if($error) echo "<div class='alert'>{$error}</div>"; ?><form method="post">
<div class="row"><div class="form-group"><label>Date of Accession</label><input type="date" name="date_of_accession" value="<?php echo htmlspecialchars($book['date_of_accession']); ?>" required></div><div class="form-group"><label>Accession No</label><input type="number" name="accession_no" value="<?php echo (int)$book['accession_no']; ?>" required></div></div>
<div class="row"><div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?php echo htmlspecialchars($book['category']); ?>" required></div><div class="form-group"><label>Author</label><input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required></div></div>
<div class="row"><div class="form-group"><label>Title & Volume</label><input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required></div><div class="form-group"><label>Publisher</label><input type="text" name="publisher" value="<?php echo htmlspecialchars($book['publisher']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Year</label><input type="number" name="year" value="<?php echo htmlspecialchars((string)$book['year']); ?>"></div><div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars((string)$book['price']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" value="<?php echo (int)$book['total_copies']; ?>"></div><div class="form-group"><label>Available Quantity</label><input type="number" name="quantity" value="<?php echo (int)$book['quantity']; ?>"></div></div>
<div class="row"><div class="form-group"><label>Bill No</label><input type="text" name="bill_no" value="<?php echo htmlspecialchars((string)$book['bill_no']); ?>"></div><div class="form-group"><label>Bill Date</label><input type="date" name="bill_date" value="<?php echo htmlspecialchars((string)$book['bill_date']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Supplier</label><input type="text" name="supplier" value="<?php echo htmlspecialchars((string)$book['supplier']); ?>"></div><div class="form-group"><label>Edition</label><input type="text" name="edition" value="<?php echo htmlspecialchars((string)$book['edition']); ?>"></div></div>
<div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2"><?php echo htmlspecialchars((string)$book['remarks']); ?></textarea></div>
<button type="submit" name="update" class="btn">Update Book</button></form></div></div></div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script>
</body></html>
