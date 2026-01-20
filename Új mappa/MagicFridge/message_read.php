<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once "db.php";

$userId = (int)$_SESSION['user_id'];
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header("Location: messages.php");
    exit;
}

/*
  Biztonság: csak akkor jelölhessen olvasottnak, ha:
  - a message user_id = ő
  VAGY
  - a message household_id olyan háztartás, aminek tagja
*/
$stmt = $mysqli->prepare("
    SELECT m.id, m.user_id, m.household_id
    FROM messages m
    WHERE m.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$msg = $res->fetch_assoc();
$stmt->close();

if (!$msg) {
    header("Location: messages.php");
    exit;
}

$allowed = false;
if (!empty($msg['user_id']) && (int)$msg['user_id'] === $userId) {
    $allowed = true;
} elseif (!empty($msg['household_id'])) {
    $hid = (int)$msg['household_id'];
    $stmt = $mysqli->prepare("SELECT 1 FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1");
    $stmt->bind_param("ii", $hid, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $allowed = (bool)$res->fetch_row();
    $stmt->close();
}

if ($allowed) {
    $stmt = $mysqli->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: messages.php");
exit;
