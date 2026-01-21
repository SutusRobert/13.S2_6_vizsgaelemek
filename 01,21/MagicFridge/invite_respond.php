<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$inviteId = isset($_POST['invite_id']) ? (int)$_POST['invite_id'] : 0;
$action = $_POST['action'] ?? '';
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;

if ($inviteId <= 0 || !in_array($action, ['accept','decline'], true)) {
    header("Location: messages.php");
    exit;
}

// meghívó betöltése (csak annak engedjük, akit meghívtak)
$stmt = $pdo->prepare("
    SELECT id, household_id, invited_user_id, status
    FROM household_invites
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$inviteId]);
$inv = $stmt->fetch();

if (!$inv || (int)$inv['invited_user_id'] !== $userId) {
    header("Location: messages.php");
    exit;
}

if ($inv['status'] !== 'pending') {
    header("Location: messages.php?info=Ez a meghívó már nem függőben van.");
    exit;
}

if ($action === 'accept') {
    // tag hozzáadás, ha még nincs
    $stmt = $pdo->prepare("SELECT id FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1");
    $stmt->execute([(int)$inv['household_id'], $userId]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO household_members (household_id, member_id, role) VALUES (?, ?, 'tag')");
        $stmt->execute([(int)$inv['household_id'], $userId]);
    }

    // meghívó státusz
    $stmt = $pdo->prepare("UPDATE household_invites SET status='accepted', responded_at=NOW() WHERE id=?");
    $stmt->execute([$inviteId]);

    // opcionálisan a meghívó üzenetet jelöld olvasottnak
    if ($messageId > 0) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
    }

    header("Location: messages.php?info=Meghívó elfogadva.");
    exit;
}

if ($action === 'decline') {
    $stmt = $pdo->prepare("UPDATE household_invites SET status='declined', responded_at=NOW() WHERE id=?");
    $stmt->execute([$inviteId]);

    if ($messageId > 0) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$messageId, $userId]);
    }

    header("Location: messages.php?info=Meghívó elutasítva.");
    exit;
}
