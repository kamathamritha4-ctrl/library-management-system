<?php
include_once("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS holidays (id INT AUTO_INCREMENT PRIMARY KEY, holiday_date DATE NOT NULL UNIQUE, description VARCHAR(255) NOT NULL)");

if (isset($_POST['add_holiday'])) {
    $holidayDate = $_POST['holiday_date'];
    $description = trim($_POST['description']);
    if ($holidayDate && $description) {
        $stmt = $conn->prepare("INSERT IGNORE INTO holidays (holiday_date, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $holidayDate, $description);
        $stmt->execute();
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_holidays.php");
    exit();
}

$holidays = $conn->query("SELECT * FROM holidays ORDER BY holiday_date");
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Holidays</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}body{background:#f4f6f9}.wrapper{display:flex;min-height:100vh}.main{flex:1;padding:28px}.card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 8px 20px rgba(0,0,0,.08)}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}.btn{padding:8px 12px;border:none;border-radius:7px;cursor:pointer;background:#2f80ed;color:white;text-decoration:none}.danger{background:#e74c3c}</style>
</head><body><div class="wrapper"><?php include("../includes/sidebar1.php"); ?><div class="main"><div class="card"><h2>📅 Manage Government Holidays</h2>
<form method="post" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;"><input type="date" name="holiday_date" required><input type="text" name="description" placeholder="Holiday description" required><button class="btn" type="submit" name="add_holiday">Add Holiday</button></form>
<table><thead><tr><th>Date</th><th>Description</th><th>Action</th></tr></thead><tbody>
<?php if($holidays && $holidays->num_rows>0){ while($row=$holidays->fetch_assoc()){ echo "<tr><td>{$row['holiday_date']}</td><td>".htmlspecialchars($row['description'])."</td><td><a class='btn danger' href='?delete={$row['id']}' onclick=\"return confirm('Delete this holiday?')\">Delete</a></td></tr>"; } } else { echo "<tr><td colspan='3'>No holidays added.</td></tr>"; } ?>
</tbody></table>
</div></div></div><script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script></body></html>
