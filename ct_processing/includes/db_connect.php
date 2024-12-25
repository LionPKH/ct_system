<?php
// db_connect.php
$host = 'localhost';
$dbname = 'ct_processing_system';
$user = 'root';
$pass = '';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle error
    die("Database connection failed: " . $e->getMessage());
}
?>