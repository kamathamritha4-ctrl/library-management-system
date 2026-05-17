<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'library_db';
$dbPort = (int) (getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conn->connect_error) {
    // fallback for local XAMPP setups that use localhost explicitly
    if ($dbHost !== 'localhost') {
        $fallbackConn = @new mysqli('localhost', $dbUser, $dbPass, $dbName, $dbPort);
        if (!$fallbackConn->connect_error) {
            $conn = $fallbackConn;
        }
    }
}

if ($conn->connect_error) {
    die(
        'Database connection failed. Please ensure MySQL is running in XAMPP and verify DB settings in config/config.php ' .
        '(host/user/password/database/port). Original error: ' . $conn->connect_error
    );
}

$conn->set_charset('utf8mb4');
?>
