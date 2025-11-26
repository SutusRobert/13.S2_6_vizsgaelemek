<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM households WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$households = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Háztartások - MagicFridge</title>
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
        <h2>Háztartásaid</h2>
        <a href="create_household.php" class="btn" style="width:auto;">+ Új háztartás</a>
    </div>

    <ul class="list">
        <?php if (empty($households)): ?>
            <li>Még nincs háztartásod.</li>
        <?php else: ?>
            <?php foreach ($households as $h): ?>
                <li><?= htmlspecialchars($h['name']); ?></li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

</body>
</html>
