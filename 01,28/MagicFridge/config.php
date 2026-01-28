<?php

// Minden oldal behúzza, ezért itt állítjuk a PDO-t
$host = 'localhost';
$db   = 'magicfridge';
$user = 'root';
$pass = 'mysql'; // ha van jelszó, ide írd be

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Adatbázis hiba: " . $e->getMessage());
}

// Sessiont NEM itt indítjuk, hanem a lapok elején: session_start();