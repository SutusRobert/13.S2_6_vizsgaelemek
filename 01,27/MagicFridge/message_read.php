<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header("Location: messages.php");
    exit;
}

// üzenet betöltése
$stmt = $pdo->prepare("SELECT id, user_id, household_id FROM messages WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) {
    header("Location: messages.php");
    exit;
}

$allowed = false;

// saját üzenet
if (!empty($msg['user_id']) && (int)$msg['user_id'] === $userId) {
    $allowed = true;
}

// háztartás üzenet
if (!$allowed && !empty($msg['household_id'])) {
    $hid = (int)$msg['household_id'];
    $stmt = $pdo->prepare("SELECT 1 FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1");
    $stmt->execute([$hid, $userId]);
    $allowed = (bool)$stmt->fetchColumn();
}

if ($allowed) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: messages.php");
exit;
