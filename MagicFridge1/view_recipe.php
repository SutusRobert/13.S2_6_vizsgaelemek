<?php
require 'config.php';

if (!isset($_GET['id'])) {
    die("Nincs recept ID megadva!");
}

$recipe_id = intval($_GET['id']);

// Recept lekérése
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$recipe_id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    die("Nincs ilyen recept!");
}

// Alapanyagok lekérése
$stmtIng = $pdo->prepare("SELECT name FROM ingredients WHERE recipe_id = ?");
$stmtIng->execute([$recipe_id]);
$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($recipe['name']) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h1><?= htmlspecialchars($recipe['name']) ?></h1>

    <h3>Alapanyagok:</h3>
    <ul>
        <?php foreach ($ingredients as $i): ?>
            <li><?= htmlspecialchars($i['name']) ?></li>
        <?php endforeach; ?>
    </ul>

    <a href="recipes.php" class="btn">Vissza</a>
</div>

</body>
</html>
