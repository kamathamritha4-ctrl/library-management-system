<?php
include_once("../config/config.php");
include_once("../includes/library_helpers.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

if (isset($_GET['return_id'])) {
    $id = (int) $_GET['return_id'];

    $stmt = $conn->prepare("SELECT * FROM issued_books WHERE id = ? AND return_date IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $accessionNo = (int) $row['accession_no'];
        $today = date("Y-m-d");

        $fineInfo = calculate_overdue_fine($conn, $row['due_date'], $today);
        $fine = (int) $fineInfo['fine'];

        $updateIssue = $conn->prepare("UPDATE issued_books SET return_date = ?, fine = ? WHERE id = ?");
        $updateIssue->bind_param("sii", $today, $fine, $id);
        $updateIssue->execute();

        $updateStock = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE accession_no = ?");
        $updateStock->bind_param("i", $accessionNo);
        $updateStock->execute();

        header("Location: issued_book.php");
        exit();
    }
}

if (isset($_POST['send_overdue_notifications'])) {
    $today = date('Y-m-d');
    $mailCount = 0;

    $query = "SELECT ib.id, ib.user_id, ib.accession_no, ib.due_date, b.title, u.name, u.email
              FROM issued_books ib
              JOIN books b ON b.accession_no = ib.accession_no
              JOIN users u ON u.id = ib.user_id
              WHERE ib.return_date IS NULL";

    $issues = $conn->query($query);

    if ($issues) {
        while ($row = $issues->fetch_assoc()) {
            $fineInfo = calculate_overdue_fine($conn, $row['due_date'], $today);
            if ($fineInfo['fine'] <= 0) {
                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $subject = "Library Overdue Notice - Accession #" . $row['accession_no'];
            $message = "Hello " . $row['name'] . ",\n\n"
                . "Your issued book is overdue.\n"
                . "Accession No: " . $row['accession_no'] . "\n"
                . "Title: " . $row['title'] . "\n"
                . "Due Date: " . $row['due_date'] . "\n"
                . "Overdue Days: " . $fineInfo['days'] . "\n"
                . "Fine Amount: Rs " . $fineInfo['fine'] . "\n\n"
                . "Please return the book at the earliest.\n"
                . "- Library Admin";

            $headers = "From: library-noreply@example.com\r\n";

            if (@mail($email, $subject, $message, $headers)) {
                $mailCount++;
            }
        }
    }

    $success = "Overdue notification process completed. Emails sent: {$mailCount}.";
}

$issues = $conn->query("SELECT ib.*, b.title FROM issued_books ib JOIN books b ON ib.accession_no = b.accession_no WHERE ib.return_date IS NULL ORDER BY ib.id DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><title>Issued Books</title><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main">
  <div class="page-header"><h2>📚 Issued Books</h2>
    <form method="post"><button class="btn btn-secondary" type="submit" name="send_overdue_notifications"><i class="fas fa-envelope"></i> Send Overdue Emails</button></form>
  </div>

  <?php if ($success) echo "<div class='alert-success'>{$success}</div>"; ?>
  <?php if ($error) echo "<div class='alert-error'>{$error}</div>"; ?>

  <div class="table-card">
    <table>
      <thead><tr><th>Accession No</th><th>Book Name</th><th>Issue Date</th><th>Due Date</th><th>Current Fine</th><th>Status</th></tr></thead>
      <tbody>
      <?php
      if ($issues && $issues->num_rows > 0) {
          $today = date('Y-m-d');
          while ($row = $issues->fetch_assoc()) {
              $fineInfo = calculate_overdue_fine($conn, $row['due_date'], $today);
              $status = "<a href='?return_id={$row['id']}' class='link-btn' onclick=\"return confirm('Mark this book as returned?')\">Return</a>";
              $fineText = $fineInfo['fine'] > 0 ? "<span class='tag-danger'>₹ {$fineInfo['fine']}</span>" : "₹ 0";
              echo "<tr><td>{$row['accession_no']}</td><td>" . htmlspecialchars($row['title']) . "</td><td>{$row['issue_date']}</td><td>{$row['due_date']}</td><td>{$fineText}</td><td>{$status}</td></tr>";
          }
      } else {
          echo "<tr><td colspan='6'>No issued books found</td></tr>";
      }
      ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<script>function toggleSidebar(){document.getElementById("sidebar").classList.toggle("collapsed");}</script>
</body></html>
