<?php
// db.php - PDO connection for shop project

$host = 'localhost';
$db_name = 'shop';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepares when possible
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // In production, don't echo errors. Log instead.
    die("Database connection failed: " . $e->getMessage());
}
?>
