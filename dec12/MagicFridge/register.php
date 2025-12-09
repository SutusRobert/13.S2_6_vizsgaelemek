<?php
session_start();
require 'config.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $error = "Minden mező kitöltése kötelező.";
    } else {
        // Ellenőrizzük, hogy foglalt-e az email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Ez az email cím már foglalt, kérlek válassz másikat.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$full_name, $email, $hash]);

            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Regisztráció – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
</div>

<div class="main-wrapper">
    <div class="card card-narrow">
        <h2>Regisztráció</h2>
        <p>Hozz létre egy fiókot, hogy elérd a MagicFridge funkcióit.</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Teljes név</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Email cím</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Jelszó</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Regisztráció</button>

            <p class="small mt-3">Már van fiókod? <a href="login.php">Jelentkezz be!</a></p>
        </form>
    </div>
</div>

</body>
</html>
