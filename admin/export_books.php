<?php
include_once("../config/config.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=books_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Date of Accession',
    'Accession No',
    'Subject',
    'Author',
    'Title & Volume',
    'Publisher',
    'Year',
    'Price Rs',
    'Total',
    'Available',
    'Edition',
    'Supplier',
    'Remarks'
]);

$result = $conn->query("SELECT date_of_accession, accession_no, category, author, title, publisher, year, price, total_copies, quantity, edition, supplier, remarks FROM books ORDER BY accession_no");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['date_of_accession'],
            $row['accession_no'],
            $row['category'],
            $row['author'],
            $row['title'],
            $row['publisher'],
            $row['year'],
            $row['price'],
            $row['total_copies'],
            $row['quantity'],
            $row['edition'],
            $row['supplier'],
            $row['remarks']
        ]);
    }
}

fclose($output);
exit();
