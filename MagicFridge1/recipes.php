<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Receptlista lekérése
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Receptek - MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<nav class="navbar">
    <img src="assets/Logo.png" class="nav-logo" alt="MagicFridge logo">
    <div class="nav-menu">
        <a href="dashboard.php">Főoldal</a>
        <a href="haztartas.php">Háztartás</a>
        <a href="recipes.php">Receptek</a>
        <a href="raktar.php">Raktár</a>
        <a href="logout.php" class="logout">Kijelentkezés</a>
    </div>
</nav>

<div class="container">
    <div class="header-row">
        <h2>Receptjeid</h2>
        <a href="create_recipe.php" class="btn" style="width:auto;">+ Új recept</a>
    </div>

    <ul class="list">
        <?php if (empty($recipes)): ?>
            <li>Még nincs felvett recept.</li>
        <?php else: ?>
            <?php foreach ($recipes as $recipe): ?>
                <li>
                    <strong><?= htmlspecialchars($recipe['name']); ?></strong>
                    <br>
                    <a style="font-size:12px;color:#a5b4fc;" href="view_recipe.php?id=<?= $recipe['id'] ?>">Megtekintés</a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

</body>
</html>