<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
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
        .search-form {
            margin-top: 16px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .search-form input[type="text"] {
            flex: 1;
        }
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
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">

        <div class="flex-between">
            <h2>Receptek</h2>
            <a class="btn" href="create_recipe.php">+ Saját recept</a>
        </div>

        <!-- SAJÁT RECEPTEK BLOKK -->
        <h3 class="mt-4">Saját receptek</h3>
        <?php if (empty($ownRecipes)): ?>
            <p class="mt-2">Még nincs saját recepted. Kattints a „+ Saját recept” gombra a létrehozáshoz.</p>
        <?php else: ?>
            <ul class="list mt-2">
                <?php foreach ($ownRecipes as $r): ?>
                    <li>
                        <span>
                            <a href="own_recipe_details.php?id=<?= $r['id'] ?>" style="color: #fff; text-decoration: underline;">
                                <?= htmlspecialchars($r['title']) ?>
                            </a>
                        </span>
                        <form method="post" action="delete_recipe.php" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-secondary" style="font-size:12px;">
                                Törlés
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- API RECEPTEK BLOKK + KERESŐ -->
        <h3 class="mt-4">Ajánlott receptek (API – TheMealDB)</h3>

        <form method="get" class="search-form">
            <input
                type="text"
                name="q"
                placeholder="Keresés az API receptjei között (pl. chicken, pasta, beef)..."
                value="<?= htmlspecialchars($searchQuery) ?>"
            >
            <button type="submit">Keresés</button>
        </form>

        <div class="grid mt-3">
            <?php if (isset($apiRecipes['_error'])): ?>
                <p style="color:red;"><?= htmlspecialchars($apiRecipes['_error']) ?></p>
            <?php elseif (empty($apiRecipes)): ?>
                <p style="color:red;">Nem található recept ezzel a kereséssel.</p>
            <?php else: ?>
                <?php foreach ($apiRecipes as $r): ?>
                    <div class="recipe-card"
                         onclick="window.location='recipe_details.php?id=<?= $r['id'] ?>'">
                        <img src="<?= htmlspecialchars($r['image']) ?>" class="recipe-img" alt="Recipe image">
                        <div class="title"><?= htmlspecialchars($r['title']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
