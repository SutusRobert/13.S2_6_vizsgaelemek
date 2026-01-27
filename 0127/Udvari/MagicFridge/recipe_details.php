<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';   // TheMealDB wrapper
require 'api/translate.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- household kiválasztás (ugyanaz a logika mint máshol) ---------- */
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

$map = [];
foreach ($households as $hh) $map[(int)$hh['household_id']] = $hh['name'];

$householdId = isset($_GET['hid']) ? (int)$_GET['hid'] : (int)$households[0]['household_id'];
if (!isset($map[$householdId])) $householdId = (int)$households[0]['household_id'];
$householdName = $map[$householdId];

/* ---------- inventory névlista (egyszerű “van/nincs” checkhez) ---------- */
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
function guessLocationForItem(string $name): string {
    $n = mb_strtolower(trim($name), 'UTF-8');

    $freezer = ['fagyaszt', 'mirelit', 'jég', 'jeg', 'fagyasztott', 'pizza', 'nugget', 'spenót', 'spenot', 'borsó', 'borso'];
    $fridge  = ['tej', 'joghurt', 'sajt', 'tejszín', 'tejszin', 'vaj', 'tojás', 'tojas', 'csirke', 'pulyka', 'marha', 'sertés', 'sertes', 'hal', 'sonka', 'kolbász', 'kolbasz'];

    foreach ($freezer as $k) if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'freezer';
    foreach ($fridge as $k)  if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'fridge';

    return 'pantry';
}


/* ---------- recept id ---------- */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Érvénytelen recept ID.");

/* ---------- API recept ---------- */
$meal = fetchRecipeDetails($id);
if (!$meal) die("Nem sikerült lekérni a recept részleteit.");

$titleEn        = $meal['strMeal'] ?? '';
$image          = $meal['strMealThumb'] ?? '';
$instructionsEn = $meal['strInstructions'] ?? '';

/* ---------- fordítás cache ---------- */
$stmt = $pdo->prepare("SELECT hu_title, hu_instructions FROM api_recipe_translations WHERE meal_id = ? LIMIT 1");
$stmt->execute([$id]);
$translation = $stmt->fetch();

if ($translation) {
    $huTitle = $translation['hu_title'];
    $huInstructions = $translation['hu_instructions'];
} else {
    $huTitle = translateToHungarian($titleEn);
    $huInstructions = translateLongTextToHungarian($instructionsEn);

    $stmt = $pdo->prepare("INSERT INTO api_recipe_translations (meal_id, hu_title, hu_instructions) VALUES (?, ?, ?)");
    $stmt->execute([$id, $huTitle, $huInstructions]);
}

/* ---------- hozzávalók összeszedése + fordítás ---------- */
$ingredientsHu = [];
for ($i = 1; $i <= 20; $i++) {
    $ingName = trim($meal["strIngredient{$i}"] ?? '');
    $measure = trim($meal["strMeasure{$i}"] ?? '');
    if ($ingName === '') continue;

    $huName = translateToHungarian($ingName);

    $hasIt = invContains($invNames, $huName) || invContains($invNames, $ingName);

    $ingredientsHu[] = [
        'name_hu' => $huName,
        'name_en' => $ingName,
        'measure' => $measure,
        'has' => $hasIt
    ];
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= h($huTitle) ?> – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .recipe-img-big{ width:100%; border-radius:14px; margin-bottom:16px; box-shadow:0 6px 20px rgba(0,0,0,.25); }
        .ing-row{
            display:flex; justify-content:space-between; gap:12px; align-items:center;
            padding:10px 12px; border-radius:12px; margin-bottom:8px;
            border:1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.06);
        }
        .ing-ok{ border-color: rgba(34,197,94,.45); background: rgba(34,197,94,.12); }
        .ing-miss{ border-color: rgba(239,68,68,.45); background: rgba(239,68,68,.12); }
        .ing-left{ display:flex; flex-direction:column; gap:4px; min-width:0; }
        .ing-left small{ opacity:.75; }
        .badge{
            padding:4px 10px; border-radius:999px; font-size:12px; font-weight:800;
            border:1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.10);
        }
        .badge-ok{ border-color: rgba(34,197,94,.55); background: rgba(34,197,94,.18); }
        .badge-bad{ border-color: rgba(239,68,68,.55); background: rgba(239,68,68,.18); }
        .topbar{ display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center; }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title"><a href="dashboard.php" class="brand-back">MagicFridge</a></span>
    </div>
    <div class="nav-links">
        <a href="recipes.php?hid=<?= (int)$householdId ?>">Vissza a receptekhez</a>
        <a href="shopping_list.php?hid=<?= (int)$householdId ?>">Bevásárlólista</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card" style="max-width: 900px; width:100%;">

        <div class="topbar">
            <div>
                <h1 style="margin-bottom:6px;"><?= h($huTitle) ?></h1>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>

            <form method="get" style="margin:0; display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="id" value="<?= (int)$id ?>">
                <label class="small" style="opacity:.8;">Háztartás</label>
                <select name="hid" onchange="this.form.submit()">
                    <?php foreach ($households as $hh): $hidOpt = (int)$hh['household_id']; ?>
                        <option value="<?= $hidOpt ?>" <?= $hidOpt === (int)$householdId ? 'selected' : '' ?>>
                            <?= h($hh['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (!empty($image)): ?>
            <img src="<?= h($image) ?>" class="recipe-img-big" alt="Recipe image">
        <?php endif; ?>

        <h2>Hozzávalók (raktár ellenőrzéssel)</h2>

        <!-- HIÁNYZÓK BEVÁSÁRLÓLISTÁRA – 1 FORM, ezért az ÖSSZES bekerül -->
        <form method="post" action="shopping_list.php" style="margin-top:10px;">
            <input type="hidden" name="action" value="add_missing_api">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
            <input type="hidden" name="recipe_title" value="<?= h($huTitle) ?>">

            <?php
            $missingCount = 0;
            foreach ($ingredientsHu as $ing):
                $rowClass = $ing['has'] ? 'ing-row ing-ok' : 'ing-row ing-miss';
            ?>
                <div class="<?= $rowClass ?>">
                    <div class="ing-left">
                        <div style="font-weight:900;"><?= h($ing['name_hu']) ?></div>
                        <small>(<?= h($ing['name_en']) ?>)</small>
                    </div>

                    <div style="display:flex; gap:10px; align-items:center;">
                        <div class="small" style="white-space:nowrap; opacity:.85;"><?= h($ing['measure']) ?></div>

                        <?php if ($ing['has']): ?>
                            <span class="badge badge-ok">Van</span>
                        <?php else: ?>
                            <span class="badge badge-bad">Hiányzik</span>
                            <!-- EZ a lényeg: [] tömb, így mind bekerül -->
                            <input type="checkbox" name="missing_name[]" value="<?= h($ing['name_hu']) ?>" checked>
                            <?php $missingCount++; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="display:flex; gap:10px; align-items:center; margin-top:12px; flex-wrap:wrap;">
                <button type="submit" class="btn" <?= $missingCount===0 ? 'disabled' : '' ?>>
                    Hiányzók bevásárlólistára (<?= (int)$missingCount ?>)
                </button>
                <span class="small" style="opacity:.75;">
                    Tipp: a helyet (Kamra/Hűtő/Fagyasztó) a bevásárlólista automatikusan tippeli.
                </span>
            </div>
        </form>

        <h2 class="mt-4">Elkészítés (magyarul)</h2>
        <p><?= nl2br(h($huInstructions)) ?></p>

    </div>
</div>

</body>
</html>
