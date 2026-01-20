<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<title>Raktár – MagicFridge</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
  <div class="nav-left">
    <img src="assets/Logo.png" class="nav-logo">
    <span class="nav-title">
      <a href="dashboard.php">MagicFridge</a>
    </span>
  </div>
  <div class="nav-links">
    <a href="logout.php">Kijelentkezés</a>
  </div>
</div>

<div class="main-wrapper">
  <div class="card">
    <h1>Raktár</h1>
    <p class="mt-3">Itt lesz a raktár modul (termékek, mennyiségek, lejáratok).</p>
  </div>
</div>

</body>
</html>
