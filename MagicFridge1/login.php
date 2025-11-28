<?php
require_once "config.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['full_name']  = $user['full_name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Hibás email vagy jelszó.";
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Bejelentkezés - MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="auth-container">
    <img src="assets/Logo.png" class="logo" alt="MagicFridge logo">
    <h2>Bejelentkezés</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email cím</label>
        <input type="email" name="email" required>

        <label>Jelszó</label>
        <input type="password" name="password" required>

        <button type="submit">Belépés</button>

        <a href="register.php" class="link">Nincs még fiókod? Regisztrálj</a>
    </form>
</div>

</body>
</html>
