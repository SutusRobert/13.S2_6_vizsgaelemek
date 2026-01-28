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

// Flash üzenetek (accept/decline redirect után)
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

/**
 * Meghívások (ha van household_invitations tábla)
 * - ezt úgy csináljuk, hogy ha a tábla nem létezik, ne dobjon hibát a teljes oldal.
 */
$pendingInvites = [];
try {
    // MySQL-ben a tábla létezésének ellenőrzése
    $chk = $pdo->query("SHOW TABLES LIKE 'household_invitations'")->fetchColumn();
    if ($chk) {
        $stmt = $pdo->prepare(
            "SELECT
                hi.id,
                hi.household_id,
                hi.invited_by,
                hi.created_at,
                h.name AS household_name,
                u.email AS inviter_email
             FROM household_invitations hi
             JOIN households h ON h.id = hi.household_id
            LEFT JOIN users u ON u.id = hi.invited_by
             WHERE hi.invited_user_id = ?
               AND hi.status = 'pending'
             ORDER BY hi.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([$userId]);
        $pendingInvites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    // ha nincs jogosultság SHOW TABLES-hoz vagy más gond van,
    // ne álljon meg az oldal – egyszerűen nem jelenítünk meg meghívásokat.
    $pendingInvites = [];
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

// Meghívók kezelése a "messages" táblában lévő háztartás-meghívó üzenetekhez
$hasHouseholdInvitesTable = false;
try {
    $chk2 = $pdo->query("SHOW TABLES LIKE 'household_invites'")->fetchColumn();
    $hasHouseholdInvitesTable = (bool)$chk2;
} catch (Throwable $e) {
    $hasHouseholdInvitesTable = false;
}

$inviteLookupByHousehold = null;
$inviteLookupAny = null;
if ($hasHouseholdInvitesTable) {
    $inviteLookupByHousehold = $pdo->prepare("
        SELECT id
        FROM household_invites
        WHERE household_id = ?
          AND invited_user_id = ?
          AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $inviteLookupAny = $pdo->prepare("
        SELECT id
        FROM household_invites
        WHERE invited_user_id = ?
          AND status = 'pending'
        ORDER BY created_at DESC
        LIMIT 1
    ");
}

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Üzenetek – MagicFridge</title>
    <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">
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

        <?php if (!empty($flash_success)) : ?>
            <div class="alert success mt-3"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if (!empty($flash_error)) : ?>
            <div class="alert danger mt-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <?php if (!empty($pendingInvites)) : ?>
            <div class="mt-3">
                <h2 style="margin-bottom:10px;">Háztartás meghívások</h2>
                <div class="message-wall">
                    <?php foreach ($pendingInvites as $inv) : ?>
                        <?php
                            $invId = (int)$inv['id'];
                            $householdName = htmlspecialchars($inv['household_name'] ?? 'Háztartás');
                            $inviter = $inv['inviter_email'] ?? ('Felhasználó #' . (int)($inv['invited_by'] ?? 0));
                            $inviter = htmlspecialchars((string)$inviter);
                            $invTime = htmlspecialchars((string)($inv['created_at'] ?? ''));
                        ?>
                        <div class="message-item message-info message-unread">
                            <div class="message-top">
                                <div class="message-title">Meghívás: <?= $householdName ?></div>
                                <div class="message-time"><?= $invTime ?></div>
                            </div>
                            <div class="message-text">
                                <?= $inviter ?> meghívott ebbe a háztartásba: <b><?= $householdName ?></b>.
                            </div>
                            <div class="message-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                                <form method="post" action="invite_action.php" class="message-form">
                                    <input type="hidden" name="invite_id" value="<?= $invId ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="message-btn">Elfogadom</button>
                                </form>
                                <form method="post" action="invite_action.php" class="message-form">
                                    <input type="hidden" name="invite_id" value="<?= $invId ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="message-btn danger">Elutasítom</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

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
                            <?php
                                // Meghívó üzenet felismerés: type vagy title alapján
                                $isInviteMsg = false;
                                if (!empty($type) && in_array($type, ['household_invite','invitation','invite'], true)) {
                                    $isInviteMsg = true;
                                }
                                if (!$isInviteMsg && !empty($title)) {
                                    $t = mb_strtolower(strip_tags((string)$title));
                                    if (strpos($t, 'háztartás meghív') !== false || strpos($t, 'meghívó') !== false) {
                                        $isInviteMsg = true;
                                    }
                                }

                                $pendingInviteId = 0;
                                if ($isInviteMsg && !$isRead && $hasHouseholdInvitesTable) {
                                    if (!empty($householdId)) {
                                        $inviteLookupByHousehold->execute([(int)$householdId, $userId]);
                                        $pendingInviteId = (int)$inviteLookupByHousehold->fetchColumn();
                                    } else {
                                        $inviteLookupAny->execute([$userId]);
                                        $pendingInviteId = (int)$inviteLookupAny->fetchColumn();
                                    }
                                }
                            ?>

                            <?php if (!$isRead && $isInviteMsg && $pendingInviteId > 0) : ?>
                                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                    <form method="post" action="invite_respond.php" class="message-form">
                                        <input type="hidden" name="invite_id" value="<?= $pendingInviteId ?>">
                                        <input type="hidden" name="message_id" value="<?= $id ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="message-btn">Elfogadom</button>
                                    </form>
                                    <form method="post" action="invite_respond.php" class="message-form">
                                        <input type="hidden" name="invite_id" value="<?= $pendingInviteId ?>">
                                        <input type="hidden" name="message_id" value="<?= $id ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="message-btn danger">Elutasítom</button>
                                    </form>
                                </div>
                            <?php elseif (!$isRead) : ?>
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