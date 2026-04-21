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
<meta charset="UTF-8">
<title>Issued Books</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}body{background:#f4f6f9}.wrapper{display:flex;min-height:100vh}.main{flex:1;padding:40px}.main h2{margin-bottom:20px;color:#333}.table-card{background:white;border-radius:15px;padding:20px;box-shadow:0 8px 20px rgba(0,0,0,.08)}table{width:100%;border-collapse:collapse}table th,table td{padding:12px;text-align:left;font-size:14px}table th{background:#f8f9fa;font-weight:600}table tr{border-bottom:1px solid #eee}.return-btn{padding:6px 12px;font-size:12px;background:#e67e22;color:white;border-radius:6px;text-decoration:none}.return-btn:hover{background:#d35400}.alert-success,.alert-error{padding:10px 15px;border-radius:8px;margin-bottom:15px}.alert-success{background:#d4edda;color:#155724}.alert-error{background:#f8d7da;color:#721c24}.top-actions{display:flex;justify-content:flex-end;margin-bottom:15px}.btn{padding:9px 14px;border:none;border-radius:8px;background:#2f80ed;color:#fff;cursor:pointer}.badge-red{background:#eb5757;color:white;padding:4px 8px;border-radius:6px;font-size:12px}
</style>
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main">
    <h2>📚 Issued Books</h2>

    <?php if ($success) echo "<div class='alert-success'>{$success}</div>"; ?>
    <?php if ($error) echo "<div class='alert-error'>{$error}</div>"; ?>

    <div class="top-actions">
        <form method="post">
            <button class="btn" type="submit" name="send_overdue_notifications"><i class="fas fa-envelope"></i> Send Overdue Emails</button>
        </form>
    </div>

    <div class="table-card">
        <table>
            <thead>
            <tr>
                <th>Accession No</th>
                <th>Book Name</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Current Fine</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($issues && $issues->num_rows > 0) {
                $today = date('Y-m-d');

                while ($row = $issues->fetch_assoc()) {
                    $fineInfo = calculate_overdue_fine($conn, $row['due_date'], $today);
                    $status = "<a href='?return_id={$row['id']}' class='return-btn' onclick=\"return confirm('Mark this book as returned?')\">Return</a>";
                    $fineText = $fineInfo['fine'] > 0 ? "<span class='badge-red'>₹ {$fineInfo['fine']}</span>" : "₹ 0";

                    echo "<tr>
                            <td>{$row['accession_no']}</td>
                            <td>" . htmlspecialchars($row['title']) . "</td>
                            <td>{$row['issue_date']}</td>
                            <td>{$row['due_date']}</td>
                            <td>{$fineText}</td>
                            <td>{$status}</td>
                          </tr>";
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
<script>
function toggleSidebar(){document.getElementById("sidebar").classList.toggle("collapsed");}
</script>
</body>
</html>
