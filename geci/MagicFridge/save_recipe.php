<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $ingredients = $_POST['ingredients'] ?? [];

    if ($title === '' || empty($ingredients)) {
        die("Hiba: a recept neve és legalább egy alapanyag kötelező.");
    }

    $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $title]);

    $recipeId = $pdo->lastInsertId();

    $stmtIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)");
    foreach ($ingredients as $ing) {
        $ing = trim($ing);
        if ($ing !== '') {
            $stmtIng->execute([$recipeId, $ing]);
        }
    }

    header("Location: recipes.php");
    exit;
}
echo "Érvénytelen kérés.";
