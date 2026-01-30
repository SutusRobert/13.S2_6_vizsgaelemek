<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

function cleanText($s){
    $s = trim((string)$s);
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create_recipe.php");
    exit;
}

$title = cleanText($_POST['title'] ?? '');
$servings = (int)($_POST['servings'] ?? 5);
$instructions = trim((string)($_POST['instructions'] ?? ''));

$ingredients = $_POST['ingredients'] ?? [];
if (!is_array($ingredients)) $ingredients = [];

$ingredientsClean = [];
foreach ($ingredients as $ing) {
    $t = trim((string)$ing);
    if ($t !== '') $ingredientsClean[] = $t;
}

if ($title === '' || empty($ingredientsClean)) {
    header("Location: create_recipe.php");
    exit;
}
if ($servings < 1) $servings = 1;
if ($servings > 50) $servings = 50;

/* ==============
   Kép feltöltés
   ============== */
$imagePath = null;

if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['image']['tmp_name'];
        $name = $_FILES['image']['name'] ?? 'image';
        $type = $_FILES['image']['type'] ?? '';

        if (!is_uploaded_file($tmp)) {
            // ignore
        } else {
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (!in_array($type, $allowed, true)) {
                // próbáljuk extension alapján is
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
                    $type = '';
                }
            }

            if ($type !== '') {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext === '') $ext = 'jpg';

                $dir = __DIR__ . '/uploads/recipes';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }

                $safe = preg_replace('/[^a-zA-Z0-9_\-\.]+/', '_', pathinfo($name, PATHINFO_FILENAME));
                $filename = 'r_' . $userId . '_' . time() . '_' . $safe . '.' . $ext;
                $dest = $dir . '/' . $filename;

                if (@move_uploaded_file($tmp, $dest)) {
                    // web path
                    $imagePath = 'uploads/recipes/' . $filename;
                }
            }
        }
    }
}

try {
    $pdo->beginTransaction();

    // recipes insert
    // (ha nálad még nincs servings/instructions/image_path oszlop, dobd rá az ALTER TABLE-eket)
    $st = $pdo->prepare("
        INSERT INTO recipes (user_id, title, servings, instructions, image_path, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $st->execute([$userId, $title, $servings, $instructions !== '' ? $instructions : null, $imagePath]);

    $recipeId = (int)$pdo->lastInsertId();

    // ingredients
    $stIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)");
    foreach ($ingredientsClean as $ing) {
        $stIng->execute([$recipeId, $ing]);
    }

    $pdo->commit();

    header("Location: own_recipe_details.php?id=".$recipeId);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    // ha kép felment, de db nem, akkor töröljük (ne maradjon szemét)
    if ($imagePath) {
        $abs = __DIR__ . '/' . $imagePath;
        if (is_file($abs)) @unlink($abs);
    }

    // debug helyett irány vissza
    header("Location: create_recipe.php");
    exit;
}
