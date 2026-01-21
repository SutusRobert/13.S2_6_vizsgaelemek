<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$fullName = $_SESSION['full_name'] ?? '';
$parts = explode(' ', trim($fullName));
$firstName = end($parts);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
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
    <div class="dash-shell">
        <div class="dash-grid">

            <div class="fridge-card">
                <div class="fridge-hero">
                    <img src="assets/fridge.png" alt="Hűtőszekrény" class="fridge-img">
                </div>
                <div class="fridge-body">
                    <div class="pill">🧊 Raktár</div>
                    <h2 class="mt-2">Készlet & lejáratok</h2>
                    <p class="mt-2">Termékek, mennyiségek, lejárat figyelés és gyors műveletek egy helyen.</p>
                    <a href="inventory.php" class="btn btn-secondary mt-3">Raktár megnyitása</a>
                </div>
            </div>

            <div class="right-stack">
                <div class="card">
                    <h1>Helló, <?= htmlspecialchars($firstName) ?>! 👋</h1>
                    <p class="mt-3">Válassz egy modult:</p>

                    <div class="menu-grid mt-4">
                        <a href="recipes.php" class="menu-tile">
                            <div class="menu-icon">🍳</div>
                            <div class="menu-title">Receptek</div>
                            <div class="menu-desc">Nézd meg, mire elég a készlet.</div>
                            <div class="menu-go">Megnyitás →</div>
                        </a>

                        <a href="messages.php" class="menu-tile">
                            <div class="menu-icon">🔔</div>
                            <div class="menu-title">Üzenetek</div>
                            <div class="menu-desc">Lejáratok, figyelmeztetések, értesítések.</div>
                            <div class="menu-go">Megnyitás →</div>
                        </a>

                        <a href="households.php" class="menu-tile">
                            <div class="menu-icon">🧺</div>
                            <div class="menu-title">Háztartás</div>
                            <div class="menu-desc">Tagok kezelése, rangok, hozzáférés.</div>
                            <div class="menu-go">Megnyitás →</div>
                        </a>

                        <a href="inventory.php" class="menu-tile">
                            <div class="menu-icon">🧊</div>
                            <div class="menu-title">Raktár</div>
                            <div class="menu-desc">Készlet, mennyiség, lejárati dátumok.</div>
                            <div class="menu-go">Megnyitás →</div>
                        </a>
                        <a href="shopping_list.php" class="dashboard-card">
                            <div class="dashboard-icon">🛒</div>
                            <div>
                                <h3>Bevásárlólista</h3>
                                <p>Háztartás közös listája</p>
                            </div>
                         </a>
                    </div>
                </div>

                <div class="note">
                    Tipp: bal felül a <strong>MagicFridge</strong> mindig visszahoz ide.
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>