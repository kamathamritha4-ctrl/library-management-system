<?php
include_once("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS holidays (id INT AUTO_INCREMENT PRIMARY KEY, holiday_date DATE NOT NULL UNIQUE, description VARCHAR(255) NOT NULL)");

$success = '';
$error = '';
$search = trim($_GET['q'] ?? '');
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if (isset($_POST['add_holiday'])) {
    $holidayDate = $_POST['holiday_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if ($holidayDate && $description) {
        $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $holidayDate, $description);
        if ($stmt->execute()) $success = 'Holiday added.'; else $error = 'Could not add holiday (date may already exist).';
    } else {
        $error = 'Date and description are required.';
    }
}

if (isset($_POST['bulk_add']) && isset($_FILES['bulk_file']) && $_FILES['bulk_file']['error'] === 0) {
    if (($handle = fopen($_FILES['bulk_file']['tmp_name'], 'r')) !== false) {
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) continue;
            $date = trim((string) $row[0]);
            $desc = trim((string) $row[1]);
            if (!$date || !$desc || strtolower($date) === 'date') continue;
            $stmt = $conn->prepare("INSERT IGNORE INTO holidays (holiday_date, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $date, $desc);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $count++;
        }
        fclose($handle);
        $success = "Bulk upload complete. Added {$count} holidays.";
    }
}

if (isset($_POST['update_holiday'])) {
    $id = (int) $_POST['holiday_id'];
    $holidayDate = $_POST['holiday_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $stmt = $conn->prepare("UPDATE holidays SET holiday_date = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $holidayDate, $description, $id);
    $stmt->execute();
    $success = 'Holiday updated.';
    header("Location: manage_holidays.php?msg=updated");
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_holidays.php?msg=deleted");
    exit();
}

if (isset($_GET['msg']) && $_GET['msg'] === 'updated') $success = 'Holiday updated.';
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') $success = 'Holiday deleted.';

$editHoliday = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM holidays WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editHoliday = $stmt->get_result()->fetch_assoc();
}

if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT * FROM holidays WHERE holiday_date LIKE ? OR description LIKE ? ORDER BY holiday_date");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $holidays = $stmt->get_result();
} else {
    $holidays = $conn->query("SELECT * FROM holidays ORDER BY holiday_date");
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Holidays</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper"><?php include("../includes/sidebar1.php"); ?><div class="main">
  <div class="page-header"><h2>🗓️ Manage Government Holidays</h2></div>
  <div class="card">
    <?php if($success) echo "<div class='alert-success'>{$success}</div>"; ?>
    <?php if($error) echo "<div class='alert-error'>{$error}</div>"; ?>

    <form method="get" class="toolbar">
      <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by date or description">
      <button class="btn btn-navy" type="submit"><i class="fas fa-search"></i> Search</button>
      <a href="manage_holidays.php"><button class="btn btn-muted" type="button">Reset</button></a>
    </form>

    <h3 class="section-title"><?php echo $editHoliday ? 'Edit Holiday' : 'Add Holiday'; ?></h3>
    <form method="post" class="actions" style="margin-bottom:16px;">
      <input type="hidden" name="holiday_id" value="<?php echo (int)($editHoliday['id'] ?? 0); ?>">
      <input type="date" name="holiday_date" value="<?php echo htmlspecialchars($editHoliday['holiday_date'] ?? ''); ?>" required>
      <input type="text" name="description" placeholder="Holiday description" value="<?php echo htmlspecialchars($editHoliday['description'] ?? ''); ?>" required style="min-width:260px;">
      <?php if ($editHoliday): ?>
        <button class="btn btn-primary" type="submit" name="update_holiday">Update Holiday</button>
        <a href="manage_holidays.php"><button class="btn btn-muted" type="button">Cancel</button></a>
      <?php else: ?>
        <button class="btn btn-primary" type="submit" name="add_holiday">Add Holiday</button>
      <?php endif; ?>
    </form>

    <h3 class="section-title">Bulk Add Holidays (CSV)</h3>
    <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:16px;">
      <input type="file" name="bulk_file" accept=".csv" required>
      <button class="btn btn-navy" type="submit" name="bulk_add">Upload CSV</button>
      <small style="color:#667085;">Format: <code>YYYY-MM-DD,Description</code></small>
    </form>

    <div class="table-card" style="box-shadow:none;padding:0;">
      <table>
        <thead><tr><th>Date</th><th>Description</th><th>Action</th></tr></thead>
        <tbody>
        <?php
        if($holidays && $holidays->num_rows>0){
            while($row=$holidays->fetch_assoc()){
                echo "<tr>
                    <td>{$row['holiday_date']}</td>
                    <td>".htmlspecialchars($row['description'])."</td>
                    <td>
                        <a class='badge-btn badge-edit' href='?edit={$row['id']}'>Edit</a>
                        <a class='badge-btn badge-delete' href='?delete={$row['id']}' onclick=\"return confirm('Delete this holiday?')\">Delete</a>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No holidays found.</td></tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  </div>
</div></div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script>
</body></html>
