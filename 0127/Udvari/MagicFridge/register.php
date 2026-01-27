<?php
session_start();
require 'config.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $password2 === '') {
        $error = "Minden mező kitöltése kötelező.";
    } elseif ($password !== $password2) {
        $error = "A jelszavak nem egyeznek.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Ez az email cím már foglalt.";
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

<!-- Buborék háttér -->
<div class="bubbles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
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
    <div class="card card-narrow auth-card cut-bottom">

    
        

        <h2>Regisztráció</h2>
        <p>Hozz létre egy fiókot, hogy elérd a MagicFridge funkcióit.</p>

        <?php if ($error): ?>
            <div class="error mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" id="registerForm">
            <div class="form-group">
                <label>Teljes név</label>
                <input type="text" name="full_name" maxlength="40" required>
            </div>

            <div class="form-group">
                <label>Email cím</label>
                <input type="email" name="email" maxlength="40" required>
            </div>

            <div class="form-group password-wrap">
                <label>Jelszó</label>
                <div class="password-box">
                    <input type="password" id="password" name="password" maxlength="40" required>
                    <span class="toggle" onclick="togglePass('password')">👁</span>
                </div>
            </div>

            <div class="form-group password-wrap">
                <label>Jelszó ismétlése</label>
                <div class="password-box">
                    <input type="password" id="password2" name="password2" maxlength="40" required>
                    <span class="toggle" onclick="togglePass('password2')">👁</span>
                    <span id="matchIcon" class="match-icon"></span>
                </div>
            </div>

            <button type="submit">Regisztráció</button>
            <p class="small mt-3">Már van fiókod? <a href="login.php">Jelentkezz be!</a></p>
        </form>

    </div>
</div>

<script>
function togglePass(id) {
    const el = document.getElementById(id);
    el.type = el.type === "password" ? "text" : "password";
}

const p1 = document.getElementById("password");
const p2 = document.getElementById("password2");
const icon = document.getElementById("matchIcon");

function checkMatch() {
    if (!p2.value) { icon.textContent = ""; return; }
    icon.textContent = (p1.value === p2.value) ? "✅" : "❌";
}

p1.addEventListener("input", checkMatch);
p2.addEventListener("input", checkMatch);
</script>


</body>
</html>
