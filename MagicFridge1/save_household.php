<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    header("Location: create_household.php?error=" . urlencode("A név megadása kötelező."));
    exit;
}

// Ellenőrizzük, hogy van-e már ilyen név ugyanannál a usernél
$check = $pdo->prepare("SELECT id FROM households WHERE user_id = ? AND name = ?");
$check->execute([$_SESSION['user_id'], $name]);

if ($check->rowCount() > 0) {
    header("Location: create_household.php?error=" . urlencode("Már van ilyen nevű háztartásod."));
    exit;
}

$insert = $pdo->prepare("INSERT INTO households (user_id, name) VALUES (?, ?)");
$insert->execute([$_SESSION['user_id'], $name]);

header("Location: create_household.php?success=" . urlencode("Háztartás sikeresen létrehozva."));
exit;
