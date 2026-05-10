<?php
include_once("../config/config.php");


if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = "";
$error = "";

function normalizeImportHeader($value) {
    return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value)));
}

function importRowHasHeader($row) {
    $headers = array_map('normalizeImportHeader', $row);
    return in_array('accessionno', $headers, true)
        || in_array('accessionnumber', $headers, true)
        || in_array('dateofaccession', $headers, true)
        || in_array('titlevolume', $headers, true);
}

function buildImportHeaderMap($row) {
    $aliases = [
        'date_of_accession' => ['dateofaccession', 'accessiondate', 'date'],
        'accession_no' => ['accessionno', 'accessionnumber', 'accession'],
        'subject' => ['subject', 'category'],
        'author' => ['author'],
        'title' => ['titlevolume', 'titleandvolume', 'title'],
        'publisher' => ['publisher'],
        'year' => ['year'],
        'price' => ['pricers', 'price', 'priceinrs'],
        'total' => ['total', 'totalcopies', 'copies'],
        'quantity' => ['available', 'quantity', 'availablecopies'],
        'bill_no' => ['billno', 'billnumber'],
        'bill_date' => ['billdate'],
        'supplier' => ['supplier'],
        'edition' => ['edition'],
        'remarks' => ['remarks', 'remark'],
    ];

    $map = [];
    foreach ($row as $index => $header) {
        $normalized = normalizeImportHeader($header);
        foreach ($aliases as $field => $fieldAliases) {
            if (in_array($normalized, $fieldAliases, true)) {
                $map[$field] = $index;
                break;
            }
        }
    }

    return $map;
}

function importValue($row, $map, $field, $fallbackIndex = null) {
    $index = $map[$field] ?? $fallbackIndex;
    return $index !== null ? ($row[$index] ?? null) : null;
}

function parseImportInt($value, $default = 0) {
    if ($value === null || trim((string) $value) === '') return $default;
    $clean = preg_replace('/[^0-9-]/', '', (string) $value);
    return $clean === '' || $clean === '-' ? $default : (int) $clean;
}

function parseImportFloat($value, $default = 0) {
    if ($value === null || trim((string) $value) === '') return $default;
    $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
    return $clean === '' || $clean === '-' ? $default : (float) $clean;
}

function importValueLooksLikeDate($value) {
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)
        || preg_match('/^\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}$/', $value)
        || preg_match('/[a-zA-Z]/', $value);
}

function parseImportDate($value, $default = null) {
    if ($value === null || trim((string) $value) === '') return $default ?: date('Y-m-d');

    if (is_numeric($value) && class_exists('\PhpOffice\PhpSpreadsheet\Shared\Date')) {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        } catch (Throwable $e) {
            // Fall through to strtotime for non-Excel numeric values.
        }
    }

    $timestamp = strtotime((string) $value);
    return $timestamp ? date('Y-m-d', $timestamp) : ($default ?: date('Y-m-d'));
}

function getBookColumns($conn) {
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM books");
    if (!$result) {
        throw new RuntimeException('Could not read books table columns: ' . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    return $columns;
}

function bindStatementParams($stmt, $types, $values) {
    $refs = [];
    foreach ($values as $index => $value) {
        $refs[$index] = &$values[$index];
    }
    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function insertBookRecord($conn, $bookColumns, $bookData) {
    $fields = [
        'date_of_accession' => ['s', $bookData['date_of_accession']],
        'accession_no' => ['i', $bookData['accession_no']],
        'category' => ['s', $bookData['category']],
        'author' => ['s', $bookData['author']],
        'title' => ['s', $bookData['title']],
        'publisher' => ['s', $bookData['publisher']],
        'year' => ['i', $bookData['year']],
        'price' => ['d', $bookData['price']],
        'total_copies' => ['i', $bookData['total_copies']],
        'quantity' => ['i', $bookData['quantity']],
        'bill_no' => ['s', $bookData['bill_no']],
        'bill_date' => ['s', $bookData['bill_date']],
        'supplier' => ['s', $bookData['supplier']],
        'edition' => ['s', $bookData['edition']],
        'remarks' => ['s', $bookData['remarks']],
    ];

    $columns = [];
    $placeholders = [];
    $types = '';
    $values = [];

    foreach ($fields as $column => [$type, $value]) {
        if (!isset($bookColumns[$column])) continue;
        $columns[] = $column;
        $placeholders[] = '?';
        $types .= $type;
        $values[] = $value;
    }

    $sql = 'INSERT INTO books (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Could not prepare book insert: ' . $conn->error);
    }

    bindStatementParams($stmt, $types, $values);
    return $stmt->execute();
}

if (isset($_POST['import']) && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
    try {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $rows = [];

        if ($ext === 'csv') {
            if (($handle = fopen($_FILES['file']['tmp_name'], 'r')) !== false) {
                while (($data = fgetcsv($handle)) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
        } elseif (in_array($ext, ['xlsx', 'xls'], true)) {
            if (PHP_VERSION_ID < 80200) {
                throw new RuntimeException('Excel import requires PHP 8.2+ in this setup. Please import CSV or upgrade PHP.');
            }

            require_once("../vendor/autoload.php");
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow(), null, true, false, false);
        } else {
            throw new RuntimeException('Unsupported file format. Please upload CSV, XLSX, or XLS.');
        }

        $headerMap = [];
        $startIndex = 0;
        if (!empty($rows) && importRowHasHeader($rows[0])) {
            $headerMap = buildImportHeaderMap($rows[0]);
            $startIndex = 1;
        }

        $bookColumns = getBookColumns($conn);
        $imported = 0;
        $skipped = 0;
        for ($index = $startIndex; $index < count($rows); $index++) {
            $row = $rows[$index];
            if (!array_filter($row, static fn ($value) => trim((string) $value) !== '')) {
                continue;
            }

            $legacyOrder = empty($headerMap) && !importValueLooksLikeDate($row[0] ?? '');
            $dateFallback = $legacyOrder ? 1 : 0;
            $accessionFallback = $legacyOrder ? 0 : 1;

            $accessionNo = parseImportInt(importValue($row, $headerMap, 'accession_no', $accessionFallback));
            if ($accessionNo <= 0) {
                $skipped++;
                continue;
            }

            $dateOfAccession = parseImportDate(importValue($row, $headerMap, 'date_of_accession', $dateFallback));
            $subject = trim((string) importValue($row, $headerMap, 'subject', 2));
            $author = trim((string) importValue($row, $headerMap, 'author', 3));
            $title = trim((string) importValue($row, $headerMap, 'title', 4));
            $publisher = trim((string) importValue($row, $headerMap, 'publisher', 5));
            $year = parseImportInt(importValue($row, $headerMap, 'year', 6), null);
            $price = parseImportFloat(importValue($row, $headerMap, 'price', 7));
            $total = max(1, parseImportInt(importValue($row, $headerMap, 'total', 8), 1));
            $quantity = parseImportInt(importValue($row, $headerMap, 'quantity'), $total);
            $quantity = max(0, min($quantity, $total));
            $billNo = trim((string) importValue($row, $headerMap, 'bill_no', $legacyOrder ? 9 : null));
            $billDateValue = importValue($row, $headerMap, 'bill_date', $legacyOrder ? 10 : null);
            $billDate = $billDateValue !== null && trim((string) $billDateValue) !== '' ? parseImportDate($billDateValue, null) : null;
            $supplier = trim((string) importValue($row, $headerMap, 'supplier', $legacyOrder ? 11 : 11));
            $edition = trim((string) importValue($row, $headerMap, 'edition', $legacyOrder ? 12 : 10));
            $remarks = trim((string) importValue($row, $headerMap, 'remarks', $legacyOrder ? 13 : 12));

            if ($subject === '' || $author === '' || $title === '') {
                $skipped++;
                continue;
            }

            $check = $conn->prepare("SELECT accession_no FROM books WHERE accession_no = ? LIMIT 1");
            if (!$check) {
                throw new RuntimeException('Could not prepare accession check: ' . $conn->error);
            }
            $check->bind_param("i", $accessionNo);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $skipped++;
                continue;
            }

            $bookData = [
                'date_of_accession' => $dateOfAccession,
                'accession_no' => $accessionNo,
                'category' => $subject,
                'author' => $author,
                'title' => $title,
                'publisher' => $publisher,
                'year' => $year,
                'price' => $price,
                'total_copies' => $total,
                'quantity' => $quantity,
                'bill_no' => $billNo,
                'bill_date' => $billDate,
                'supplier' => $supplier,
                'edition' => $edition,
                'remarks' => $remarks,
            ];

            if (insertBookRecord($conn, $bookColumns, $bookData)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        $success = "Bulk import completed successfully. Imported {$imported} book(s)." . ($skipped > 0 ? " Skipped {$skipped} duplicate or invalid row(s)." : "");
    } catch (Throwable $e) {
        $error = "Import failed: " . $e->getMessage();
    }
}

if (isset($_POST['save'])) {
    $dateOfAccession = $_POST['date_of_accession'] ?: date('Y-m-d');
    $accessionNo = (int) $_POST['accession_no'];
    $subject = trim($_POST['subject']);
    $author = trim($_POST['author']);
    $title = trim($_POST['title']);
    $publisher = trim($_POST['publisher']);
    $year = $_POST['year'] !== '' ? (int) $_POST['year'] : null;
    $price = $_POST['price'] !== '' ? (float) $_POST['price'] : 0;
    $total = max(1, (int) $_POST['total_copies']);
    $billNo = trim($_POST['bill_no']);
    $billDate = $_POST['bill_date'] ?: null;
    $supplier = trim($_POST['supplier']);
    $edition = trim($_POST['edition']);
    $remarks = trim($_POST['remarks']);

    $check = $conn->prepare("SELECT accession_no FROM books WHERE accession_no = ? LIMIT 1");
    if (!$check) {
        $error = "Error: " . $conn->error;
    } else {
        $check->bind_param("i", $accessionNo);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Accession number already exists.";
        } else {
            try {
                $bookColumns = getBookColumns($conn);
                $bookData = [
                    'date_of_accession' => $dateOfAccession,
                    'accession_no' => $accessionNo,
                    'category' => $subject,
                    'author' => $author,
                    'title' => $title,
                    'publisher' => $publisher,
                    'year' => $year,
                    'price' => $price,
                    'total_copies' => $total,
                    'quantity' => $total,
                    'bill_no' => $billNo,
                    'bill_date' => $billDate,
                    'supplier' => $supplier,
                    'edition' => $edition,
                    'remarks' => $remarks,
                ];
                if (insertBookRecord($conn, $bookColumns, $bookData)) $success = "Book added successfully."; else $error = "Error: " . $conn->error;
            } catch (Throwable $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><title>Add Book</title><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="admin-theme.css">
</head>
<body>
<div class="wrapper">
<?php include("../includes/sidebar1.php"); ?>
<div class="main">
  <div class="page-header"><h2>📘 Add New Book</h2></div>
  <div class="card">
    <?php if($success) echo "<div class='alert-success'>{$success}</div>"; ?>
    <?php if($error) echo "<div class='alert-error'>{$error}</div>"; ?>

    <h3 class="section-title">Bulk Import (Excel/CSV)</h3>
    <form method="post" enctype="multipart/form-data" class="actions" style="margin-bottom:20px;">
      <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
      <button type="submit" name="import" class="btn btn-primary">Import</button>
    </form>

    <form method="post">
      <div class="row">
        <div class="form-group"><label>Date of Accession</label><input type="date" name="date_of_accession" value="<?php echo date('Y-m-d');?>" required></div>
        <div class="form-group"><label>Accession Number</label><input type="number" name="accession_no" required></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Category</label><input type="text" name="subject" required></div>
        <div class="form-group"><label>Author</label><input type="text" name="author" required></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Title & Volume</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Publisher</label><input type="text" name="publisher"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Year</label><input type="number" name="year"></div>
        <div class="form-group"><label>Price Rs</label><input type="number" step="0.01" name="price"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" min="1" required></div>
        <div class="form-group"><label>Bill No</label><input type="text" name="bill_no"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Bill Date</label><input type="date" name="bill_date"></div>
        <div class="form-group"><label>Supplier</label><input type="text" name="supplier"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Edition</label><input type="text" name="edition"></div>
        <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="1"></textarea></div>
      </div>
      <div class="actions"><button class="btn btn-primary" type="submit" name="save">Save Book</button><button class="btn btn-muted" type="reset">Reset</button></div>
    </form>
  </div>
</div>
</div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');}</script>
</body>
</html>
