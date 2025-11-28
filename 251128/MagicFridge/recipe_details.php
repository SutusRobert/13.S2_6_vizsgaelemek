<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';
require 'api/translate.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Érvénytelen recept ID.");
}

$details = fetchRecipeDetails($id);

if (!$details) {
    die("Nem sikerült lekérni a recept részleteit.");
}

// Magyar fordítás
$hungarianTitle = translateToHungarian($details['title']);
$hungarianSummary = translateToHungarian(strip_tags($details['summary'] ?? ''));
$hungarianInstructions = translateToHungarian(strip_tags($details['instructions'] ?? ''));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($hungarianTitle) ?> – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .recipe-img-big {
            width: 100%;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .ingredient {
            background: #eef2ff;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="recipes.php">Vissza a receptekhez</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">

        <h1><?= htmlspecialchars($hungarianTitle) ?></h1>

        <img src="<?= htmlspecialchars($details['image']) ?>" class="recipe-img-big">

        <h2>Leírás</h2>
        <p><?= nl2br(htmlspecialchars($hungarianSummary)) ?></p>

        <h2 class="mt-4">Hozzávalók</h2>
        <?php foreach ($details['extendedIngredients'] as $ing): ?>
            <div class="ingredient">
                <?= htmlspecialchars(translateToHungarian($ing['original'])) ?>
            </div>
        <?php endforeach; ?>

        <h2 class="mt-4">Elkészítés</h2>
        <p><?= nl2br(htmlspecialchars($hungarianInstructions)) ?></p>

    </div>
</div>

</body>
</html>
