<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$userId = (int)$_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? '';
$parts = explode(' ', trim($fullName));
$firstName = end($parts);

// --- háztartás id meghatározás (session -> db fallback)
$householdId = $_SESSION['household_id'] ?? null;

if (!$householdId) {
    try {
        $st = $pdo->prepare("SELECT household_id FROM household_members WHERE user_id = ? LIMIT 1");
        $st->execute([$userId]);
        $householdId = $st->fetchColumn();
    } catch (Throwable $e) {
        $householdId = null;
    }
}

// --- értesítések (nem olvasottak) a dashboard sávhoz
$unreadCount = 0;
$unreadPreview = [];

if ($householdId) {
    try {
        $st = $pdo->prepare("
    SELECT id, title, message, created_at
    FROM messages
    WHERE household_id = ?
      AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 3
");
$st->execute([$householdId]);
$unreadPreview = $st->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = count($unreadPreview);
        $st->execute([$userId, $householdId]);
        $unreadCount = (int)$st->fetchColumn();

        // top 3 nem olvasott preview
        $st = $pdo->prepare("
            SELECT m.id, m.title, m.body, m.created_at
            FROM messages m
            LEFT JOIN message_reads mr
              ON mr.message_id = m.id AND mr.user_id = ?
            WHERE m.household_id = ?
              AND mr.user_id IS NULL
            ORDER BY m.created_at DESC
            LIMIT 3
        ");
        $st->execute([$userId, $householdId]);
        $unreadPreview = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $unreadCount = 0;
        $unreadPreview = [];
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Buborékok minden oldalon -->
<div class="bubbles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <!-- Dashboardon ne legyen vissza gomb -->
        <span class="nav-title nav-title--static">MagicFridge</span>
    </div>

    <div class="nav-links">
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">
        <h1>Helló, <?= htmlspecialchars($firstName) ?>! 👋</h1>
        <p class="mt-3">Válassz egy modult:</p>
<div class="dash-fridge">
    <img src="assets/fridge.png" alt="Hűtő" class="dash-fridge-img">
    <div>
        <div class="dash-fridge-pill">🧊 Raktár</div>
        <div class="dash-fridge-title">Készlet & lejáratok</div>
        <div class="dash-fridge-desc">
            Kövesd a termékeket, mennyiségeket és a lejárati időket egy helyen.
        </div>
    </div>
</div>

        <!-- 4 egységes kártya + 1 wide (bevásárlólista) -->
        <div class="menu-grid mt-4">

            <a href="recipes.php" class="menu-tile">
                <div class="menu-icon">🍳</div>
                <div class="menu-title">Receptek</div>
                <div class="menu-desc">Nézd meg, mire elég a készlet.</div>
                <div class="menu-go">Megnyitás →</div>
            </a>

            <a href="messages.php" class="menu-tile">
                <div class="menu-icon">🔔</div>
                <div class="menu-title">Üzenetek</div>
                <div class="menu-desc">Lejáratok, figyelmeztetések, értesítések.</div>
                <div class="menu-go">Megnyitás →</div>
            </a>

            <a href="households.php" class="menu-tile">
                <div class="menu-icon">🧺</div>
                <div class="menu-title">Háztartás</div>
                <div class="menu-desc">Tagok kezelése, rangok, hozzáférés.</div>
                <div class="menu-go">Megnyitás →</div>
            </a>

            <a href="inventory.php" class="menu-tile">
                <div class="menu-icon">🧊</div>
                <div class="menu-title">Raktár</div>
                <div class="menu-desc">Készlet, mennyiség, lejárati dátumok.</div>
                <div class="menu-go">Megnyitás →</div>
            </a>

            <!-- Bevásárlólista: ugyanolyan, csak full széles + dupla magas -->
            <a href="shopping_list.php" class="menu-tile menu-tile--wide">
                <div class="menu-icon">🛒</div>
                <div class="menu-title">Bevásárlólista</div>
                <div class="menu-desc">Háztartás közös listája. Pipálás után mehet a raktárba.</div>
                <div class="menu-go">Megnyitás →</div>
            </a>

        </div>

        <!-- Értesítés blokk (NEM kattintható) -->
        <div class="dash-notify mt-4" aria-live="polite">
            <div class="dn-head">
                <div class="dn-left">
                    <span class="dn-ico">🔔</span>
                    <span class="dn-title">Friss értesítések</span>
                </div>
                <div class="dn-badge <?= $unreadCount > 0 ? 'is-on' : '' ?>">
                    <?= $unreadCount > 0 ? $unreadCount . ' új' : 'Nincs új' ?>
                </div>
            </div>

            <?php if ($unreadCount > 0): ?>
                <div class="dn-list">
                    <?php foreach ($unreadPreview as $m): ?>
                        <div class="dn-item">
                            <div class="dn-item-title"><?= htmlspecialchars($m['title'] ?? 'Értesítés') ?></div>
                            <div class="dn-item-desc">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($m['body'] ?? ''), 0, 110, '…', 'UTF-8')) ?>
                            </div>
                            <div class="dn-item-meta"><?= htmlspecialchars($m['created_at'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="dn-foot">
                    <span class="dn-hint">Ha az Üzeneteknél olvasottnak jelölöd, innen automatikusan eltűnik.</span>
                </div>
            <?php else: ?>
                <div class="dn-empty">Minden rendben — nincs új üzenet.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Bubik "ne induljanak nulláról" érzet: random indulási pont -->
<script>
document.querySelectorAll('.bubbles span').forEach(b => {
  const d = parseFloat(getComputedStyle(b).animationDuration) || 20;
  b.style.animationDelay = (Math.random() * d * -1).toFixed(2) + 's';
});
</script>

</body>
</html>
