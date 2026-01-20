<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>MagicFridge – Kezdőlap</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="login.php">Bejelentkezés</a>
        <a href="register.php">Regisztráció</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card card-narrow">
        <h1>Üdv a MagicFridge-ben!</h1>
        <p>Intelligens receptek, háztartáskezelés és készletfigyelés egy helyen.</p>

        <div class="mt-3">
            <a href="login.php" class="btn">Bejelentkezés</a>
            <a href="register.php" class="btn btn-secondary" style="margin-left:8px;">Regisztráció</a>
        </div>
    </div>
</div>

</body>
</html>