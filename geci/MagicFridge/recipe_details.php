<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';   // TheMealDB wrapper, csak a név régi
require 'api/translate.php';     // Ebben van: translateToHungarian + translateLongTextToHungarian

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Érvénytelen recept ID.");
}

// 1) Recept lekérése az API-ból (TheMealDB)
$meal = fetchRecipeDetails($id);

if (!$meal) {
    die("Nem sikerült lekérni a recept részleteit.");
}

// Angol mezők TheMealDB-ből
$titleEn        = $meal['strMeal']         ?? '';
$image          = $meal['strMealThumb']    ?? '';
$instructionsEn = $meal['strInstructions'] ?? '';
$category       = $meal['strCategory']     ?? '';
$area           = $meal['strArea']         ?? '';
$tags           = $meal['strTags']         ?? '';

// 2) Magyar cím + magyar elkészítés CACHE-ELÉSE
$stmt = $pdo->prepare("
    SELECT hu_title, hu_instructions
    FROM api_recipe_translations
    WHERE meal_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$translation = $stmt->fetch();

if ($translation) {
    // Van már fordítás -> nem fordítunk újra
    $huTitle        = $translation['hu_title'];
    $huInstructions = $translation['hu_instructions'];
} else {
    // Nincs még fordítás -> most készítjük és elmentjük

    // Rövid szöveg: egyszerű fordítás
    $huTitle = translateToHungarian($titleEn);

    // Hosszú szöveg: darabolós fordító (500 karakternél hosszabb szövegre)
    $huInstructions = translateLongTextToHungarian($instructionsEn);

    // Mentés az adatbázisba, hogy legközelebb villámgyors legyen
    $stmt = $pdo->prepare("
        INSERT INTO api_recipe_translations (meal_id, hu_title, hu_instructions)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id, $huTitle, $huInstructions]);
}

// 3) Hozzávalók összerakása: strIngredient1..20 + strMeasure1..20
$ingredients = [];
for ($i = 1; $i <= 20; $i++) {
    $ingName = trim($meal["strIngredient{$i}"] ?? '');
    $measure = trim($meal["strMeasure{$i}"] ?? '');

    if ($ingName !== '') {
        $ingredients[] = [
            'name_en'  => $ingName,
            'measure'  => $measure,
        ];
    }
}

// 4) Hozzávalók magyar fordítása (rövid szöveg, maradhat direkt hívás)
$ingredientsHu = [];
foreach ($ingredients as $ing) {
    $huName = translateToHungarian($ing['name_en']);
    $ingredientsHu[] = [
        'name_hu' => $huName,
        'name_en' => $ing['name_en'],
        'measure' => $ing['measure'],
    ];
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($huTitle) ?> – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .recipe-img-big {
            width: 100%;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .ingredient {
            background: rgba(15, 23, 42, 0.9);
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            color: #e2e8f0;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
        }
        .ingredient-names {
            flex: 1;
        }
        .ingredient-names small {
            display: block;
            opacity: 0.7;
            font-size: 12px;
        }
        .ingredient-measure {
            white-space: nowrap;
            font-weight: 600;
        }
        .meta {
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="recipes.php">Vissza a receptekhez</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">

        <h1><?= htmlspecialchars($huTitle) ?></h1>

        <p class="meta">
            <?php if ($category): ?>
                Kategória: <strong><?= htmlspecialchars($category) ?></strong>
            <?php endif; ?>
            <?php if ($area): ?>
                &nbsp;•&nbsp; Eredet: <strong><?= htmlspecialchars($area) ?></strong>
            <?php endif; ?>
            <?php if ($tags): ?>
                &nbsp;•&nbsp; Címkék: <strong><?= htmlspecialchars($tags) ?></strong>
            <?php endif; ?>
        </p>

        <?php if (!empty($image)): ?>
            <img src="<?= htmlspecialchars($image) ?>" class="recipe-img-big" alt="Recipe image">
        <?php endif; ?>

        <h2>Hozzávalók</h2>
        <?php if (empty($ingredientsHu)): ?>
            <p>Nincsenek hozzávalók megadva.</p>
        <?php else: ?>
            <?php foreach ($ingredientsHu as $ing): ?>
                <div class="ingredient">
                    <div class="ingredient-names">
                        <span><?= htmlspecialchars($ing['name_hu']) ?></span>
                        <small>(<?= htmlspecialchars($ing['name_en']) ?>)</small>
                    </div>
                    <span class="ingredient-measure">
                        <?= htmlspecialchars($ing['measure']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="mt-4">Elkészítés (magyarul)</h2>
        <p><?= nl2br(htmlspecialchars($huInstructions)) ?></p>

        <h3 class="mt-4">Eredeti angol leírás</h3>
        <p><?= nl2br(htmlspecialchars($instructionsEn)) ?></p>

    </div>
</div>

</body>
</html>
