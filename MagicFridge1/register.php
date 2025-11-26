<?php
require_once "config.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $error = "Minden mezőt ki kell tölteni.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Érvénytelen email cím.";
    } elseif (strlen($password) < 6) {
        $error = "A jelszónak legalább 6 karakteresnek kell lennie.";
    } else {
        // nézzük, foglalt-e az email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $error = "Ez az email cím már foglalt.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$full_name, $email, $hash]);

            $success = "Sikeres regisztráció! Jelentkezz be.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Regisztráció - MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="auth-container">
    <img src="assets/Logo.png" class="logo" alt="MagicFridge logo">
    <h2>Regisztráció</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Teljes név</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

        <label>Email cím</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label>Jelszó</label>
        <input type="password" name="password" required>

        <button type="submit">Regisztráció</button>

        <a href="login.php" class="link">Már van fiókod? Jelentkezz be</a>
    </form>
</div>

</body>
</html>
