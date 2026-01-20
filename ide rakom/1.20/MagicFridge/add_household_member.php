<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
    header("Location: households.php?error=Adj meg egy email címet.");
    exit;
}

// háztartás lekérése (nálad az owner_id-s háztartás az alap)
$stmt = $pdo->prepare("SELECT id, name FROM households WHERE owner_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$household = $stmt->fetch();

if (!$household) {
    header("Location: households.php?error=Nincs háztartás.");
    exit;
}

// user keresése email alapján
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: households.php?error=Nincs ilyen emaillel regisztrált felhasználó.");
    exit;
}

// ne hívd meg saját magad
if ((int)$user['id'] === (int)$_SESSION['user_id']) {
    header("Location: households.php?error=Magadat nem tudod meghívni.");
    exit;
}

// már tag?
$stmt = $pdo->prepare("SELECT id FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1");
$stmt->execute([$household['id'], $user['id']]);
if ($stmt->fetch()) {
    header("Location: households.php?error=Ez a felhasználó már tag.");
    exit;
}

// van már pending meghívó?
$stmt = $pdo->prepare("
    SELECT id FROM household_invites
    WHERE household_id = ? AND invited_user_id = ? AND status = 'pending'
    LIMIT 1
");
$stmt->execute([$household['id'], $user['id']]);
if ($stmt->fetch()) {
    header("Location: households.php?error=Már van függőben lévő meghívó ennek a felhasználónak.");
    exit;
}

// meghívó létrehozása
$stmt = $pdo->prepare("
    INSERT INTO household_invites (household_id, invited_user_id, invited_by_user_id, status)
    VALUES (?, ?, ?, 'pending')
");
$stmt->execute([$household['id'], $user['id'], $_SESSION['user_id']]);
$inviteId = (int)$pdo->lastInsertId();

// üzenet létrehozása a meghívottnak a messages táblába
$inviterName = $_SESSION['full_name'] ?? 'Valaki';
$title = "Háztartás meghívó";
$body  = $inviterName . " meghívott a(z) \"" . $household['name'] . "\" háztartásba.";

$stmt = $pdo->prepare("
    INSERT INTO messages (user_id, type, title, body, link_url, is_read)
    VALUES (?, 'info', ?, ?, ?, 0)
");
$stmt->execute([
    $user['id'],
    $title,
    $body,
    "invite:" . $inviteId
]);

header("Location: households.php?info=Meghívó elküldve email alapján: " . urlencode($email));
exit;
