<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$hm_id = (int)($_POST['hm_id'] ?? 0);
if (!$hm_id) {
    header("Location: households.php?error=Hiányzó azonosító.");
    exit;
}

// member + household lekérése
$stmt = $pdo->prepare("
    SELECT hm.id, hm.role, h.owner_id
    FROM household_members hm
    JOIN households h ON hm.household_id = h.id
    WHERE hm.id = ?
");
$stmt->execute([$hm_id]);
$row = $stmt->fetch();

if (!$row || $row['owner_id'] != $_SESSION['user_id']) {
    header("Location: households.php?error=Nincs jogosultság.");
    exit;
}

$newRole = ($row['role'] === 'alap felhasználó') ? 'tag' : 'alap felhasználó';

$stmt = $pdo->prepare("UPDATE household_members SET role = ? WHERE id = ?");
$stmt->execute([$newRole, $hm_id]);

header("Location: households.php?info=Rang frissítve.");
exit;
