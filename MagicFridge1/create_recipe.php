<?php
require_once "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Új recept - MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">

    <script>
        // Placeholder kezelés automatikus eltűnés + visszatöltés
        function handlePlaceholder(input, text) {
            input.addEventListener("focus", () => {
                if (input.value === text) input.value = "";
            });

            input.addEventListener("blur", () => {
                if (input.value.trim() === "") input.value = text;
            });
        }

        function addIngredient() {
            const container = document.getElementById("ingredients");
            const input = document.createElement("input");

            input.type = "text";
            input.name = "ingredients[]";
            input.value = "pl. Csirkemell";
            input.required = true;

            // alkalmazzuk a placeholder scriptet
            handlePlaceholder(input, "pl. Csirkemell");

            container.appendChild(input);
        }

        window.onload = () => {
            // recept neve placeholder
            const recipeInput = document.getElementById("recipeName");
            handlePlaceholder(recipeInput, "pl. Csirkemell tésztával");

            // első alapanyagplaceholder
            const firstIng = document.getElementById("firstIng");
            handlePlaceholder(firstIng, "pl. Csirkemell");
        };
    </script>

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

<div class="auth-container" style="width:480px;">
    <h2>Új recept hozzáadása</h2>

    <form action="save_recipe.php" method="POST">

        <label>Recept neve</label>
        <input type="text" name="name" id="recipeName" value="pl. Csirkemell tésztával" required>

        <label>Alapanyagok</label>
        <div id="ingredients">
            <input type="text" id="firstIng" name="ingredients[]" value="pl. Csirkemell" required>
        </div>

        <button type="button" onclick="addIngredient()">+ Új alapanyag</button>

        <button type="submit" style="margin-top:14px;">Recept mentése</button>

        <a href="recipes.php" class="link">Vissza a receptekhez</a>
    </form>
</div>

</body>
</html>
