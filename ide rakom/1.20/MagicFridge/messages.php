<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config.php';
if (isset($_GET['debug'])) {
    $userId = (int)($_SESSION['user_id'] ?? 0);

    echo "<pre style='background:#111;color:#0f0;padding:12px;border-radius:10px;'>";
    echo "DATABASE(): " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    echo "SESSION user_id: " . $userId . "\n";

    // összes üzenet száma a DB-ben, AMIHEZ CSATLAKOZOL
    echo "messages COUNT(*): " . $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn() . "\n";

    // Nézzük a household_members oszlopnevet (member_id vs user_id) automatikusan:
    $cols = $pdo->query("SHOW COLUMNS FROM household_members")->fetchAll();
    $colNames = array_map(fn($c) => $c['Field'], $cols);
    echo "household_members columns: " . implode(", ", $colNames) . "\n";

    $householdIds = [];

    if (in_array('member_id', $colNames, true)) {
        $cols = $pdo->query("SHOW COLUMNS FROM household_members")->fetchAll();
        $colNames = array_map(fn($c) => $c['Field'], $cols);

        $memberCol = in_array('member_id', $colNames, true) ? 'member_id' : 'user_id';

        $stmt = $pdo->prepare("SELECT household_id FROM household_members WHERE $memberCol = ?");
        $stmt->execute([$userId]);
        $householdIds = array_map(fn($r) => (int)$r['household_id'], $stmt->fetchAll());

        $st->execute([$userId]);
        $householdIds = $st->fetchAll(PDO::FETCH_COLUMN);
        echo "Using member_id -> household_ids: " . json_encode($householdIds) . "\n";
    } elseif (in_array('user_id', $colNames, true)) {
        $st = $pdo->prepare("SELECT household_id FROM household_members WHERE user_id = ?");
        $st->execute([$userId]);
        $householdIds = $st->fetchAll(PDO::FETCH_COLUMN);
        echo "Using user_id -> household_ids: " . json_encode($householdIds) . "\n";
    } else {
        echo "ERROR: household_members table has no member_id/user_id column.\n";
    }

    // Mutassunk pár üzenetet is
    $st = $pdo->query("SELECT id, household_id, user_id, type, title, created_at FROM messages ORDER BY id DESC LIMIT 10");
    $rows = $st->fetchAll();
    echo "Last 10 messages:\n";
    foreach ($rows as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "</pre>";
    exit;
}

$userId = (int)$_SESSION['user_id'];

// 1) user háztartásai
$stmt = $pdo->prepare("SELECT household_id FROM household_members WHERE member_id = ?");
$stmt->execute([$userId]);
$householdIds = array_map(fn($r) => (int)$r['household_id'], $stmt->fetchAll());

// 2) üzenetek lekérése (háztartás + user)
$messages = [];

if (!empty($householdIds)) {
    $in = implode(',', array_fill(0, count($householdIds), '?'));

    $sql = "
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE household_id IN ($in) OR user_id = ?
        ORDER BY created_at DESC
        LIMIT 200
    ";

    $params = array_merge($householdIds, [$userId]);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $stmt->execute([$userId]);
    $messages = $stmt->fetchAll();
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

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title"><a href="dashboard.php">MagicFridge</a></span>
    </div>

    <div class="nav-links">
        <a href="recipes.php">Receptek</a>
        <a href="households.php">Háztartás</a>
        <a href="messages.php">Üzenetek</a>
        <a href="#" style="opacity:0.6;cursor:default;">Raktár (később)</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">
        <h1>Üzenetfal</h1>
        <p class="mt-3">Itt látod a háztartásodhoz és a fiókodhoz tartozó értesítéseket.</p>

        <div class="message-wall mt-3">
            <?php if (empty($messages)) : ?>
                <div class="message-empty">Nincs új üzenet. 🎉</div>
            <?php else : ?>
                <?php foreach ($messages as $m) : ?>
                    <?php
                        $type = htmlspecialchars($m['type']);
                        $title = htmlspecialchars($m['title']);
                        $body = htmlspecialchars($m['body']);
                        $time = htmlspecialchars($m['created_at']);
                        $isRead = (int)$m['is_read'] === 1;
                        $id = (int)$m['id'];
                    ?>
                    <div class="message-item message-<?= $type ?> <?= $isRead ? 'message-read' : 'message-unread' ?>">
                        <div class="message-top">
                            <div class="message-title"><?= $title ?></div>
                            <div class="message-time"><?= $time ?></div>
                        </div>
                        <div class="message-text"><?= $body ?></div>
                        <?php
$link = $m['link_url'] ?? '';
$isInvite = (is_string($link) && substr($link, 0, 7) === 'invite:');
$inviteId = $isInvite ? (int)substr($link, 7) : 0;
?>

<?php if ($isInvite && $inviteId > 0): ?>
    <div class="message-actions">
        <form method="post" action="invite_respond.php" class="message-form" style="display:flex; gap:8px;">
            <input type="hidden" name="invite_id" value="<?= $inviteId ?>">
            <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
            <button type="submit" name="action" value="accept" class="message-btn">Elfogad</button>
            <button type="submit" name="action" value="decline" class="message-btn">Elutasít</button>
        </form>
    </div>
<?php endif; ?>
                        <?php if (!$isRead) : ?>
                            <div class="message-actions">
                                <form method="post" action="message_read.php" class="message-form">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="message-btn">Megjelölés olvasottnak</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
