<?php
include_once("../config/config.php");
include_once("../includes/library_helpers.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";
$bookDetails = null;

$issueDate = $_POST['issue_date'] ?? date('Y-m-d');
$dueDate = calculate_due_date($conn, $issueDate);

if (isset($_POST['preview'])) {
    $accessionNo = (int) ($_POST['accession_no'] ?? 0);

    $bookStmt = $conn->prepare("SELECT * FROM books WHERE accession_no = ?");
    $bookStmt->bind_param("i", $accessionNo);
    $bookStmt->execute();
    $bookResult = $bookStmt->get_result();

    if ($bookResult->num_rows === 1) {
        $bookDetails = $bookResult->fetch_assoc();
    } else {
        $error = "Book not found for the provided accession number.";
    }
}

if (isset($_POST['issue'])) {
    $userId = (int) $_POST['user_id'];
    $accessionNo = (int) $_POST['accession_no'];
    $issueDate = $_POST['issue_date'];
    $dueDate = calculate_due_date($conn, $issueDate);

    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role IN ('student','faculty')");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows !== 1) {
        $error = "Invalid user ID. Issue is allowed only to student/faculty users.";
    } else {
        $bookStmt = $conn->prepare("SELECT quantity FROM books WHERE accession_no = ?");
        $bookStmt->bind_param("i", $accessionNo);
        $bookStmt->execute();
        $bookResult = $bookStmt->get_result();

        if ($bookResult->num_rows === 1) {
            $book = $bookResult->fetch_assoc();

            if ((int) $book['quantity'] > 0) {
                $insertStmt = $conn->prepare("INSERT INTO issued_books (user_id, accession_no, issue_date, due_date) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("iiss", $userId, $accessionNo, $issueDate, $dueDate);

                if ($insertStmt->execute()) {
                    $updateStmt = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE accession_no = ?");
                    $updateStmt->bind_param("i", $accessionNo);
                    $updateStmt->execute();
                    $success = "Book issued successfully.";
                } else {
                    $error = "Unable to issue book: " . $insertStmt->error;
                }
            } else {
                $error = "No copies available for this book.";
            }
        } else {
            $error = "Book not found.";
        }
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
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f4f6f9;}
.wrapper{display:flex;min-height:100vh;}
.main{flex:1;padding:40px;}
.card{background:white;border-radius:15px;padding:35px;max-width:760px;margin:auto;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
.card h2{margin-bottom:20px;color:#333;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;margin-bottom:6px;font-weight:500;font-size:14px;}
.form-group input{width:100%;padding:10px 12px;border-radius:8px;border:1px solid #ddd;font-size:14px;background:#fafafa;}
.form-group input:focus{border-color:#3498db;outline:none;background:white;box-shadow:0 0 0 2px rgba(52,152,219,0.2);}
.btn{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:500;color:white;transition:0.3s;}
.btn-primary{background:#3498db;}
.btn-primary:hover{background:#2980b9;}
.btn-secondary{background:#2f80ed;}
.btn-secondary:hover{background:#2367c7;}
.action-row{display:flex;gap:10px;flex-wrap:wrap;}
.alert-success,.alert-error{padding:10px 15px;border-radius:8px;margin-bottom:20px;}
.alert-success{background:#d4edda;color:#155724;}
.alert-error{background:#f8d7da;color:#721c24;}
.preview{margin:18px 0;padding:15px;border:1px solid #e4e7eb;border-radius:10px;background:#fbfcfe;}
.preview h3{margin-bottom:10px;font-size:17px;color:#2c3e50;}
.preview-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;}
.preview-grid p{margin:0;}
@media (max-width: 700px){.main{padding:20px;}.card{padding:22px;}.preview-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main">
<div class="card">
<h2>📖 Issue Book</h2>

<?php if ($success) echo "<div class='alert-success'>{$success}</div>"; ?>
<?php if ($error) echo "<div class='alert-error'>{$error}</div>"; ?>

<form method="post">
    <div class="form-group">
        <label>User ID (Student/Faculty)</label>
        <input type="number" name="user_id" value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
        <label>Accession Number</label>
        <input type="number" name="accession_no" value="<?php echo htmlspecialchars($_POST['accession_no'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
        <label>Issue Date</label>
        <input type="date" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($issueDate); ?>" required>
    </div>

    <div class="form-group">
        <label>Due Date (15 days + Sunday/Holiday adjustment)</label>
        <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($dueDate); ?>" readonly>
    </div>

    <div class="action-row">
        <button type="submit" name="preview" class="btn btn-secondary"><i class="fas fa-eye"></i> Preview Book</button>
        <button type="submit" name="issue" class="btn btn-primary"><i class="fas fa-check-circle"></i> Issue Book</button>
    </div>
</form>

<?php if ($bookDetails): ?>
<div class="preview">
    <h3>Book Details (By Accession No)</h3>
    <div class="preview-grid">
        <p><strong>Date of Accession:</strong> <?php echo htmlspecialchars($bookDetails['date_of_accession'] ?? '-'); ?></p>
        <p><strong>Accession No:</strong> <?php echo htmlspecialchars($bookDetails['accession_no'] ?? '-'); ?></p>
        <p><strong>Subject:</strong> <?php echo htmlspecialchars($bookDetails['category'] ?? '-'); ?></p>
        <p><strong>Author:</strong> <?php echo htmlspecialchars($bookDetails['author'] ?? '-'); ?></p>
        <p><strong>Title & Volume:</strong> <?php echo htmlspecialchars($bookDetails['title'] ?? '-'); ?></p>
        <p><strong>Publisher:</strong> <?php echo htmlspecialchars($bookDetails['publisher'] ?? '-'); ?></p>
        <p><strong>Year:</strong> <?php echo htmlspecialchars($bookDetails['year'] ?? '-'); ?></p>
        <p><strong>Price (Rs):</strong> <?php echo htmlspecialchars((string) ($bookDetails['price'] ?? '-')); ?></p>
        <p><strong>Total:</strong> <?php echo htmlspecialchars((string) ($bookDetails['total_copies'] ?? '-')); ?></p>
        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($bookDetails['supplier'] ?? '-'); ?></p>
        <p><strong>Edition:</strong> <?php echo htmlspecialchars($bookDetails['edition'] ?? '-'); ?></p>
        <p><strong>Remarks:</strong> <?php echo htmlspecialchars($bookDetails['remarks'] ?? '-'); ?></p>
    </div>
</div>
<?php endif; ?>

</div>
</div>
</div>

<script>
function toggleSidebar(){
  document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>
</body>
</html>
