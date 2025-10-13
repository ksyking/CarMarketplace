<?php
// app/db.php
session_start();
$dsn = 'mysql:host=localhost;dbname=autotrade;charset=utf8mb4';
$user = 'root';   // default for XAMPP
$pass = '';       // default for XAMPP
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];
try { $pdo = new PDO($dsn, $user, $pass, $options); }
catch (PDOException $e) { exit('Database connection failed'); }
