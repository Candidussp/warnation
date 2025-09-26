<?php
$host = "localhost";
$dbname = "warnation";
$user = "root";   // XAMPP default
$pass = "";       // leave empty unless you set one

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // use native prepares
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In development it's OK to show the error; in production log it instead
    die("DB Connection failed: " . $e->getMessage());
}
?>
