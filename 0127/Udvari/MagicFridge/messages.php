<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];

// DEBUG-hoz (ha nem kell, kiveheted)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Meghívás típusok (ha nálad más van, ide add hozzá) */
function isInviteType(?string $type): bool {
    if (!$type) return false;
    $t = strtolower(trim($type));
    return in_array($t, ['invite', 'household_invite', 'invitation'], true);
}

/** household_id kinyerés linkből: hid=12 vagy household_id=12 */
function extractHouseholdId(?string $link_url): ?int {
    if (!$link_url) return null;
    if (preg_match('/(?:hid|household_id)=([0-9]+)/i', $link_url, $m)) {
        return (int)$m[1];
    }
    return null;
}

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
        $ins = $pdo->prepare("
            INSERT INTO messages (household_id, user_id, type, title, body, link_url, is_read, created_at)
            VALUES (?, NULL, 'warning', 'Lejárt termék', ?, 'inventory.php', 0, NOW())
        ");
        $upd = $pdo->prepare("UPDATE inventory_items SET expired_notified = 1 WHERE id = ?");

        foreach ($expiredItems as $it) {
            $body = "A(z) „{$it['name']}” lejárt (" . date('Y-m-d', strtotime($it['expires_at'])) . ").";
            $ins->execute([(int)$it['household_id'], $body]);
            $upd->execute([(int)$it['id']]);
        }
    }
}

/**
 * ✅ ÜZENETEK LEKÉRÉSE:
 * - a user háztartásaihoz tartozó üzenetek VAGY usernek célzott üzenetek
 * - és CSAK azokat mutatjuk, amiket a user még nem olvasott (message_reads alapján)
 *
 * Ha nincs message_reads tábla, fallback: is_read = 0
 */
$messages = [];
if (!empty($householdIds)) {
    $placeholders = implode(',', array_fill(0, count($householdIds), '?'));

    try {
        $sql = "
            SELECT m.id, m.household_id, m.user_id, m.type, m.title, m.body, m.link_url, m.is_read, m.created_at
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE ((m.household_id IN ($placeholders)) OR (m.user_id = ?))
              AND mr.message_id IS NULL
            ORDER BY m.created_at DESC
            LIMIT 200
        ";
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$userId], $householdIds, [$userId]);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // fallback régi logika: közös is_read
        $sql = "
            SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
            FROM messages
            WHERE ((household_id IN ($placeholders)) OR (user_id = ?))
              AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 200
        ";
        $stmt = $pdo->prepare($sql);
        $params = array_merge($householdIds, [$userId]);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    // nincs household: csak user üzenetek
    try {
        $stmt = $pdo->prepare("
            SELECT m.id, m.household_id, m.user_id, m.type, m.title, m.body, m.link_url, m.is_read, m.created_at
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE m.user_id = ?
              AND mr.message_id IS NULL
            ORDER BY m.created_at DESC
            LIMIT 200
        ");
        $stmt->execute([$userId, $userId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare("
            SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
            FROM messages
            WHERE user_id = ?
              AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 200
        ");
        $stmt->execute([$userId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Üzenetek – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- ✅ Buborékok vissza -->
<div class="bubbles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">
            <a href="dashboard.php" class="brand-back">MagicFridge</a>
        </span>
    </div>
    <div class="nav-links">
        <a href="logout.php">Kijelentkezés</a>
    </div>
</div>

<div class="container">
    <div class="panel">
        <div class="panel-title">Üzenetfal</div>

        <div class="messages-list">
            <?php if (empty($messages)) : ?>
                <div class="message-empty">Nincs új üzenet. 🎉</div>
            <?php else : ?>
                <?php foreach ($messages as $m) : ?>
                    <?php
                        $type = htmlspecialchars($m["type"] ?? "info");
                        $title = htmlspecialchars($m["title"] ?? "");
                        $body  = htmlspecialchars($m["body"] ?? "");
                        $time  = htmlspecialchars($m["created_at"] ?? "");
                        $id = (int)($m["id"] ?? 0);

                        $isInvite = isInviteType($m["type"] ?? null);
                        $hid = extractHouseholdId($m["link_url"] ?? null);
                    ?>
                    <div class="message-item message-<?= $type ?> message-unread">
                        <div class="message-top">
                            <div class="message-title"><?= $title ?></div>
                            <div class="message-time"><?= $time ?></div>
                        </div>

                        <div class="message-text"><?= nl2br($body) ?></div>

                        <?php if (!empty($m["link_url"])) : ?>
                            <div class="message-link">
                                <a href="<?= htmlspecialchars($m["link_url"]) ?>" target="_blank">Link megnyitása</a>
                            </div>
                        <?php endif; ?>

                        <div class="message-actions">
                            <?php if ($isInvite) : ?>
                                <form method="post" action="message_read.php" class="message-form" style="display:inline;">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="household_id" value="<?= (int)($hid ?? 0) ?>">
                                    <button type="submit" class="message-btn">Elfogadom</button>
                                </form>

                                <form method="post" action="message_read.php" class="message-form" style="display:inline;">
                                    <input type="hidden" name="action" value="decline">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="household_id" value="<?= (int)($hid ?? 0) ?>">
                                    <button type="submit" class="message-btn">Elutasítom</button>
                                </form>

                                <?php if ($hid === null) : ?>
                                    <div style="margin-top:8px; opacity:.8;">
                                        (A meghívás linkjéből nem tudtam kiolvasni a household_id-t.)
                                    </div>
                                <?php endif; ?>

                            <?php else : ?>
                                <form method="post" action="message_read.php" class="message-form">
                                    <input type="hidden" name="action" value="read">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="message-btn">Megjelölés olvasottnak</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top:16px;">
            <a class="btn" href="dashboard.php">Vissza</a>
        </div>
    </div>
</div>

</body>
</html>
