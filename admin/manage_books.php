<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Delete logic
if(isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_books.php");
    exit();
}

// Search + Fetch books
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT * FROM books WHERE accession_no LIKE ? OR title LIKE ? OR author LIKE ? OR category LIKE ? OR publisher LIKE ? ORDER BY id");
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $books = $stmt->get_result();
} else {
    $books = $conn->query("SELECT * FROM books ORDER BY id");
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manage Books</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f4f6f9;
}

.wrapper {
    display: flex;
    min-height: 100vh;
}

/* ===== Sidebar ===== */
.sidebar {
    width: 240px;
    background: #2c3e50;
    padding: 25px 15px;
    color: white;
    transition: 0.3s ease;
    overflow: hidden;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.collapse-btn {
    background: transparent;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 10px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #ecf0f1;
    border-radius: 8px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: #34495e;
}

/* Collapsed */
.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed h3 {
    display: none;
}

.sidebar.collapsed a span {
    display: none;
}

.sidebar.collapsed .sidebar-header {
    justify-content: center;
}

/* ===== Main ===== */
.main {
    flex: 1;
    padding: 40px;
}

.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.main-header h2 {
    font-weight: 600;
    color: #333;
}

.add-btn {
    background: #27ae60;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.add-btn:hover {
    background: #219150;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 12px;
    text-align: left;
    font-size: 14px;
}

table th {
    background: #f8f9fa;
    font-weight: 600;
}

table tr {
    border-bottom: 1px solid #eee;
}

table tr:hover {
    background: #f9f9f9;
}

/* Buttons */
.action-btn {
    padding: 6px 10px;
    font-size: 12px;
    text-decoration: none;
    border-radius: 6px;
    color: white;
    margin-right: 5px;
}

.edit-btn {
    background: #f39c12;
}

.edit-btn:hover {
    background: #d68910;
}

.delete-btn {
    background: #e74c3c;
}

.delete-btn:hover {
    background: #c0392b;
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- Sidebar -->
    <?php include("../includes/sidebar1.php"); ?>

    <!-- Main Content -->
    <div class="main">

        <div class="main-header">
            <h2>📚 Manage Books</h2>
            <div style="display:flex; gap:10px;">
                <a href="add_book.php">
                    <button class="add-btn">
                        <i class="fas fa-plus"></i> Add Book
                    </button>
                </a>
            </div>
        </div>

        <form method="get" style="display:flex; gap:10px; margin-bottom:16px;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by accession, title, author, category, publisher" style="flex:1; max-width:520px; padding:10px 12px; border:1px solid #d5deeb; border-radius:8px;">
            <button class="add-btn" type="submit" style="background:#2f80ed;"><i class="fas fa-search"></i> Search</button>
            <a href="manage_books.php"><button class="add-btn" type="button" style="background:#95a5a6;">Reset</button></a>
        </form>

        <form method="post" action="export_books.php">
        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
            <button class="add-btn" type="submit" style="background:#2f80ed;">
                <i class="fas fa-file-export"></i> Export (Selected / All)
            </button>
        </div>
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Accession No</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Publisher</th>
                        <th>Edition</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Available</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                if($books && $books->num_rows > 0) {
                    while($row = $books->fetch_assoc()) {
                        echo "<tr>
                                <td><input type='checkbox' name='book_ids[]' value='{$row['id']}'></td>
                                <td>{$row['accession_no']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['author']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['publisher']}</td>
                                <td>{$row['edition']}</td>
                                <td>₹ {$row['price']}</td>
                                <td>{$row['total_copies']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['date_of_accession']}</td>
                                <td>
                                    <a href='edit_book.php?id={$row['id']}' class='action-btn edit-btn'>Edit</a>
                                    <a href='manage_books.php?delete={$row['id']}'
                                       class='action-btn delete-btn'
                                       onclick=\"return confirm('Are you sure you want to delete this book?')\">
                                       Delete
                                    </a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='12'>No books found</td></tr>";
                }
                ?>

                </tbody>
            </table>
        </div>
        </form>

    </div>

</div>

<script>
document.getElementById("selectAll")?.addEventListener("change", function(){
    document.querySelectorAll("input[name=\"book_ids[]\"]").forEach(cb => cb.checked = this.checked);
});

function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
</script>

</body>
</html>