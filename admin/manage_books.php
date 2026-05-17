<?php
include_once("../config/config.php");

function has_column(mysqli $conn, string $table, string $column): bool {
    $tableEsc = $conn->real_escape_string($table);
    $columnEsc = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if(isset($_GET['delete'])) {
    $deleteCol = has_column($conn, 'books', 'id') ? 'id' : 'accession_no';
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM books WHERE {$deleteCol} = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: manage_books.php");
    exit();
}

$search = trim($_GET['q'] ?? '');
$orderCol = has_column($conn, 'books', 'id') ? 'id' : 'accession_no';
$searchableColumns = [];
foreach (['accession_no', 'title', 'author', 'category', 'publisher'] as $col) {
    if (has_column($conn, 'books', $col)) {
        $searchableColumns[] = $col;
    }
}

if ($search !== '') {
    $like = "%{$search}%";
    if (!empty($searchableColumns)) {
        $where = implode(' OR ', array_map(static fn($col) => "{$col} LIKE ?", $searchableColumns));
        $sql = "SELECT * FROM books WHERE {$where} ORDER BY {$orderCol}";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = str_repeat('s', count($searchableColumns));
            $values = array_fill(0, count($searchableColumns), $like);
            $bindArgs = [$types];
            foreach ($values as $k => $v) {
                $bindArgs[] = &$values[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindArgs);
            $stmt->execute();
            $books = $stmt->get_result();
        } else {
            $books = $conn->query("SELECT * FROM books ORDER BY {$orderCol}");
        }
    } else {
        $books = $conn->query("SELECT * FROM books ORDER BY {$orderCol}");
    }
} else {
    $books = $conn->query("SELECT * FROM books ORDER BY {$orderCol}");
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
                                $bookId = (int) ($row['id'] ?? ($row['accession_no'] ?? 0));
                                $accessionNo = htmlspecialchars((string) ($row['accession_no'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $title = htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $author = htmlspecialchars((string) ($row['author'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $category = htmlspecialchars((string) ($row['category'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $publisher = htmlspecialchars((string) ($row['publisher'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $edition = htmlspecialchars((string) ($row['edition'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $price = number_format((float) ($row['price'] ?? 0), 2);
                                $totalCopies = (int) ($row['total_copies'] ?? 0);
                                $quantity = (int) ($row['quantity'] ?? 0);
                                $dateOfAccession = htmlspecialchars((string) ($row['date_of_accession'] ?? ''), ENT_QUOTES, 'UTF-8');

                                echo "<tr>
                                    <td><input type='checkbox' name='book_ids[]' value='{$bookId}'></td>
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
                                        <a href='edit_book.php?id={$bookId}' class='badge-btn badge-edit'>Edit</a>
                                        <a href='manage_books.php?delete={$bookId}' class='badge-btn badge-delete' onclick=\"return confirm('Are you sure you want to delete this book?')\">Delete</a>
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
