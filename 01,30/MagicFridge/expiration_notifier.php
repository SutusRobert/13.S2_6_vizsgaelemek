<?php
// expiration_notifier.php
// Feladat: lejárt inventory tételekből egyszeri "messages" értesítés generálása.

function runExpirationNotifier(PDO $pdo, int $userId): void
{
    // 1) user háztartásai (ugyanaz a logika, mint messages.php-ben)
    $stmt = $pdo->prepare("SELECT household_id FROM household_members WHERE member_id = ?");
    $stmt->execute([$userId]);
    $householdIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($householdIds)) {
        return;
    }

    // 2) keressük azokat a tételeket, amik:
    // - expires_at nem NULL
    // - már lejártak
    // - még nem küldtünk róluk üzenetet
    $placeholders = implode(',', array_fill(0, count($householdIds), '?'));
    $sql = "
        SELECT id, household_id, name, expires_at
        FROM inventory_items
        WHERE household_id IN ($placeholders)
          AND expires_at IS NOT NULL
          AND expires_at < CURDATE()
          AND expired_notified = 0
        ORDER BY expires_at ASC
        LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($householdIds);
    $expiredItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$expiredItems) return;

    // 3) beszúrjuk üzenetként + visszajelöljük a terméket, hogy már értesítettünk
    $insertMsg = $pdo->prepare("
        INSERT INTO messages (household_id, user_id, type, title, body, link_url, is_read, created_at)
        VALUES (?, NULL, ?, ?, ?, ?, 0, NOW())
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

        $type  = "danger";
        $title = "Lejárt termék a raktárban";
        $body  = "Lejárt: {$name} (lejárat: {$exp}). Nézd meg a raktárban.";
        $link  = "inventory.php";

        $insertMsg->execute([$hid, $type, $title, $body, $link]);
        $markNotified->execute([$iid, $hid]);
    }
}
