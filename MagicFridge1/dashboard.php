<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Csak a keresztnév kiszedése
$fullName = $_SESSION['user']['full_name'];
$parts = explode(" ", trim($fullName));
$firstName = end($parts);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>MagicFridge – Főoldal</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            margin: 0;
            background: #f7f8fc;
            font-family: Arial, sans-serif;
        }

        /* Felső menü */
        .navbar {
            background: #1f2937;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-left img {
            height: 45px;
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: 0.2s;
        }

        .navbar-links a:hover {
            color: #60a5fa;
        }

        .logout-btn {
            background: #dc2626;
            padding: 8px 14px;
            color: white;
            border-radius: 5px;
            margin-left: 20px;
            text-decoration: none;
            font-size: 15px;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        /* Tartalom */
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        h1 {
            margin-top: 0;
            color: #1f2937;
        }

        p {
            color: #4b5563;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="navbar-left">
            <img src="assets/Logo.png" alt="MagicFridge Logo">
            <strong style="font-size: 20px;">MagicFridge</strong>
        </div>

        <div class="navbar-links">
            <a href="recipes.php">Receptek</a>
            <a href="haztartas.php">Háztartás</a>
            <a href="raktar.php">Raktár</a>
            <a href="logout.php" class="logout-btn">Kijelentkezés</a>
        </div>
    </div>

    <!-- TARTALOM -->
    <div class="container">
        <h1>Üdv újra, <?= htmlspecialchars($firstName) ?>! 👋</h1>
        <p>
            Örülünk, hogy ismét itt vagy a MagicFridge-ben.  
            A felső menü segítségével könnyedén navigálhatsz a receptek, a háztartási feladatok
            és a raktár kezelése között.
        </p>
        <p>
            Jó főzést és szervezést kívánunk!
        </p>
    </div>

</body>
</html>
