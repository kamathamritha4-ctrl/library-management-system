<?php
include_once("../config/config.php");
include_once("../includes/library_helpers.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

function ensure_overdue_notification_column(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM issued_books LIKE 'overdue_last_notified'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE issued_books ADD COLUMN overdue_last_notified DATETIME NULL");
    }
}

function send_overdue_notifications(mysqli $conn, string $today, bool $onlyUnnotifiedToday = false): array
{
    $mailCount = 0;
    $failedSends = 0;
    $skippedNoEmail = 0;
    $skippedNotOverdue = 0;
    $skippedInvalidDates = 0;

    $query = "SELECT ib.id, ib.user_id, ib.accession_no, ib.issue_date, ib.due_date, ib.overdue_last_notified, b.title, u.name, u.email
              FROM issued_books ib
              JOIN books b ON b.accession_no = ib.accession_no
              JOIN users u ON u.id = ib.user_id
              WHERE ib.return_date IS NULL";

    $issues = $conn->query($query);
    if (!$issues) {
        return compact('mailCount', 'skippedNoEmail', 'skippedNotOverdue', 'skippedInvalidDates');
    }

    $updateNotifyStmt = $conn->prepare("UPDATE issued_books SET overdue_last_notified = NOW() WHERE id = ?");
    while ($row = $issues->fetch_assoc()) {
        if ($row['due_date'] < $row['issue_date']) {
            $skippedInvalidDates++;
            continue;
        }

        if ($onlyUnnotifiedToday && !empty($row['overdue_last_notified'])) {
            $lastNotified = substr((string) $row['overdue_last_notified'], 0, 10);
            if ($lastNotified === $today) {
                continue;
            }
        }

        $fineInfo = calculate_overdue_fine($conn, $row['due_date'], $today);
        if ($fineInfo['fine'] <= 0) {
            $skippedNotOverdue++;
            continue;
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '') {
            $skippedNoEmail++;
            continue;
        }

        $subject = "Library Overdue Notice - Book Return Required";
        $safeName = htmlspecialchars((string) $row['name']);
        $safeTitle = htmlspecialchars((string) $row['title']);
        $safeDueDate = htmlspecialchars((string) $row['due_date']);

        $htmlMessage = "
            <html><body style='font-family:Arial,sans-serif;line-height:1.6;color:#1f2937'>
            <p>Dear {$safeName},</p>
            <p>This is a reminder that the following library book is overdue:</p>
            <table cellpadding='6' cellspacing='0' border='1' style='border-collapse:collapse;border-color:#d1d5db'>
              <tr><td><strong>Accession No</strong></td><td>{$row['accession_no']}</td></tr>
              <tr><td><strong>Book Title</strong></td><td>{$safeTitle}</td></tr>
              <tr><td><strong>Due Date</strong></td><td>{$safeDueDate}</td></tr>
              <tr><td><strong>Overdue Days</strong></td><td>{$fineInfo['days']}</td></tr>
              <tr><td><strong>Current Fine</strong></td><td>₹ {$fineInfo['fine']}</td></tr>
            </table>
            <p>Please return the book as soon as possible to avoid additional fines.</p>
            <p>Regards,<br>Library Administration</p>
            </body></html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Library Admin <library-noreply@example.com>\r\n";

        if (mail($email, $subject, $htmlMessage, $headers)) {
            $mailCount++;
            if ($updateNotifyStmt) {
                $issueId = (int) $row['id'];
                $updateNotifyStmt->bind_param("i", $issueId);
                $updateNotifyStmt->execute();
            }
        } else {
            $failedSends++;
        }
    }

    return compact('mailCount', 'failedSends', 'skippedNoEmail', 'skippedNotOverdue', 'skippedInvalidDates');
}

function sync_live_fines(mysqli $conn, string $asOfDate): void
{
    $activeIssues = $conn->query("SELECT id, issue_date, due_date, fine FROM issued_books WHERE return_date IS NULL");
    if (!$activeIssues) {
        return;
    }

    $updateFineStmt = $conn->prepare("UPDATE issued_books SET fine = ? WHERE id = ?");
    if (!$updateFineStmt) {
        return;
    }

    while ($issue = $activeIssues->fetch_assoc()) {
        if (!empty($issue['issue_date']) && $issue['due_date'] < $issue['issue_date']) {
            continue;
        }
        $fineInfo = calculate_overdue_fine($conn, $issue['due_date'], $asOfDate);
        $calculatedFine = (int) $fineInfo['fine'];
        $storedFine = (int) ($issue['fine'] ?? 0);

        if ($calculatedFine !== $storedFine) {
            $issueId = (int) $issue['id'];
            $updateFineStmt->bind_param("ii", $calculatedFine, $issueId);
            $updateFineStmt->execute();
        }
    }
}

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
    ensure_overdue_notification_column($conn);
    $stats = send_overdue_notifications($conn, $today, false);
    $success = "Overdue notification process completed. Emails sent: {$stats['mailCount']}, failed to send: {$stats['failedSends']}, skipped (not overdue): {$stats['skippedNotOverdue']}, skipped (missing email): {$stats['skippedNoEmail']}, skipped (invalid issue/due dates): {$stats['skippedInvalidDates']}.";
    if ($stats['failedSends'] > 0) {
        $error = "Mail transport failed for {$stats['failedSends']} email(s). Configure SMTP/sendmail on the server to deliver emails.";
    }
}

$today = date('Y-m-d');
ensure_overdue_notification_column($conn);
sync_live_fines($conn, $today);
send_overdue_notifications($conn, $today, true);

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
          while ($row = $issues->fetch_assoc()) {
              $status = "<a href='?return_id={$row['id']}' class='link-btn' onclick=\"return confirm('Mark this book as returned?')\">Return</a>";
              $fineValue = (int) ($row['fine'] ?? 0);
              $fineText = $fineValue > 0 ? "<span class='tag-danger'>₹ {$fineValue}</span>" : "₹ 0";
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
