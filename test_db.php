<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dsn = "mysql:host=localhost;port=3306;dbname=rebook_db";
    $pdo = new PDO($dsn, 'root', '');
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}