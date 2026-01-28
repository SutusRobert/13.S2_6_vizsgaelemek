<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// háztartás létrehozás, ha még nincs
$userId = (int)$_SESSION['user_id'];

/* 1) Ha van saját (owner) háztartása, azt mutatjuk */
$stmt = $pdo->prepare("SELECT * FROM households WHERE owner_id = ? LIMIT 1");
$stmt->execute([$userId]);
$household = $stmt->fetch();

/* 2) Ha nincs saját, akkor tagként keressünk egyet */
if (!$household) {
    $stmt = $pdo->prepare("
        SELECT h.*
        FROM household_members hm
        JOIN households h ON h.id = hm.household_id
        WHERE hm.member_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $household = $stmt->fetch();
}

/* 3) Ha még így sincs, akkor nincs háztartása */
if (!$household) {
    // itt nálad valószínű van már egy UI, ami azt írja hogy nincs háztartás
    // hagyd meg a meglévő megjelenítést
}

$stmt->execute([$_SESSION['user_id']]);
$household = $stmt->fetch();

if (!$household) {
    $stmt = $pdo->prepare("INSERT INTO households (owner_id, name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['full_name'] . " háztartása"]);
    $householdId = $pdo->lastInsertId();

    // A tulajdonos automatikusan admin tag lesz ebben a háztartásban
    $stmt = $pdo->prepare("INSERT INTO household_members (household_id, member_id, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$householdId, $_SESSION['user_id']]);

    $stmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
    $stmt->execute([$householdId]);
    $household = $stmt->fetch();
}

// tagok lekérése (user_id-t is lehúzzuk, hogy tudjuk, ki kicsoda)
$stmt = $pdo->prepare("
    SELECT hm.id AS hm_id, u.id AS user_id, u.full_name, hm.role
    FROM household_members hm
    JOIN users u ON hm.member_id = u.id
    WHERE hm.household_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$household['id']]);
$members = $stmt->fetchAll();

$info = $_GET['info'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Háztartás – MagicFridge</title>
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

<div class="navbar">
    <div class="nav-left">
    <a href="dashboard.php" class="brand-back" style="display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </a>
    </div>

    <div class="nav-links">
        
        <a href="dashboard.php">Főoldal</a>
        <a href="recipes.php">Receptek</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>
<div class="bubbles" aria-hidden="true" id="bubbles">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>


<div class="main-wrapper">
    <div class="card">
        <h2>Háztartás</h2>
        <p class="small mt-2">Háztartás neve: <strong><?= htmlspecialchars($household['name']) ?></strong></p>

        <?php if ($info): ?>
            <div class="success mt-2"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error mt-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h3 class="mt-4">Tag meghívása (regisztrált felhasználók közül)</h3>
<form class="mt-2" method="post" action="add_household_member.php">
    <div class="form-group">
        <label>Email (pontosan úgy, ahogy regisztrálva van)</label>
        <input type="email" name="email" required>
    </div>
    <button type="submit">Meghívás küldése</button>
</form>


        <h3 class="mt-4">Tagok</h3>
        <ul class="list mt-2">
            <?php if (empty($members)): ?>
                <li>Még nincsenek tagok.</li>
            <?php else: ?>
                <?php foreach ($members as $m): ?>
                    <li>
                        <span>
                            <?= htmlspecialchars($m['full_name']) ?>
                            <?php if ($m['role'] === 'admin'): ?>
                                <span class="badge badge-primary">admin</span>
                            <?php elseif ($m['role'] === 'alap felhasználó'): ?>
                                <span class="badge badge-primary">alap felhasználó</span>
                            <?php else: ?>
                                <span class="badge">tag</span>
                            <?php endif; ?>
                        </span>

                        <?php if ($m['role'] !== 'admin'): ?>
                            <form method="post" action="toggle_role.php" style="margin:0;">
                                <input type="hidden" name="hm_id" value="<?= $m['hm_id'] ?>">
                                <button type="submit" class="btn btn-secondary" style="font-size:12px;">
                                    <?php if ($m['role'] === 'alap felhasználó'): ?>
                                        Rang eltávolítása
                                    <?php else: ?>
                                        Rang hozzáadása
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Admin rang nem módosítható, nincs gomb -->
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
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