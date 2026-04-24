<?php
include("../config/config.php");

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty')) {
    header("Location: ../index.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$books = null;

if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT accession_no, category, author, title, quantity FROM books WHERE title LIKE ? OR author LIKE ? OR category LIKE ? OR accession_no LIKE ? ORDER BY title");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $books = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<title>Faculty Book Search</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root{--primary:#E24C24;--primary2:#C93E18;--navy:#1F2940}
*{box-sizing:border-box}
body{margin:0;font-family:'Poppins','Segoe UI',Arial,sans-serif;background:radial-gradient(circle at top right,#ffe9e1 0%,#f7ece8 35%,#f5f7fb 78%)}
.overlay{min-height:100vh;background:rgba(20,29,48,.22);display:flex;flex-direction:column;align-items:center;padding:34px 16px;color:#fff}
.header{width:min(1080px,95%);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.logout{background:#1F2940;color:white;padding:9px 13px;border-radius:10px;text-decoration:none;font-weight:600}
.search-box{background:rgba(255,255,255,.2);backdrop-filter:blur(9px);padding:18px;border-radius:14px;box-shadow:0 14px 30px rgba(0,0,0,.22);width:min(1080px,95%)}
.search-row{display:flex;gap:10px;flex-wrap:wrap}
.search-box input{padding:12px 14px;flex:1;min-width:220px;border:none;border-radius:10px;font-size:15px}
.search-box button{padding:12px 18px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary2));color:white;font-weight:600;cursor:pointer}
.container{width:min(1080px,95%);background:white;color:#111827;padding:20px;border-radius:14px;box-shadow:0 15px 34px rgba(0,0,0,.2);margin-top:18px;overflow:auto}
.container h3{margin:0 0 10px}
table{width:100%;border-collapse:collapse;min-width:640px}
th,td{padding:12px;border-bottom:1px solid #edf2f7;text-align:left}th{background:#f8fafc;color:#334155}
.badge{padding:5px 10px;border-radius:20px;color:white;font-size:12px;font-weight:600}.available{background:#16a34a}.not-available{background:#dc2626}
</style>
</head>
<body>
<div class="overlay">
    <div class="header">
        <div style="display:flex; align-items:center; gap:10px;"><img src="https://trishaedu.com/Trisha-Logo.png" alt="Trisha Logo" style="width:44px;height:44px;border-radius:10px;background:white;object-fit:contain;"><h2>📘 Trisha Faculty Library Search</h2></div>
        <a class="logout" href="../logout.php">Logout</a>
    </div>

    <div class="search-box">
        <form method="get" class="search-row">
            <input type="text" name="search" placeholder="Search by Accession No, Subject, Author, or Title" value="<?php echo htmlspecialchars($search); ?>" required>
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if ($books !== null): ?>
    <div class="container">
        <h3>Search Results</h3>
        <table>
            <thead>
            <tr>
                <th>Accession No</th>
                <th>Subject</th>
                <th>Author</th>
                <th>Title</th>
                <th>Copies Available</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($books->num_rows > 0) {
                while ($row = $books->fetch_assoc()) {
                    $statusClass = ((int) $row['quantity'] > 0) ? "available" : "not-available";
                    $statusText = ((int) $row['quantity'] > 0) ? "Available" : "Not Available";
                    echo "<tr>
                            <td>" . htmlspecialchars($row['accession_no']) . "</td>
                            <td>" . htmlspecialchars($row['category']) . "</td>
                            <td>" . htmlspecialchars($row['author']) . "</td>
                            <td>" . htmlspecialchars($row['title']) . "</td>
                            <td>" . (int) $row['quantity'] . "</td>
                            <td><span class='badge {$statusClass}'>{$statusText}</span></td>
                         </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No books found.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
