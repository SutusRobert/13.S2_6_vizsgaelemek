<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$fullName = $_SESSION['full_name'] ?? '';
$parts = explode(' ', trim($fullName));
$firstName = end($parts);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="recipes.php">Receptek</a>
        <a href="households.php">Háztartás</a>
        <a href="#" style="opacity:0.6;cursor:default;">Raktár (később)</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">
        <h1>Helló, <?= htmlspecialchars($firstName) ?>! 👋</h1>
        <p class="mt-3">Válassz a fenti menüpontok közül:</p>
        <ul class="list mt-3">
            <li><span>Receptek megtekintése, Spoonacular + saját receptek</span></li>
            <li><span>Háztartás: emberek hozzáadása, rangolás</span></li>
        </ul>
    </div>
</div>

</body>
</html>
