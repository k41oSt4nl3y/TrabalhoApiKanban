<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("CREATE DATABASE IF NOT EXISTS kanbandb");
    echo "Database created successfully\n";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
