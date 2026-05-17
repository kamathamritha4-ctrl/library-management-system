<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_books.php");
    exit();
}

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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper">
    <?php include("../includes/sidebar1.php"); ?>

    <div class="main">
        <div class="page-header">
            <h2>📚 Manage Books</h2>
            <a href="add_book.php"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add Book</button></a>
        </div>

        <div class="card">
            <form method="get" class="toolbar">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search accession, title, author, category or publisher">
                <button class="btn btn-navy" type="submit"><i class="fas fa-search"></i> Search</button>
                <a href="manage_books.php"><button class="btn btn-muted" type="button">Reset</button></a>
            </form>

            <form method="post" action="export_books.php">
                <div class="toolbar" style="justify-content:flex-end; margin-top:2px;">
                    <button class="btn btn-navy" type="submit"><i class="fas fa-file-export"></i> Export (Selected / All)</button>
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
                                        <a href='edit_book.php?id={$row['id']}' class='badge-btn badge-edit'>Edit</a>
                                        <a href='manage_books.php?delete={$row['id']}' class='badge-btn badge-delete' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</a>
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
</div>
<script>
document.getElementById("selectAll")?.addEventListener("change", function(){
    document.querySelectorAll("input[name='book_ids[]']").forEach(cb => cb.checked = this.checked);
});
function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("collapsed"); }
</script>
</body>
</html>
