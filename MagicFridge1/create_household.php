<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Új háztartás - MagicFridge</title>
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

<div class="auth-container">
    <h2>Új háztartás létrehozása</h2>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" action="save_household.php">
        <label>Háztartás neve</label>
        <input type="text" name="name" required>
        <button type="submit">Létrehozás</button>
    </form>
</div>

</body>
</html>
