<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];


// DEBUG-hoz érdemes ideiglenesen bekapcsolni (később kiveheted)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* User háztartásai (owner + member) */
$stmt = $pdo->prepare("
    SELECT id AS household_id FROM households WHERE owner_id = ?
    UNION
    SELECT household_id FROM household_members WHERE member_id = ?
");
$stmt->execute([$userId, $userId]);
$householdIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* Lejárt termék értesítések generálása (csak egyszer / tétel) */
if (!empty($householdIds)) {
    $placeholders = implode(',', array_fill(0, count($householdIds), '?'));

    $stmt = $pdo->prepare("
        SELECT id, household_id, name, expires_at
        FROM inventory_items
        WHERE household_id IN ($placeholders)
          AND expires_at IS NOT NULL
          AND expires_at < CURDATE()
          AND expired_notified = 0
        ORDER BY expires_at ASC
        LIMIT 200
    ");
    $stmt->execute($householdIds);
    $expiredItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($expiredItems)) {
        $insertMsg = $pdo->prepare("
            INSERT INTO messages (household_id, user_id, type, title, body, link_url, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        $markNotified = $pdo->prepare("
            UPDATE inventory_items
            SET expired_notified = 1
            WHERE id = ? AND household_id = ?
        ");

        foreach ($expiredItems as $it) {
            $hid = (int)$it['household_id'];
            $iid = (int)$it['id'];

            $name = (string)$it['name'];
            $exp  = (string)$it['expires_at'];

            $insertMsg->execute([
                $hid,
                $userId,
                "danger",
                "Lejárt termék a raktárban",
                "Lejárt: {$name} (lejárat: {$exp}). Nézd meg a raktárban.",
                "inventory.php"
            ]);

            $markNotified->execute([$iid, $hid]);
        }
    }
}


/* 1) User háztartásai */
$stmt = $pdo->prepare("SELECT household_id FROM household_members WHERE member_id = ?");
$stmt->execute([$userId]);
$householdIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

/* 2) Üzenetek lekérése (háztartás + user) */
$messages = [];

if (!empty($householdIds)) {
    $placeholders = implode(',', array_fill(0, count($householdIds), '?'));
    $sql = "
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE (household_id IN ($placeholders)) OR (user_id = ?)
        ORDER BY created_at DESC
        LIMIT 200
    ";
    $params = array_merge($householdIds, [$userId]);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Üzenetek – MagicFridge</title>
    <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">
            <a href="dashboard.php" class="brand-back">MagicFridge</a>
        </span>
    </div>
    <div class="nav-links">
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">
        <h1>Üzenetfal</h1>
        <p class="mt-3">Háztartásodhoz és a fiókodhoz tartozó rendszerüzenetek.</p>

        <div class="message-wall mt-3">
            <?php if (empty($messages)) : ?>
                <div class="message-empty">Nincs új üzenet. 🎉</div>
            <?php else : ?>
                <?php foreach ($messages as $m) : ?>
                    <?php
                        $type = htmlspecialchars($m["type"] ?? "info");
                        $title = htmlspecialchars($m["title"] ?? "");
                        $body  = htmlspecialchars($m["body"] ?? "");
                        $time  = htmlspecialchars($m["created_at"] ?? "");
                        $isRead = (int)($m["is_read"] ?? 0) === 1;
                        $id = (int)($m["id"] ?? 0);
                    ?>
                    <div class="message-item message-<?= $type ?> <?= $isRead ? 'message-read' : 'message-unread' ?>">
                        <div class="message-top">
                            <div class="message-title"><?= $title ?></div>
                            <div class="message-time"><?= $time ?></div>
                        </div>
                        <div class="message-text"><?= $body ?></div>

                        <div class="message-actions">
                            <?php if (!$isRead) : ?>
                                <form method="post" action="message_read.php" class="message-form">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="message-btn">Megjelölés olvasottnak</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>