<?php
session_start();
require 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Helytelen email vagy jelszó.";
    } else {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Helytelen email vagy jelszó.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Bejelentkezés – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Buborék háttér -->
<div class="bubbles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
</div>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>

    <div class="nav-right">
        <div class="about-nav">
            <span class="about-trigger">Rólunk</span>

            <div class="about-dropdown">
                <p><strong>MagicFridge</strong> – közös háztartás, közös készlet, kevesebb pazarlás.</p>
                <p>Segít nyomon követni, mi van otthon, mikor jár le valami, és mit érdemes főzni.</p>
                <ul>
                    <li>Lejáratfigyelés és értesítések</li>
                    <li>Háztartás és jogosultságok</li>
                    <li>Receptek a készlet alapján</li>
                    <li>Bevásárlólista</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="main-wrapper">
    <div class="card card-narrow">
        <h2>Bejelentkezés</h2>
        <p>Lépj be a fiókodba a receptek és háztartás kezeléséhez.</p>

        <?php if ($error): ?>
            <div class="error mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Email cím</label>
                <input type="email" name="email" maxlength="40" required>
            </div>

            <div class="form-group">
                <label>Jelszó</label>
                <input type="password" name="password" maxlength="40" required>
            </div>

            <button type="submit">Belépés</button>

            <p class="small mt-3">Még nincs fiókod? <a href="register.php">Regisztrálj itt.</a></p>
        </form>
    </div>
</div>

</body>
</html>
