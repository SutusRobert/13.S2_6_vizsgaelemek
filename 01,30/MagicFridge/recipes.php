<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ================================
   HOUSEHOLDS + HID választás
   ================================ */
$stmt = $pdo->prepare("
    SELECT id AS household_id, name FROM households WHERE owner_id = ?
    UNION
    SELECT h.id AS household_id, h.name
    FROM household_members hm
    JOIN households h ON h.id = hm.household_id
    WHERE hm.member_id = ?
    ORDER BY household_id ASC
");
$stmt->execute([$userId, $userId]);
$households = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$households) {
    header("Location: households.php");
    exit;
}

$householdMap = [];
foreach ($households as $hh) $householdMap[(int)$hh['household_id']] = $hh['name'];

$householdId = isset($_GET['hid']) ? (int)$_GET['hid'] : (int)$households[0]['household_id'];
if (!isset($householdMap[$householdId])) $householdId = (int)$households[0]['household_id'];
$householdName = $householdMap[$householdId];

/* ================================
   Raktár (névlista) -> receptekhez összevetés
   ================================ */
$stmt = $pdo->prepare("
    SELECT LOWER(TRIM(name)) AS n
    FROM inventory_items
    WHERE household_id = ?
    GROUP BY LOWER(TRIM(name))
");
$stmt->execute([$householdId]);
$invNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

function invContains(array $invNames, string $needle): bool {
    $needle = mb_strtolower(trim($needle), 'UTF-8');
    if ($needle === '') return false;
    foreach ($invNames as $n) {
        if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}

/* ================================
   ✅ Magyar legördülő (HU címke, EN kulcsszó az API-nak)
   ================================ */
$presetFoods = [
    'chicken'    => 'Csirkés ételek',
    'beef'       => 'Marhás ételek',
    'pork'       => 'Sertés ételek',
    'fish'       => 'Halételek',
    'salmon'     => 'Lazacos',
    'tuna'       => 'Tonhalas',
    'shrimp'     => 'Rák / garnéla',

    'pasta'      => 'Tészták',
    'noodles'    => 'Noodle / ázsiai tészta',
    'rice'       => 'Rizses ételek',
    'potato'     => 'Krumplis ételek',
    'soup'       => 'Levesek',
    'stew'       => 'Raguk / pörköltek',
    'salad'      => 'Saláták',
    'sandwich'   => 'Szendvicsek',
    'burger'     => 'Burgerek',
    'pizza'      => 'Pizza',
    'curry'      => 'Curry',
    'vegetarian' => 'Vegetáriánus',
    'vegan'      => 'Vegán',

    'breakfast'  => 'Reggeli',
    'egg'        => 'Tojásos kaják',
    'omelette'   => 'Omlett',

    'cheese'     => 'Sajtos kaják',
    'mushroom'   => 'Gombás kaják',
    'tomato'     => 'Paradicsomos kaják',
    'beans'      => 'Babos / hüvelyes',
    'lentil'     => 'Lencsés',

    'dessert'    => 'Desszertek',
    'cake'       => 'Sütik / torták',
    'cookie'     => 'Kekszek',
    'pancake'    => 'Palacsinta / pancake',
    'chocolate'  => 'Csokis desszert',
    'ice cream'  => 'Fagyi',

    'healthy'    => 'Egészséges',
];

/* A kiválasztott szűrő */
$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery === '' || !isset($presetFoods[$searchQuery])) {
    $searchQuery = 'chicken';
}

/* API receptek lekérése */
$apiRecipes = fetchSpoonacularRecipes($searchQuery, 50);

/* Saját receptek */
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ?");
$stmt->execute([$userId]);
$ownRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Receptek – MagicFridge</title>
    <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">

    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .recipe-card {
            padding: 10px;
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform .2s;
            position: relative;
        }
        .recipe-card:hover { transform: scale(1.03); }
        .recipe-img { width: 100%; border-radius: 10px; }
        .title { font-weight: bold; margin-top: 6px; font-size: 15px; color: #fff; }
        .search-form { margin-top: 12px; display: flex; gap: 10px; align-items: center; }
        .search-form select { flex: 1; }
        .hhbar{ display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; }
    </style>

    <style>
      .bubbles{
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
      }
      .navbar, .main-wrapper { position: relative; z-index: 2; }
    </style>
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
        <a href="inventory_list.php?hid=<?= (int)$householdId ?>">Készlet</a>
        <a href="shopping_list.php?hid=<?= (int)$householdId ?>">Bevásárlólista</a>
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

        <div class="hhbar">
            <div>
                <h2 style="margin-bottom:6px;">Receptek</h2>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>

            <form method="get" style="margin:0; display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
                <label class="small" style="opacity:.8;">Háztartás váltás</label>
                <select name="hid" onchange="this.form.submit()">
                    <?php foreach ($households as $hh): $hidOpt = (int)$hh['household_id']; ?>
                        <option value="<?= $hidOpt ?>" <?= $hidOpt === (int)$householdId ? 'selected' : '' ?>>
                            <?= h($hh['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">OK</button></noscript>
            </form>
        </div>

        <div class="flex-between mt-3">
            <div></div>
            <a class="btn" href="create_recipe.php">+ Saját recept</a>
        </div>

        <!-- SAJÁT RECEPTEK -->
        <h3 class="mt-4">Saját receptek</h3>
        <?php if (empty($ownRecipes)): ?>
            <p class="mt-2">Még nincs saját recepted. Kattints a „+ Saját recept” gombra a létrehozáshoz.</p>
        <?php else: ?>
            <ul class="list mt-2">
                <?php foreach ($ownRecipes as $r): ?>
                    <?php
                        $stmt = $pdo->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
                        $stmt->execute([(int)$r['id']]);
                        $ings = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        $canCook = true;
                        foreach ($ings as $ingName) {
                            if (!invContains($invNames, (string)$ingName)) { $canCook = false; break; }
                        }
                    ?>
                    <li style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <span style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <a href="own_recipe_details.php?id=<?= (int)$r['id'] ?>&hid=<?= (int)$householdId ?>" style="color: #fff; text-decoration: underline;">
                                <?= h($r['title']) ?>
                            </a>
                            <?php if ($canCook): ?>
                                <span class="badge badge-ok">✔ Megvan</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✖ Hiányzik</span>
                            <?php endif; ?>
                        </span>

                        <form method="post" action="delete_recipe.php" style="margin:0;">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn btn-secondary" style="font-size:12px;">Törlés</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- ✅ API RECEPTEK + MAGYAR LEGDÖRDÜLŐ -->
        <h3 class="mt-4">Ajánlott receptek (API)</h3>

        <form method="get" class="search-form">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">

            <select name="q" class="input" onchange="this.form.submit()">
                <?php foreach ($presetFoods as $val => $label): ?>
                    <option value="<?= h($val) ?>" <?= ($searchQuery === $val ? 'selected' : '') ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <noscript><button type="submit">Szűrés</button></noscript>
        </form>

        <div class="small" style="opacity:.75; margin-top:8px;">
            Kiválasztva: <b><?= h($presetFoods[$searchQuery] ?? '—') ?></b>
        </div>

        <div class="grid mt-3">
            <?php if (is_array($apiRecipes) && isset($apiRecipes['_error'])): ?>
                <p style="color:red;"><?= h($apiRecipes['_error']) ?></p>

            <?php elseif (empty($apiRecipes) || !is_array($apiRecipes)): ?>
                <p style="color:red;">Nem található recept ezzel a szűrővel.</p>

            <?php else: ?>
                <?php foreach ($apiRecipes as $r): ?>
                    <?php
                        // ✅ kompatibilis: id/title/image VAGY idMeal/strMeal/strMealThumb
                        $id = 0;
                        if (is_array($r)) {
                            $id = (int)($r['idMeal'] ?? $r['id'] ?? 0);
                            $title = (string)($r['strMeal'] ?? $r['title'] ?? '');
                            $img = (string)($r['strMealThumb'] ?? $r['image'] ?? '');
                        } else {
                            $title = '';
                            $img = '';
                        }

                        if ($id <= 0 || $title === '') continue;
                    ?>
                    <div class="recipe-card"
                         onclick="window.location='recipe_details.php?id=<?= (int)$id ?>&hid=<?= (int)$householdId ?>'">
                        <?php if ($img !== ''): ?>
                            <img src="<?= h($img) ?>" class="recipe-img" alt="Recipe image">
                        <?php endif; ?>
                        <div class="title"><?= h($title) ?></div>
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
