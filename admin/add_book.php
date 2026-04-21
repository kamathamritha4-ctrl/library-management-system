<?php
require_once("../vendor/autoload.php");
include_once("../config/config.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

if (isset($_POST['import']) && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
    try {
        $spreadsheet = IOFactory::load($_FILES['file']['tmp_name']);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $accessionNo = (int) ($row[0] ?? 0);
            if ($accessionNo <= 0) {
                continue;
            }

            $dateOfAccession = !empty($row[1]) ? date('Y-m-d', strtotime((string) $row[1])) : date('Y-m-d');
            $subject = trim((string) ($row[2] ?? ''));
            $author = trim((string) ($row[3] ?? ''));
            $title = trim((string) ($row[4] ?? ''));
            $publisher = trim((string) ($row[5] ?? ''));
            $year = !empty($row[6]) ? (int) $row[6] : null;
            $price = isset($row[7]) ? (float) $row[7] : 0;
            $total = isset($row[8]) ? (int) $row[8] : 1;
            $billNo = trim((string) ($row[9] ?? ''));
            $billDate = !empty($row[10]) ? date('Y-m-d', strtotime((string) $row[10])) : null;
            $supplier = trim((string) ($row[11] ?? ''));
            $edition = trim((string) ($row[12] ?? ''));
            $remarks = trim((string) ($row[13] ?? ''));

            $check = $conn->prepare("SELECT id FROM books WHERE accession_no = ?");
            $check->bind_param("i", $accessionNo);
            $check->execute();
            $exists = $check->get_result();
            if ($exists->num_rows > 0) {
                continue;
            }

            $stmt = $conn->prepare("INSERT INTO books (date_of_accession, accession_no, category, author, title, publisher, year, price, total_copies, quantity, bill_no, bill_date, supplier, edition, remarks)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sisssssdiiissss",
                $dateOfAccession,
                $accessionNo,
                $subject,
                $author,
                $title,
                $publisher,
                $year,
                $price,
                $total,
                $total,
                $billNo,
                $billDate,
                $supplier,
                $edition,
                $remarks
            );
            $stmt->execute();
        }

        $success = "Bulk import completed successfully.";
    } catch (Exception $e) {
        $error = "Import failed: " . $e->getMessage();
    }
}

if (isset($_POST['save'])) {
    $dateOfAccession = $_POST['date_of_accession'] ?: date('Y-m-d');
    $accessionNo = (int) $_POST['accession_no'];
    $subject = trim($_POST['subject']);
    $author = trim($_POST['author']);
    $title = trim($_POST['title']);
    $publisher = trim($_POST['publisher']);
    $year = $_POST['year'] !== '' ? (int) $_POST['year'] : null;
    $price = $_POST['price'] !== '' ? (float) $_POST['price'] : 0;
    $total = max(1, (int) $_POST['total_copies']);
    $billNo = trim($_POST['bill_no']);
    $billDate = $_POST['bill_date'] ?: null;
    $supplier = trim($_POST['supplier']);
    $edition = trim($_POST['edition']);
    $remarks = trim($_POST['remarks']);

    $check = $conn->prepare("SELECT id FROM books WHERE accession_no = ?");
    $check->bind_param("i", $accessionNo);
    $check->execute();
    $exists = $check->get_result();

    if ($exists->num_rows > 0) {
        $error = "Accession number already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO books (date_of_accession, accession_no, category, author, title, publisher, year, price, total_copies, quantity, bill_no, bill_date, supplier, edition, remarks)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sisssssdiiissss",
            $dateOfAccession,
            $accessionNo,
            $subject,
            $author,
            $title,
            $publisher,
            $year,
            $price,
            $total,
            $total,
            $billNo,
            $billDate,
            $supplier,
            $edition,
            $remarks
        );

        if ($stmt->execute()) {
            $success = "Book added successfully.";
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
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}body{background:#f4f6f9}.wrapper{display:flex;min-height:100vh}.main{flex:1;padding:30px}.card{background:white;border-radius:16px;padding:26px;max-width:980px;margin:auto;box-shadow:0 15px 35px rgba(0,0,0,.08)}.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}.form-group{margin-bottom:12px}.form-group label{display:block;margin-bottom:6px;font-size:14px}.form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}.actions{display:flex;gap:10px;margin-top:14px}.btn{padding:10px 16px;border:none;border-radius:8px;cursor:pointer}.btn-save{background:#4f46e5;color:#fff}.btn-reset{background:#cbd5e1}.alert-success,.alert-error{padding:10px 12px;border-radius:8px;margin-bottom:10px}.alert-success{background:#d4edda;color:#155724}.alert-error{background:#f8d7da;color:#721c24}
@media(max-width:760px){.row{grid-template-columns:1fr}.main{padding:18px}}
</style>
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main"><div class="card">
<h2 style="margin-bottom:14px;">📘 Add New Book</h2>
<?php if($success) echo "<div class='alert-success'>{$success}</div>"; ?>
<?php if($error) echo "<div class='alert-error'>{$error}</div>"; ?>

<h3 style="margin:8px 0;">Bulk Import (Excel/CSV)</h3>
<form method="post" enctype="multipart/form-data" style="margin-bottom:18px;">
  <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
  <button type="submit" name="import" class="btn btn-save">Import</button>
</form>

<form method="post">
<div class="row">
  <div class="form-group"><label>Date of Accession</label><input type="date" name="date_of_accession" value="<?php echo date('Y-m-d');?>" required></div>
  <div class="form-group"><label>Accession Number</label><input type="number" name="accession_no" required></div>
</div>
<div class="row">
  <div class="form-group"><label>Subject</label><input type="text" name="subject" required></div>
  <div class="form-group"><label>Author</label><input type="text" name="author" required></div>
</div>
<div class="row">
  <div class="form-group"><label>Title & Volume</label><input type="text" name="title" required></div>
  <div class="form-group"><label>Publisher</label><input type="text" name="publisher"></div>
</div>
<div class="row">
  <div class="form-group"><label>Year</label><input type="number" name="year"></div>
  <div class="form-group"><label>Price Rs</label><input type="number" step="0.01" name="price"></div>
</div>
<div class="row">
  <div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" min="1" required></div>
  <div class="form-group"><label>Bill No</label><input type="text" name="bill_no"></div>
</div>
<div class="row">
  <div class="form-group"><label>Bill Date</label><input type="date" name="bill_date"></div>
  <div class="form-group"><label>Supplier</label><input type="text" name="supplier"></div>
</div>
<div class="row">
  <div class="form-group"><label>Edition</label><input type="text" name="edition"></div>
  <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="1"></textarea></div>
</div>
<div class="actions"><button class="btn btn-save" type="submit" name="save">Save Book</button><button class="btn btn-reset" type="reset">Reset</button></div>
</form>
</div></div></div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script>
</body></html>
