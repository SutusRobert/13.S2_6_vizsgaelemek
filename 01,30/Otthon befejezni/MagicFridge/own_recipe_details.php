<?php
session_start();
require 'config.php';

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

$householdMap = [];
foreach ($households as $hh) $householdMap[(int)$hh['household_id']] = $hh['name'];

$householdId = isset($_GET['hid']) ? (int)$_GET['hid'] : (int)($households[0]['household_id'] ?? 0);
if ($householdId && !isset($householdMap[$householdId])) {
    $householdId = (int)($households[0]['household_id'] ?? 0);
}
$householdName = $householdMap[$householdId] ?? '';

/* ================================
   Raktár nevek
   ================================ */
$invNames = [];
if ($householdId) {
    $stmt = $pdo->prepare("
        SELECT LOWER(TRIM(name)) AS n
        FROM inventory_items
        WHERE household_id = ?
        GROUP BY LOWER(TRIM(name))
    ");
    $stmt->execute([$householdId]);
    $invNames = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

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
   Recipe betöltése
   ================================ */
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$id, $userId]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: recipes.php");
    exit;
}

/* ================================
   Hozzávaló hozzáadása (saját receptnél)
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_ingredient') {
    $newIng = trim((string)($_POST['new_ingredient'] ?? ''));
    $newQty = trim((string)($_POST['new_quantity'] ?? ''));

    if ($newIng !== '') {
        $stAdd = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient, quantity) VALUES (?, ?, ?)");
        $stAdd->execute([$id, $newIng, ($newQty !== '' ? $newQty : null)]);
    }

    header("Location: own_recipe_details.php?id=" . $id);
    exit;
}


/* hozzávalók */
$stmt = $pdo->prepare("SELECT ingredient, quantity FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
$stmt->execute([$id]);
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$servings = (int)($recipe['servings'] ?? 5);
if ($servings < 1) $servings = 5;

/* 1 főre kb. 250 g – elosztva a hozzávalók között */
function suggestQuantityForIngredientList(array $ingredients, int $servings): array {
    $result = [];
    $count = count($ingredients) ?: 1;

    // ha nincs megadott mennyiség, 1 főre kb. 250 g összesen (elosztva a hozzávalók között)
    $gramsPerPersonTotal = 250;
    $gramsPerPersonPerIngredient = $gramsPerPersonTotal / $count;

    foreach ($ingredients as $row) {
        $name = (string)($row['ingredient'] ?? '');
        $qty  = trim((string)($row['quantity'] ?? ''));

        // ha a user megadta, azt használjuk
        if ($qty !== '') {
            $result[] = ['name' => $name, 'amount' => $qty];
            continue;
        }

        $lower = mb_strtolower($name, 'UTF-8');
        if (mb_stripos($lower, 'tojás') !== false || mb_stripos($lower, 'tojas') !== false) {
            $totalPieces = $servings * 1;
            $result[] = ['name' => $name, 'amount' => $totalPieces . ' db'];
            continue;
        }

        $total = (int)round($gramsPerPersonPerIngredient * $servings);
        $result[] = ['name' => $name, 'amount' => $total . ' g'];
    }
    return $result;
}

$ingredientsSuggested = suggestQuantityForIngredientList($ingredients, $servings);

/* ================================
   AUTO steps fallback (ha nincs instructions)
   ================================ */
function detectRecipeType(string $title, string $ingredientsText): string {
    $text = mb_strtolower($title . ' ' . $ingredientsText, 'UTF-8');

    $soupKeywords = ['leves', 'soup'];
    foreach ($soupKeywords as $k) if (mb_strpos($text, $k) !== false) return 'soup';

    $pastaKeywords = ['tészta', 'teszta', 'pasta', 'spaghetti', 'penne'];
    foreach ($pastaKeywords as $k) if (mb_strpos($text, $k) !== false) return 'pasta';

    $stewKeywords = ['pörkölt', 'porkolt', 'ragu', 'stew'];
    foreach ($stewKeywords as $k) if (mb_strpos($text, $k) !== false) return 'stew';

    return 'generic';
}

function renderProcessSteps(string $type, string $ingredientsTextEscaped, int $servings): string {
    $html = '<ol class="process-steps">';
    switch ($type) {
        case 'pasta':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Főzés:</strong> Forralj sós vizet, főzd ki a tésztát.</li>';
            $html .= '<li><strong>Alap:</strong> Készítsd el a feltétet/szószt serpenyőben.</li>';
            $html .= '<li><strong>Összekeverés:</strong> Keverd össze a tésztát a szósszal.</li>';
            $html .= '<li><strong>Tálalás:</strong> Oszd el kb. ' . (int)$servings . ' adagba és tálald.</li>';
            break;

        case 'soup':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Alap:</strong> Pirítsd a zöldségeket/húst kevés zsiradékon.</li>';
            $html .= '<li><strong>Felöntés:</strong> Öntsd fel vízzel/alaplével, fűszerezd.</li>';
            $html .= '<li><strong>Főzés:</strong> Főzd puhára, ízesítsd utána.</li>';
            $html .= '<li><strong>Tálalás:</strong> ' . (int)$servings . ' adagban tálald.</li>';
            break;

        case 'stew':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Pirítás:</strong> Piríts hagymát, majd húst/alapot.</li>';
            $html .= '<li><strong>Párolás:</strong> Fűszerezd, öntsd fel kevés folyadékkal és párold.</li>';
            $html .= '<li><strong>Sűrítés:</strong> Állítsd be az állagot, kóstolj rá.</li>';
            $html .= '<li><strong>Tálalás:</strong> ' . (int)$servings . ' adagban tálald.</li>';
            break;

        default:
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Fő lépés:</strong> Készítsd el az alapot (pirítás/főzés/sütés).</li>';
            $html .= '<li><strong>Ízesítés:</strong> Fűszerezd, állítsd be az ízeket.</li>';
            $html .= '<li><strong>Tálalás:</strong> Oszd el ' . (int)$servings . ' adagba.</li>';
            break;
    }
    $html .= '</ol>';
    return $html;
}

$ingText = implode(', ', array_map(fn($r) => $r['ingredient'], $ingredients));
$type = detectRecipeType((string)$recipe['title'], $ingText);
$autoSteps = renderProcessSteps($type, h($ingText), $servings);

$storedInstructions = trim((string)($recipe['instructions'] ?? ''));
$imagePath = trim((string)($recipe['image_path'] ?? ''));

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= h($recipe['title']) ?> – Saját recept</title>
    <link rel="stylesheet" href="assets/style.css?v=1">
    <style>
        .hero-img{
            width:100%;
            height: 220px;
            object-fit: cover;
            border-radius: 16px;
            border:1px solid rgba(255,255,255,.12);
        }
        .hero-placeholder{
            height: 220px;
            border-radius: 16px;
            border:1px solid rgba(255,255,255,.12);
            background: linear-gradient(135deg, rgba(255,255,255,.08), rgba(0,0,0,.10));
            display:flex; align-items:center; justify-content:center;
            font-weight:900; opacity:.85;
        }
        .pill{
            display:inline-flex; align-items:center; gap:8px;
            padding:6px 10px; border-radius:999px;
            border:1px solid rgba(255,255,255,.14);
            background: rgba(0,0,0,.10);
            font-size: 12px; font-weight: 800;
        }
        .process-steps{ margin-top:10px; }
        .process-steps li{ margin-bottom: 8px; }
        .instructions-box{
            border:1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.08);
            border-radius: 16px;
            padding: 14px;
            white-space: pre-wrap;
        }
    </style>
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

<div class="nav-left">
  <a href="dashboard.php" class="nav-brand-link" aria-label="Vissza a főoldalra">
    <img src="assets/Logo.png" class="nav-logo" alt="Logo">
    <span class="nav-title nav-title--static">MagicFridge</span>
  </a>
</div>

    <div class="nav-links">
        <a href="recipes.php?hid=<?= (int)$householdId ?>">Receptek</a>
        <a href="dashboard.php">Dashboard</a>
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

        <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:6px;"><?= h($recipe['title']) ?></h2>
                <div class="small" style="opacity:.8;">Háztartás: <b><?= h($householdName) ?></b></div>
                <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                    <span class="pill">👥 <?= (int)$servings ?> fő</span>
                    <span class="pill">🧺 <?= count($ingredients) ?> hozzávaló</span>
                </div>
            </div>

            <form method="get" style="margin:0;">
                <input type="hidden" name="id" value="<?= (int)$id ?>">
                <label class="small" style="opacity:.8;">Háztartás</label><br>
                <select name="hid" onchange="this.form.submit()">
                    <?php foreach ($households as $hh): $hidOpt=(int)$hh['household_id']; ?>
                        <option value="<?= $hidOpt ?>" <?= $hidOpt===(int)$householdId?'selected':'' ?>>
                            <?= h($hh['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">OK</button></noscript>
            </form>
        </div>

        <div style="margin-top:14px;">
            <?php if ($imagePath !== '' && file_exists(__DIR__ . '/' . $imagePath)): ?>
                <img class="hero-img" src="<?= h($imagePath) ?>" alt="Recept kép">
            <?php else: ?>
                <div class="hero-placeholder">Saját recept</div>
            <?php endif; ?>
        </div>

        <h3 style="margin-top:18px;">Hozzávalók (<?= (int)$servings ?> főre)</h3>

        <form method="post" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <input type="hidden" name="action" value="add_ingredient">
            <input type="text" name="new_ingredient" placeholder="Új hozzávaló (pl. Csirkemell)" required
                   style="flex:1; min-width:240px;">
            <input type="text" name="new_quantity" placeholder="Mennyiség (pl. 250 g)"
                   style="width:200px; min-width:160px;">
            <button class="btn btn-primary" type="submit">+ Hozzáadás</button>
        </form>


        <form method="post" action="shopping_list.php" style="margin-top:10px;">
            <input type="hidden" name="action" value="add_missing_api">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
            <input type="hidden" name="recipe_title" value="<?= h($recipe['title']) ?>">

            <?php $missingCount = 0; ?>

            <div class="mt-2">
                <?php foreach ($ingredientsSuggested as $idx => $row): ?>
                    <?php $has = invContains($invNames, (string)$row['name']); ?>
                    <div class="note" style="padding:10px 12px; margin-bottom:10px; border-left: 4px solid <?= $has ? 'rgba(34,197,94,.7)' : 'rgba(239,68,68,.7)' ?>;">
                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
                            <div>
                                <div style="font-weight:900;"><?= h($row['name']) ?></div>
                                <div class="muted"><?= h($row['amount']) ?></div>
                            </div>

                            <div style="display:flex; gap:10px; align-items:center;">
                                <div class="pill"><?= $has ? '✔ Megvan' : '✖ Hiányzik' ?></div>

                                <?php if (!$has): ?>
                                    <input type="hidden" name="missing_item[<?= (int)$idx ?>][name]" value="<?= h($row['name']) ?>">
                                    <input type="hidden" name="missing_item[<?= (int)$idx ?>][measure]" value="<?= h($row['amount']) ?>">
                                    <input type="checkbox" name="missing_item[<?= (int)$idx ?>][add]" checked style="transform:scale(1.1);">
                                    <?php $missingCount++; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:12px;">
                <button type="submit" class="btn" <?= $missingCount===0 ? 'disabled' : '' ?>>
                    Hiányzók bevásárlólistára (<?= (int)$missingCount ?>)
                </button>
                <span class="small" style="opacity:.75;">
                    A bevásárlólistára csak a “Hiányzik” tételek kerülnek.
                </span>
            </div>
        </form>

        <h3 style="margin-top:18px;">Elkészítés (<?= (int)$servings ?> főre)</h3>

        <?php if ($storedInstructions !== ''): ?>
            <div class="instructions-box"><?= h($storedInstructions) ?></div>
        <?php else: ?>
            <div class="muted" style="margin-top:6px;">Nincs külön megadott leírás — automatikus leírás:</div>
            <?= $autoSteps ?>
        <?php endif; ?>

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
