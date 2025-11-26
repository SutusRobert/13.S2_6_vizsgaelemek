<?php
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name']);
    $ingredients = $_POST['ingredients'];

    if ($name === "" || empty($ingredients)) {
        die("Hiba: A mezők kitöltése kötelező!");
    }

    // 1. Recept mentése
    $stmt = $pdo->prepare("INSERT INTO recipes (user_id, name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $name]);

    $recipe_id = $pdo->lastInsertId(); // új recept ID

    // 2. Alapanyagok mentése
    $stmtIng = $pdo->prepare("INSERT INTO ingredients (recipe_id, name) VALUES (?, ?)");

    foreach ($ingredients as $ingredient) {
        $ingredient = trim($ingredient);

        if ($ingredient !== "") {
            $stmtIng->execute([$recipe_id, $ingredient]); 
        }
    }

    header("Location: recipes.php?success=1");
    exit;
}

echo "Hibás kérés!";
