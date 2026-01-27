<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_POST['action'] ?? 'read';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$householdId = isset($_POST['household_id']) ? (int)$_POST['household_id'] : 0;

if ($id > 0) {
    // 1) Accept: beléptetjük a háztartásba (ha van householdId)
    if ($action === 'accept' && $householdId > 0) {
        try {
            $st = $pdo->prepare("INSERT IGNORE INTO household_members (household_id, member_id) VALUES (?, ?)");
            $st->execute([$householdId, $userId]);
        } catch (Throwable $e) {
            // ha nincs IGNORE PDO driveren, akkor sima INSERT + try/catch:
            try {
                $st = $pdo->prepare("INSERT INTO household_members (household_id, member_id) VALUES (?, ?)");
                $st->execute([$householdId, $userId]);
            } catch (Throwable $e2) { /* no-op */ }
        }
    }

    // 2) Mindhárom akció esetén: olvasottá tesszük (userenként, message_reads)
    try {
        $st = $pdo->prepare("INSERT IGNORE INTO message_reads (user_id, message_id) VALUES (?, ?)");
        $st->execute([$userId, $id]);
    } catch (Throwable $e) {
        // fallback: ha nincs message_reads, akkor régi közös is_read
        $st = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $st->execute([$id]);
    }
}

header("Location: messages.php");
exit;
