<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "db.php";

$userId = (int)$_SESSION['user_id'];

$fullName = $_SESSION['full_name'] ?? '';
$parts = explode(' ', trim($fullName));
$firstName = end($parts);

/* 1) User háztartásai */
$householdIds = [];
$stmt = $mysqli->prepare("SELECT household_id FROM household_members WHERE member_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $householdIds[] = (int)$row['household_id'];
}
$stmt->close();

/* 2) Üzenetek lekérése (háztartás + user) */
$messages = [];

if (!empty($householdIds)) {
    $placeholders = implode(',', array_fill(0, count($householdIds), '?'));
    $types = str_repeat('i', count($householdIds));

    $sql = "
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE (household_id IN ($placeholders)) OR (user_id = ?)
        ORDER BY created_at DESC
        LIMIT 200
    ";

    $stmt = $mysqli->prepare($sql);

    // bind: householdIds..., userId
    $bindTypes = $types . "i";
    $params = array_merge($householdIds, [$userId]);

    $stmt->bind_param($bindTypes, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
} else {
    // ha nincs háztartás, csak saját üzenetek
    $stmt = $mysqli->prepare("
        SELECT id, household_id, user_id, type, title, body, link_url, is_read, created_at
        FROM messages
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
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
        <p class="mt-3">Háztartásodhoz és a fiókodhoz tartozó rendszerüzenetek.</p>

        <div class="message-wall mt-3">
            <?php if (empty($messages)) : ?>
                <div class="message-empty">Nincs új üzenet. 🎉</div>
            <?php else : ?>
                <?php foreach ($messages as $m) : ?>
                    <?php
                        $type = htmlspecialchars($m["type"]);
                        $title = htmlspecialchars($m["title"]);
                        $body  = htmlspecialchars($m["body"]);
                        $time  = htmlspecialchars($m["created_at"]);
                        $isRead = (int)$m["is_read"] === 1;
                        $id = (int)$m["id"];
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
