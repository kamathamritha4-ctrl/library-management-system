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
<title>Student Book Search</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(130deg,#1d3557,#457b9d);}
.overlay{min-height:100vh;background:rgba(0,0,0,.45);display:flex;flex-direction:column;align-items:center;padding:40px 16px;color:#fff}
.header{width:min(1000px,95%);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.logout{background:#dc3545;color:white;padding:9px 12px;border-radius:8px;text-decoration:none}
.search-box{background:rgba(255,255,255,.12);backdrop-filter:blur(7px);padding:18px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.25);width:min(1000px,95%)}
.search-row{display:flex;gap:10px;flex-wrap:wrap}
.search-box input{padding:12px 15px;flex:1;min-width:200px;border:none;border-radius:8px;font-size:15px}
.search-box button{padding:12px 18px;border:none;border-radius:8px;background:#0d6efd;color:white;font-weight:500;cursor:pointer}
.container{width:min(1000px,95%);background:white;color:black;padding:20px;border-radius:12px;box-shadow:0 15px 35px rgba(0,0,0,.25);margin-top:20px;overflow:auto}
table{width:100%;border-collapse:collapse;min-width:640px}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}th{background:#f8f9fa}
.badge{padding:5px 10px;border-radius:20px;color:white;font-size:12px;font-weight:500}.available{background:#198754}.not-available{background:#dc3545}
</style>
</head>
<body>
<div class="overlay">
    <div class="header">
        <h2>📘 Student Library Search</h2>
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
