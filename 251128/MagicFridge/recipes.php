<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// API receptek lekérése
$apiRecipes = fetchSpoonacularRecipes('chicken', 50);

// Saját receptek lekérése
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
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
        }
        .recipe-card:hover {
            transform: scale(1.05);
        }
        .recipe-img {
            width: 100%;
            border-radius: 10px;
        }
        .title {
            font-weight: bold;
            margin-top: 6px;
            font-size: 15px;
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
        <a href="dashboard.php">Főoldal</a>
        <a href="households.php">Háztartás</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">

        <div class="flex-between">
            <h2>Receptek</h2>
            <a class="btn" href="create_recipe.php">+ Saját recept</a>
        </div>

        <h3 class="mt-4">Ajánlott receptek (API – 50 db)</h3>

        <div class="grid mt-3">

            <?php if (empty($apiRecipes)): ?>
                <p style="color:red;">Nem sikerült az API lekérés.</p>
            <?php else: ?>

                <?php foreach ($apiRecipes as $r): ?>
                    <div class="recipe-card"
                         onclick="window.location='recipe_details.php?id=<?= $r['id'] ?>'">
                        <img src="<?= htmlspecialchars($r['image']) ?>" class="recipe-img">
                        <div class="title"><?= htmlspecialchars($r['title']) ?></div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>
</div>

</body>
</html>
