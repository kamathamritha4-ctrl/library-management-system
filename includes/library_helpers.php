<?php

function table_exists(mysqli $conn, string $tableName): bool
{
    $safeName = $conn->real_escape_string($tableName);
    $result = $conn->query("SHOW TABLES LIKE '{$safeName}'");

    return $result && $result->num_rows > 0;
}

function is_library_holiday(mysqli $conn, string $dateYmd): bool
{
    if (!table_exists($conn, 'holidays')) {
        return false;
    }

    $stmt = $conn->prepare('SELECT id FROM holidays WHERE holiday_date = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $dateYmd);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result && $result->num_rows > 0;
}

function to_next_working_day(mysqli $conn, DateTime $date): DateTime
{
    $adjusted = clone $date;

    while ((int) $adjusted->format('w') === 0 || is_library_holiday($conn, $adjusted->format('Y-m-d'))) {
        $adjusted->modify('+1 day');
    }

    return $adjusted;
}

function calculate_due_date(mysqli $conn, string $issueDate, int $borrowDays = 15): string
{
    $due = DateTime::createFromFormat('Y-m-d', $issueDate);
    if (!$due) {
        return $issueDate;
    }

    $due->modify("+{$borrowDays} days");
    $adjustedDue = to_next_working_day($conn, $due);

    return $adjustedDue->format('Y-m-d');
}

function calculate_overdue_fine(mysqli $conn, string $dueDate, string $returnDate, int $dailyFine = 5): array
{
    $due = DateTime::createFromFormat('Y-m-d', $dueDate);
    $returned = DateTime::createFromFormat('Y-m-d', $returnDate);

    if (!$due || !$returned) {
        return ['days' => 0, 'fine' => 0, 'adjusted_due_date' => $dueDate];
    }

    $adjustedDue = to_next_working_day($conn, $due);

    if ($returned <= $adjustedDue) {
        return ['days' => 0, 'fine' => 0, 'adjusted_due_date' => $adjustedDue->format('Y-m-d')];
    }

    $days = (int) $adjustedDue->diff($returned)->format('%a');
    $fine = $days * $dailyFine;

    return ['days' => $days, 'fine' => $fine, 'adjusted_due_date' => $adjustedDue->format('Y-m-d')];
}
