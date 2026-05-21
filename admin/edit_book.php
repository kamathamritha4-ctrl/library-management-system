<?php
include_once("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$accessionNoFromQuery = isset($_GET['accession_no']) ? (int) $_GET['accession_no'] : 0;

if ($accessionNoFromQuery <= 0) {
    header("Location: manage_books.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM books WHERE accession_no = ?");
$stmt->bind_param("i", $accessionNoFromQuery);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: manage_books.php");
    exit();
}
$book = $result->fetch_assoc();
$originalAccessionNo = (int) $book['accession_no'];
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

    $dup = $conn->prepare("SELECT accession_no FROM books WHERE accession_no = ? AND accession_no <> ? LIMIT 1");
    $dup->bind_param("ii", $accessionNo, $originalAccessionNo);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        $error = "Accession number already used by another book.";
    } else {
        $conn->begin_transaction();
        if ($accessionNo !== $originalAccessionNo) {
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
        }

        $update = $conn->prepare("UPDATE books SET date_of_accession=?, accession_no=?, category=?, author=?, title=?, publisher=?, year=?, price=?, total_copies=?, quantity=?, bill_no=?, bill_date=?, supplier=?, edition=?, remarks=? WHERE accession_no=?");
        $update->bind_param("sissssidiisssssi", $dateOfAccession, $accessionNo, $subject, $author, $title, $publisher, $year, $price, $total, $quantity, $billNo, $billDate, $supplier, $edition, $remarks, $originalAccessionNo);
        $bookUpdated = $update->execute();

        if ($bookUpdated && $accessionNo !== $originalAccessionNo) {
            $issueUpdate = $conn->prepare("UPDATE issued_books SET accession_no = ? WHERE accession_no = ?");
            $issueUpdate->bind_param("ii", $accessionNo, $originalAccessionNo);
            $bookUpdated = $issueUpdate->execute();
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
        }

        if ($bookUpdated) {
            $conn->commit();
            header("Location: manage_books.php");
            exit();
        }

        $conn->rollback();
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        $error = "Error updating book: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Book</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head><body><div class="wrapper"><?php include("../includes/sidebar1.php"); ?><div class="main">
<div class="page-header"><h2>✏️ Edit Book</h2></div>
<div class="card"><?php if($error) echo "<div class='alert-error'>{$error}</div>"; ?><form method="post">
<div class="row"><div class="form-group"><label>Date of Accession</label><input type="date" name="date_of_accession" value="<?php echo htmlspecialchars($book['date_of_accession']); ?>" required></div><div class="form-group"><label>Accession No</label><input type="number" name="accession_no" value="<?php echo (int)$book['accession_no']; ?>" required></div></div>
<div class="row"><div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?php echo htmlspecialchars($book['category']); ?>" required></div><div class="form-group"><label>Author</label><input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required></div></div>
<div class="row"><div class="form-group"><label>Title & Volume</label><input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required></div><div class="form-group"><label>Publisher</label><input type="text" name="publisher" value="<?php echo htmlspecialchars($book['publisher']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Year</label><input type="number" name="year" value="<?php echo htmlspecialchars((string)$book['year']); ?>"></div><div class="form-group"><label>Price</label><input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars((string)$book['price']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" value="<?php echo (int)$book['total_copies']; ?>"></div><div class="form-group"><label>Available Quantity</label><input type="number" name="quantity" value="<?php echo (int)$book['quantity']; ?>"></div></div>
<div class="row"><div class="form-group"><label>Bill No</label><input type="text" name="bill_no" value="<?php echo htmlspecialchars((string)$book['bill_no']); ?>"></div><div class="form-group"><label>Bill Date</label><input type="date" name="bill_date" value="<?php echo htmlspecialchars((string)$book['bill_date']); ?>"></div></div>
<div class="row"><div class="form-group"><label>Supplier</label><input type="text" name="supplier" value="<?php echo htmlspecialchars((string)$book['supplier']); ?>"></div><div class="form-group"><label>Edition</label><input type="text" name="edition" value="<?php echo htmlspecialchars((string)$book['edition']); ?>"></div></div>
<div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2"><?php echo htmlspecialchars((string)$book['remarks']); ?></textarea></div>
<div class="actions"><button type="submit" name="update" class="btn btn-primary">Update Book</button></div></form></div></div></div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script>
</body></html>
