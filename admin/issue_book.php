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
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main">
  <div class="page-header"><h2>📖 Issue Book</h2></div>
  <div class="card">
    <?php if ($success) echo "<div class='alert-success'>{$success}</div>"; ?>
    <?php if ($error) echo "<div class='alert-error'>{$error}</div>"; ?>

    <form method="post">
      <div class="row">
        <div class="form-group"><label>User ID (Student/Faculty)</label><input type="number" name="user_id" value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>" required></div>
        <div class="form-group"><label>Accession Number</label><input type="number" name="accession_no" value="<?php echo htmlspecialchars($_POST['accession_no'] ?? ''); ?>" required></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Issue Date</label><input type="date" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($issueDate); ?>" required></div>
        <div class="form-group"><label>Due Date (15 days + Sunday/Holiday adjustment)</label><input type="date" id="due_date" value="<?php echo htmlspecialchars($dueDate); ?>" readonly></div>
      </div>
      <div class="actions">
        <button type="submit" name="preview" class="btn btn-secondary"><i class="fas fa-eye"></i> Preview Book</button>
        <button type="submit" name="issue" class="btn btn-primary"><i class="fas fa-check-circle"></i> Issue Book</button>
      </div>
    </form>

    <?php if ($bookDetails): ?>
    <h3 class="section-title" style="margin-top:20px;">Book Details (By Accession No)</h3>
    <div class="table-card" style="padding:14px;">
      <div class="row">
        <div><strong>Date of Accession:</strong> <?php echo htmlspecialchars($bookDetails['date_of_accession'] ?? '-'); ?></div>
        <div><strong>Accession No:</strong> <?php echo htmlspecialchars($bookDetails['accession_no'] ?? '-'); ?></div>
        <div><strong>Subject:</strong> <?php echo htmlspecialchars($bookDetails['category'] ?? '-'); ?></div>
        <div><strong>Author:</strong> <?php echo htmlspecialchars($bookDetails['author'] ?? '-'); ?></div>
        <div><strong>Title & Volume:</strong> <?php echo htmlspecialchars($bookDetails['title'] ?? '-'); ?></div>
        <div><strong>Publisher:</strong> <?php echo htmlspecialchars($bookDetails['publisher'] ?? '-'); ?></div>
        <div><strong>Year:</strong> <?php echo htmlspecialchars($bookDetails['year'] ?? '-'); ?></div>
        <div><strong>Price (Rs):</strong> <?php echo htmlspecialchars((string) ($bookDetails['price'] ?? '-')); ?></div>
        <div><strong>Total:</strong> <?php echo htmlspecialchars((string) ($bookDetails['total_copies'] ?? '-')); ?></div>
        <div><strong>Supplier:</strong> <?php echo htmlspecialchars($bookDetails['supplier'] ?? '-'); ?></div>
        <div><strong>Edition:</strong> <?php echo htmlspecialchars($bookDetails['edition'] ?? '-'); ?></div>
        <div><strong>Remarks:</strong> <?php echo htmlspecialchars($bookDetails['remarks'] ?? '-'); ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
<script>function toggleSidebar(){document.getElementById("sidebar").classList.toggle("collapsed");}</script>
</body>
</html>
