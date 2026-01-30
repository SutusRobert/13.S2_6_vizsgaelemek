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

/* hozzávalók */
$stmt = $pdo->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
$stmt->execute([$id]);
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$servings = (int)($recipe['servings'] ?? 5);
if ($servings < 1) $servings = 5;

/* 1 főre kb. 250 g – elosztva a hozzávalók között */
function suggestQuantityForIngredientList(array $ingredients, int $servings): array {
    $result = [];
    $count = count($ingredients) ?: 1;

    $gramsPerPersonTotal = 250;
    $gramsPerPersonPerIngredient = $gramsPerPersonTotal / $count;

    foreach ($ingredients as $row) {
        $name = $row['ingredient'];

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

        <div class="mt-2">
            <?php foreach ($ingredientsSuggested as $row): ?>
                <?php
                    $has = invContains($invNames, (string)$row['name']);
                ?>
                <div class="note" style="padding:10px 12px; margin-bottom:10px; border-left: 4px solid <?= $has ? 'rgba(34,197,94,.7)' : 'rgba(239,68,68,.7)' ?>;">
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
                        <div>
                            <div style="font-weight:900;"><?= h($row['name']) ?></div>
                            <div class="muted"><?= h($row['amount']) ?></div>
                        </div>
                        <div class="pill <?= $has ? '' : '' ?>"><?= $has ? '✔ Megvan' : '✖ Hiányzik' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top:18px;">Elkészítés (<?= (int)$servings ?> főre)</h3>

        <?php if ($storedInstructions !== ''): ?>
            <div class="instructions-box"><?= h($storedInstructions) ?></div>
        <?php else: ?>
            <div class="muted" style="margin-top:6px;">Nincs külön megadott leírás — automatikus leírás:</div>
            <?= $autoSteps ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
