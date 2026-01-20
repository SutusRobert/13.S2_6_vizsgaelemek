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
    <title>Új recept – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <script>
        function addIngredient() {
            const cont = document.getElementById('ingredients');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'ingredients[]';
            input.placeholder = 'pl. Csirkemell';
            input.required = true;
            cont.appendChild(input);
        }
    </script>
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
        <h2>Új saját recept</h2>
        <form method="post" action="save_recipe.php">
            <div class="form-group">
                <label>Recept neve</label>
                <input type="text" name="title" placeholder="pl. Csirkemell tésztával" required>
            </div>

            <div class="form-group">
                <label>Alapanyagok</label>
                <div id="ingredients">
                    <input type="text" name="ingredients[]" placeholder="pl. Csirkemell" required>
                </div>
                <button type="button" class="btn btn-secondary mt-2" onclick="addIngredient()">+ Új alapanyag</button>
            </div>

            <button type="submit" class="mt-3">Mentés</button>
            <a href="recipes.php" class="btn btn-secondary mt-3">Mégse</a>
        </form>
    </div>
</div>

</body>
</html>
