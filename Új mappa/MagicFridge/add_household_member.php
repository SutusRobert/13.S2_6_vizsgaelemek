<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
if ($fullName === '') {
    header("Location: households.php?error=Adj meg egy nevet.");
    exit;
}

// háztartás lekérése
$stmt = $pdo->prepare("SELECT id FROM households WHERE owner_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$household = $stmt->fetch();
if (!$household) {
    header("Location: households.php?error=Nincs háztartás.");
    exit;
}

// user keresése név alapján
$stmt = $pdo->prepare("SELECT id FROM users WHERE full_name = ? LIMIT 1");
$stmt->execute([$fullName]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: households.php?error=Nincs ilyen nevű regisztrált felhasználó.");
    exit;
}

// már tag?
$stmt = $pdo->prepare("SELECT id FROM household_members WHERE household_id = ? AND member_id = ?");
$stmt->execute([$household['id'], $user['id']]);
if ($stmt->fetch()) {
    header("Location: households.php?error=Ez a felhasználó már tag.");
    exit;
}

// tag hozzáadása
$stmt = $pdo->prepare("INSERT INTO household_members (household_id, member_id, role) VALUES (?, ?, 'tag')");
$stmt->execute([$household['id'], $user['id']]);

header("Location: households.php?info=Felhasználó hozzáadva.");
exit;
