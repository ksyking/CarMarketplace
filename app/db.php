<?php
session_start();

$dsn  = 'mysql:host=localhost;dbname=autotrade;charset=utf8mb4';
$user = 'root';
$pass = ''; // XAMPP default

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  exit('Database connection failed: ' . $e->getMessage());
}