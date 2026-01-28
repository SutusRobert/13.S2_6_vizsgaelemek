<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$userId = (int)$_SESSION['user_id'];

$fullName = $_SESSION['full_name'] ?? '';
$parts = preg_split('/\s+/', trim($fullName));
$firstName = $parts ? end($parts) : '';
if (!$firstName) $firstName = 'Felhasználó';

if (isset($pdo)) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

/**
 * Háztartások
 */
$householdIds = [];
$sessionHid = $_SESSION['household_id'] ?? null;

if (!empty($sessionHid)) {
    $householdIds = [(int)$sessionHid];
} else {
    try {
        $st = $pdo->prepare("
            SELECT id AS household_id FROM households WHERE owner_id = ?
            UNION
            SELECT household_id FROM household_members WHERE member_id = ?
        ");
        $st->execute([$userId, $userId]);
        $householdIds = array_values(array_filter(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {
        $householdIds = [];
    }
}

/**
 * Unread üzenetek (count + top3)
 */
$unreadCount = 0;
$unreadPreview = [];

try {
    if (!empty($householdIds)) {
        $placeholders = implode(',', array_fill(0, count($householdIds), '?'));

        $sqlCount = "
            SELECT COUNT(*)
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE ((m.household_id IN ($placeholders)) OR (m.user_id = ?))
              AND mr.message_id IS NULL
        ";
        $st = $pdo->prepare($sqlCount);
        $params = array_merge([$userId], $householdIds, [$userId]);
        $st->execute($params);
        $unreadCount = (int)$st->fetchColumn();

        $sqlPrev = "
            SELECT m.id, m.title, m.body, m.created_at, m.type
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE ((m.household_id IN ($placeholders)) OR (m.user_id = ?))
              AND mr.message_id IS NULL
            ORDER BY m.created_at DESC
            LIMIT 3
        ";
        $st = $pdo->prepare($sqlPrev);
        $st->execute($params);
        $unreadPreview = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sqlCount = "
            SELECT COUNT(*)
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE m.user_id = ?
              AND mr.message_id IS NULL
        ";
        $st = $pdo->prepare($sqlCount);
        $st->execute([$userId, $userId]);
        $unreadCount = (int)$st->fetchColumn();

        $sqlPrev = "
            SELECT m.id, m.title, m.body, m.created_at, m.type
            FROM messages m
            LEFT JOIN message_reads mr
                   ON mr.message_id = m.id AND mr.user_id = ?
            WHERE m.user_id = ?
              AND mr.message_id IS NULL
            ORDER BY m.created_at DESC
            LIMIT 3
        ";
        $st = $pdo->prepare($sqlPrev);
        $st->execute([$userId, $userId]);
        $unreadPreview = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $unreadCount = 0;
    $unreadPreview = [];
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
      /* Bubik tényleg háttér */
      .bubbles{
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
      }
      .navbar, .dash-row { position: relative; z-index: 2; }

      /* ✅ EGYENLETES: középre igazított “sáv”, azonos bal/jobb padding,
         azonos gap a dobozok között */
      .dash-row{
        max-width: 1750px;
        margin: 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 28px;
        padding: 18px 28px 40px; /* bal/jobb ugyanannyi */
        box-sizing: border-box;
      }

      /* bal/jobb fix széles, közép rugalmas */
      .dash-left, .dash-side{
        width: 420px;
        flex: 0 0 420px;
        min-width: 0;
      }

      .dash-mid{
        flex: 1 1 auto;
        min-width: 560px;
        max-width: 980px;
      }

      /* a main-wrapper ne “középre húzza” külön a cardot */
      .main-wrapper{ margin: 0; width: 100%; }

      /* jobb oldali box belső spacing */
      .side-card{ padding: 18px; }
      .side-stack{ display: grid; gap: 14px; }

      /* mobilon egymás alá */
      @media (max-width: 1200px){
        .dash-row{
          flex-direction: column;
          align-items: center;
          justify-content: flex-start;
          max-width: 100%;
        }
        .dash-left, .dash-side{ width: min(520px, 100%); flex-basis: auto; }
        .dash-mid{ min-width: 0; max-width: 100%; }
      }
    </style>
</head>
<body>

<div class="bubbles" aria-hidden="true" id="bubbles">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title nav-title--static">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="dash-row">

  <!-- BAL: HŰTŐ BOX -->
  <div class="dash-left">
    <div class="fridge-card">
      <div class="fridge-hero">
        <img src="assets/fridge.png" alt="Hűtő" class="fridge-img">
      </div>

      <div class="fridge-body">
        <span class="pill">🧊 Raktár</span>
        <h3 style="margin-top:10px;">Készlet & lejáratok</h3>
        <p class="small mt-2">
          Kövesd a termékeket, mennyiségeket és a lejárati időket egy helyen.
        </p>

        <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn" href="inventory.php">Raktár megnyitása</a>
          <a class="btn btn-secondary" href="shopping_list.php">Bevásárlólista</a>
        </div>
      </div>
    </div>
  </div>

  <!-- KÖZÉP: DASHBOARD CARD -->
  <div class="dash-mid">
    <div class="main-wrapper">
      <div class="card">

        <h1>Helló, <?= htmlspecialchars($firstName) ?>! 👋</h1>
        <p class="mt-2">Válassz egy modult:</p>

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

          <a href="shopping_list.php" class="menu-tile menu-tile--wide">
            <div class="menu-icon">🛒</div>
            <div class="menu-title">Bevásárlólista</div>
            <div class="menu-desc">Háztartás közös listája. Pipálás után mehet a raktárba.</div>
            <div class="menu-go">Megnyitás →</div>
          </a>

        </div>

        <div class="dash-notify mt-4" aria-live="polite">
          <div class="dn-head">
            <div class="dn-left">
              <span class="dn-ico">🔔</span>
              <span class="dn-title">Friss értesítések</span>
            </div>
            <div class="dn-badge <?= $unreadCount > 0 ? 'is-on' : '' ?>">
              <?= $unreadCount > 0 ? (int)$unreadCount . ' új' : 'Nincs új' ?>
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
              <span class="dn-hint">Ha az Üzeneteknél lekezeled (olvasott/elfogad/elutasít), innen automatikusan eltűnik.</span>
            </div>
          <?php else: ?>
            <div class="dn-empty">Minden rendben — nincs új üzenet.</div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <!-- JOBB: TIPPEK BOX -->
  <div class="dash-side">
    <div class="card side-card">
      <div class="side-stack">

        <div class="note">
          <div style="font-weight:900; margin-bottom:8px;">✨ Napi tipp</div>
          <div id="dashTip">A dobozokra írj dátumot: 10 mp, napokkal kevesebb pazarlás.</div>
        </div>

        <div class="note">
          <div style="font-weight:900; margin-bottom:10px;">⚡ Gyors műveletek</div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <a class="btn btn-mini" href="messages.php">🔔 Üzenetek (<?= $unreadCount > 0 ? (int)$unreadCount . " új" : "nincs új" ?>)</a>
            <a class="btn btn-mini" href="inventory.php">🧊 Raktár</a>
            <a class="btn btn-mini" href="shopping_list.php">🛒 Bevásárlólista</a>
            <a class="btn btn-mini" href="recipes.php">🍳 Receptek</a>
          </div>
        </div>

        <div class="note">
          <div style="font-weight:900; margin-bottom:8px;">🎯 Mini küldetés</div>
          <div id="dashMission">Tegyél fel 1 dolgot a bevásárlólistára, amit mindig elfelejtesz.</div>
        </div>

      </div>
    </div>
  </div>

</div>

<script>
/* Bubik random indulás + parallax */
(() => {
  const bubbles = document.getElementById('bubbles');
  if (!bubbles) return;

  const items = Array.from(bubbles.querySelectorAll('span')).map((el, i) => {
    const dur = parseFloat(getComputedStyle(el).animationDuration) || 20;
    el.style.animationDelay = (Math.random() * dur * -1).toFixed(2) + 's';
    const speed = 0.6 + (i % 7) * 0.15;
    const depth = 8 + (i % 6) * 6;
    return { el, speed, depth };
  });

  let mx = 0, my = 0, tx = 0, ty = 0;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  window.addEventListener('mousemove', (e) => {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mx = clamp((e.clientX - cx) / cx, -1, 1);
    my = clamp((e.clientY - cy) / cy, -1, 1);
  }, { passive: true });

  function tick() {
    tx += (mx - tx) * 0.06;
    ty += (my - ty) * 0.06;

    const sy = window.scrollY || 0;
    for (const it of items) {
      const px = tx * it.depth * it.speed;
      const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
      it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>

</body>
</html>
