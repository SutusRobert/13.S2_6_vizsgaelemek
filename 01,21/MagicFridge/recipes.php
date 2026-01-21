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
   (egyszerű: ha név benne van, akkor "van")
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

// Keresőmező: GET paraméterből jön a keresés
$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery === '') {
    $searchQuery = 'chicken'; // alapértelmezett
}

// API receptek lekérése (TheMealDB)
$apiRecipes = fetchSpoonacularRecipes($searchQuery, 50);

// Saját receptek lekérése
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ?");
$stmt->execute([$userId]);
$ownRecipes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Receptek – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .recipe-card {
            padding: 10px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform .2s;
            position: relative;
        }
        .recipe-card:hover { transform: scale(1.05); }
        .recipe-img { width: 100%; border-radius: 10px; }
        .title { font-weight: bold; margin-top: 6px; font-size: 15px; }
        .search-form { margin-top: 16px; display: flex; gap: 8px; align-items: center; }
        .search-form input[type="text"] { flex: 1; }
        .hhbar{ display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; }
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

<div class="main-wrapper">
    <div class="card">

        <div class="hhbar">
            <div>
                <h2 style="margin-bottom:6px;">Receptek</h2>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>

            <form method="get" style="margin:0; display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
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

        <div class="flex-between mt-3">
            <div></div>
            <a class="btn" href="create_recipe.php">+ Saját recept</a>
        </div>

        <!-- SAJÁT RECEPTEK BLOKK -->
        <h3 class="mt-4">Saját receptek</h3>
        <?php if (empty($ownRecipes)): ?>
            <p class="mt-2">Még nincs saját recepted. Kattints a „+ Saját recept” gombra a létrehozáshoz.</p>
        <?php else: ?>
            <ul class="list mt-2">
                <?php foreach ($ownRecipes as $r): ?>
                    <?php
                        // "Meg tudom csinálni?" -> egyszerű név-egyezés
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

        <!-- API RECEPTEK BLOKK + KERESŐ -->
        <h3 class="mt-4">Ajánlott receptek (API – TheMealDB)</h3>

        <form method="get" class="search-form">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
            <input
                type="text"
                name="q"
                placeholder="Keresés az API receptjei között (pl. chicken, pasta, beef)..."
                value="<?= h($searchQuery) ?>"
            >
            <button type="submit">Keresés</button>
        </form>

        <div class="grid mt-3">
            <?php if (isset($apiRecipes['_error'])): ?>
                <p style="color:red;"><?= h($apiRecipes['_error']) ?></p>
            <?php elseif (empty($apiRecipes)): ?>
                <p style="color:red;">Nem található recept ezzel a kereséssel.</p>
            <?php else: ?>
                <?php foreach ($apiRecipes as $r): ?>
                    <div class="recipe-card"
                         onclick="window.location='recipe_details.php?id=<?= (int)$r['id'] ?>&hid=<?= (int)$householdId ?>'">
                        <img src="<?= h($r['image']) ?>" class="recipe-img" alt="Recipe image">
                        <div class="title"><?= h($r['title']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
