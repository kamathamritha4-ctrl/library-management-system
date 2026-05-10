<?php
include_once("../config/config.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    $id = (int) $_POST['delete_book'];
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM books WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
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
                                $id = (int) $row['id'];
                                $accessionNo = (int) $row['accession_no'];
                                $title = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
                                $author = htmlspecialchars($row['author'] ?? '', ENT_QUOTES, 'UTF-8');
                                $category = htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8');
                                $publisher = htmlspecialchars($row['publisher'] ?? '', ENT_QUOTES, 'UTF-8');
                                $edition = htmlspecialchars($row['edition'] ?? '', ENT_QUOTES, 'UTF-8');
                                $price = htmlspecialchars($row['price'] ?? '', ENT_QUOTES, 'UTF-8');
                                $totalCopies = (int) $row['total_copies'];
                                $quantity = (int) $row['quantity'];
                                $dateOfAccession = htmlspecialchars($row['date_of_accession'] ?? '', ENT_QUOTES, 'UTF-8');
                                echo "<tr>
                                    <td><input type='checkbox' name='book_ids[]' value='{$id}'></td>
                                    <td>{$accessionNo}</td>
                                    <td>{$title}</td>
                                    <td>{$author}</td>
                                    <td>{$category}</td>
                                    <td>{$publisher}</td>
                                    <td>{$edition}</td>
                                    <td>₹ {$price}</td>
                                    <td>{$totalCopies}</td>
                                    <td>{$quantity}</td>
                                    <td>{$dateOfAccession}</td>
                                    <td>
                                        <a href='edit_book.php?accession_no={$accessionNo}' class='badge-btn badge-edit'>Edit</a>
                                        <button type='submit' name='delete_book' value='{$id}' class='badge-btn badge-delete' formaction='manage_books.php' formmethod='post' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</button>
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
