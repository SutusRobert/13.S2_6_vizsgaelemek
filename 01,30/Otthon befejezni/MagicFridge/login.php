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
    <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">
    <style>
      /* Bubik tényleg háttér */
      .bubbles{
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
      }
      .navbar, .dash-row { position: relative; z-index: 2; }

      /* ✅ EGYENLETES: középre igazított “sáv”, azonos bal/jobb padding,
         azonos gap a dobozok között */
      .dash-row{
        max-width: 1750px;
        margin: 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 28px;
        padding: 18px 28px 40px; /* bal/jobb ugyanannyi */
        box-sizing: border-box;
      }

      /* bal/jobb fix széles, közép rugalmas */
      .dash-left, .dash-side{
        width: 420px;
        flex: 0 0 420px;
        min-width: 0;
      }

      .dash-mid{
        flex: 1 1 auto;
        min-width: 560px;
        max-width: 980px;
      }

      /* a main-wrapper ne “középre húzza” külön a cardot */
      .main-wrapper{ margin: 0; width: 100%; }

      /* jobb oldali box belső spacing */
      .side-card{ padding: 18px; }
      .side-stack{ display: grid; gap: 14px; }

      /* mobilon egymás alá */
      @media (max-width: 1200px){
        .dash-row{
          flex-direction: column;
          align-items: center;
          justify-content: flex-start;
          max-width: 100%;
        }
        .dash-left, .dash-side{ width: min(520px, 100%); flex-basis: auto; }
        .dash-mid{ min-width: 0; max-width: 100%; }
      }
    </style>

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
<script>
/* Bubik random indulás + parallax */
(() => {
  const bubbles = document.getElementById('bubbles');
  if (!bubbles) return;

  const items = Array.from(bubbles.querySelectorAll('span')).map((el, i) => {
    const dur = parseFloat(getComputedStyle(el).animationDuration) || 20;
    el.style.animationDelay = (Math.random() * dur * -1).toFixed(2) + 's';
    const speed = 0.6 + (i % 7) * 0.15;
    const depth = 8 + (i % 6) * 6;
    return { el, speed, depth };
  });

  let mx = 0, my = 0, tx = 0, ty = 0;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  window.addEventListener('mousemove', (e) => {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mx = clamp((e.clientX - cx) / cx, -1, 1);
    my = clamp((e.clientY - cy) / cy, -1, 1);
  }, { passive: true });

  function tick() {
    tx += (mx - tx) * 0.06;
    ty += (my - ty) * 0.06;

    const sy = window.scrollY || 0;
    for (const it of items) {
      const px = tx * it.depth * it.speed;
      const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
      it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>


</body>
</html>
