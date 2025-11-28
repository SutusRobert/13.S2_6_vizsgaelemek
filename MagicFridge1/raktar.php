<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$success = "";

// Új tétel mentése
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $item_name = trim($_POST['item_name'] ?? '');
    $quantity  = (int)($_POST['quantity'] ?? 1);

    if ($item_name === '') {
        $error = "Add meg a termék nevét.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO warehouse (user_id, item_name, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $item_name, $quantity]);
        $success = "Tétel hozzáadva a raktárhoz.";
    }
}

// Lista lekérése
$list = $pdo->prepare("SELECT * FROM warehouse WHERE user_id = ? ORDER BY id DESC");
$list->execute([$_SESSION['user_id']]);
$items = $list->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Raktár - MagicFridge</title>
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
    <h2>Raktár</h2>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="item_name" placeholder="Termék neve" required>
        <input type="number" name="quantity" placeholder="Mennyiség" value="1" min="1" required>
        <button type="submit">Hozzáadás</button>
    </form>

    <ul class="list">
        <?php if (empty($items)): ?>
            <li>Még nincs termék a raktárban.</li>
        <?php else: ?>
            <?php foreach ($items as $i): ?>
                <li><?= htmlspecialchars($i['item_name']) ?> (<?= (int)$i['quantity'] ?> db)</li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

</body>
</html>
