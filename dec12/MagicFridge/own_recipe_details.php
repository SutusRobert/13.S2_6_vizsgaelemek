<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Érvénytelen recept ID.");
}

// Recept + tulajdonjog ellenőrzés
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['user_id']]);
$recipe = $stmt->fetch();

if (!$recipe) {
    die("Nincs ilyen saját recept, vagy nincs jogosultságod megtekinteni.");
}

// Hozzávalók lekérése
$stmt = $pdo->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
$stmt->execute([$id]);
$ingredients = $stmt->fetchAll();

$servings = 5; // 5 főre számolunk

// 1 főre kb. 250 g össz-alapanyag – ezt elosztjuk az összes hozzávaló között
function suggestQuantityForIngredientList(array $ingredients, int $servings): array
{
    $result = [];
    $count = count($ingredients) ?: 1;

    // 1 főre kb. 250 g étel
    $gramsPerPersonTotal = 250;
    $gramsPerPersonPerIngredient = $gramsPerPersonTotal / $count;

    foreach ($ingredients as $row) {
        $name = $row['ingredient'];

        // Tojás külön logika: db-ban számoljuk
        $lower = mb_strtolower($name, 'UTF-8');
        if (mb_stripos($lower, 'tojás') !== false || mb_stripos($lower, 'tojas') !== false) {
            // 1 főre 1 tojás
            $totalPieces = $servings * 1;
            $result[] = [
                'name' => $name,
                'amount' => $totalPieces . ' db'
            ];
            continue;
        }

        // Minden másnál gramm – egyenlően osztva
        $total = (int)round($gramsPerPersonPerIngredient * $servings);
        $result[] = [
            'name' => $name,
            'amount' => $total . ' g'
        ];
    }

    return $result;
}

// Recept típus felismerése – a cím + hozzávalók alapján
function detectRecipeType(string $title, array $ingredientNames): string
{
    $text = mb_strtolower($title . ' ' . implode(' ', $ingredientNames), 'UTF-8');

    // Levesek
    $soupKeywords = ['leves', 'húsleves', 'gulyás', 'gulyas', 'soup', 'ramen'];
    foreach ($soupKeywords as $k) {
        if (mb_strpos($text, $k) !== false) {
            return 'soup';
        }
    }

    // Tésztás ételek
    $pastaKeywords = ['tészta', 'teszta', 'spagetti', 'spaghetti', 'penne', 'fusilli', 'pasta', 'lasagne'];
    foreach ($pastaKeywords as $k) {
        if (mb_strpos($text, $k) !== false) {
            return 'pasta';
        }
    }

    // Saláták
    $saladKeywords = ['saláta', 'salata', 'salad', 'coleslaw'];
    foreach ($saladKeywords as $k) {
        if (mb_strpos($text, $k) !== false) {
            return 'salad';
        }
    }

    // Sütős ételek
    $bakedKeywords = ['sült', 'sult', 'tepsis', 'rakott', 'bake', 'baked', 'csőben', 'csoben'];
    foreach ($bakedKeywords as $k) {
        if (mb_strpos($text, $k) !== false) {
            return 'baked';
        }
    }

    // Pörkölt / raguk
    $stewKeywords = ['pörkölt', 'porkolt', 'ragu', 'stew'];
    foreach ($stewKeywords as $k) {
        if (mb_strpos($text, $k) !== false) {
            return 'stew';
        }
    }

    // Alapértelmezett: általános egytálétel / serpenyős
    return 'generic';
}

// Elkészítési lépések generálása típus szerint
function renderProcessSteps(string $type, string $ingredientsTextEscaped, int $servings): string
{
    $html = '<ol class="process-steps">';

    switch ($type) {
        case 'soup':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő az összes hozzávalót: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Alapanyagok előkészítése:</strong> A zöldségeket és húsokat mosd meg, tisztítsd meg, majd darabold fel egyenletes nagyságúra.</li>';
            $html .= '<li><strong>Leves alap elkészítése:</strong> Egy nagy lábasban hevíts fel kevés olajat vagy zsiradékot, majd enyhén pirítsd meg a hagymát és a fő alapanyagokat.</li>';
            $html .= '<li><strong>Felöntés vízzel:</strong> Öntsd fel bő vízzel (úgy, hogy minden alapanyag bőven ellepődjön), majd forrald fel.</li>';
            $html .= '<li><strong>Főzés:</strong> Közepes lángon főzd a levest, időnként kevergetve, amíg az összes hozzávaló megpuhul és az ízek összeérnek.</li>';
            $html .= '<li><strong>Ízesítés:</strong> Sózd, borsozd ízlés szerint, esetleg adj hozzá zöldfűszereket. Forrald még pár percig.</li>';
            $html .= '<li><strong>Tálalás:</strong> A kész levest merd tányérokba, és oszd el körülbelül ' . $servings . ' adagba.</li>';
            break;

        case 'pasta':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő az összes hozzávalót: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Tészta főzése:</strong> Egy nagy lábasban forralj fel bő, sós vizet, majd főzd benne a tésztát a csomagoláson jelzett ideig.</li>';
            $html .= '<li><strong>Szósz vagy feltét:</strong> Közben egy serpenyőben hevíts fel kevés olajat, és pirítsd vagy párold meg benne a többi hozzávalót (zöldségek, hús, szósz alap).</li>';
            $html .= '<li><strong>Összekeverés:</strong> A megfőtt tésztát szűrd le, majd keverd össze a serpenyőben készített szósszal vagy feltéttel.</li>';
            $html .= '<li><strong>Ízesítés:</strong> Sózd, borsozd, adj hozzá reszelt sajtot vagy egyéb fűszereket ízlés szerint.</li>';
            $html .= '<li><strong>Tálalás:</strong> Oszd el a tésztás ételt körülbelül ' . $servings . ' adagba, és tálald melegen.</li>';
            break;

        case 'salad':
            $html .= '<li><strong>Előkészítés:</strong> Mosd meg alaposan, majd szárítsd vagy töröld szárazra a friss hozzávalókat: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Felvágás:</strong> A zöldségeket, sajtokat, húskockákat vágd falatnyi darabokra.</li>';
            $html .= '<li><strong>Öntet készítése:</strong> Egy kis tálban keverd össze az öntet alapanyagait (pl. olaj, citromlé vagy ecet, só, bors, fűszerek).</li>';
            $html .= '<li><strong>Keverés:</strong> Egy nagy keverőtálban fogd össze az összes hozzávalót, majd locsold meg az öntettel, és óvatosan forgasd össze.</li>';
            $html .= '<li><strong>Pihentetés (opcionális):</strong> Ha van idő, hagyd 5–10 percig állni, hogy az ízek összeérjenek.</li>';
            $html .= '<li><strong>Tálalás:</strong> Oszd el a salátát kb. ' . $servings . ' adagba, és tálald frissen.</li>';
            break;

        case 'baked':
            $html .= '<li><strong>Előkészítés:</strong> Melegítsd elő a sütőt kb. 180–200 °C-ra, és készíts elő egy tepsit vagy hőálló tálat.</li>';
            $html .= '<li><strong>Hozzávalók előkészítése:</strong> A hozzávalókat (' . $ingredientsTextEscaped . ') tisztítsd meg, darabold fel, majd rendezd el a tepsiben vagy tálban.</li>';
            $html .= '<li><strong>Fűszerezés:</strong> Locsold meg olajjal vagy szószokkal, majd sózd, borsozd, fűszerezd ízlés szerint.</li>';
            $html .= '<li><strong>Sütés:</strong> Tedd a sütőbe, és süsd addig, amíg az alapanyagok átsülnek, megpuhulnak és kissé megpirulnak a tetején.</li>';
            $html .= '<li><strong>Ellenőrzés:</strong> Időnként nézz rá, és ha szükséges, keverd át vagy fedd le, hogy ne égjen meg.</li>';
            $html .= '<li><strong>Tálalás:</strong> A kész ételt vedd ki a sütőből, hagyd pár percig hűlni, majd oszd el kb. ' . $servings . ' adagba.</li>';
            break;

        case 'stew':
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő a hozzávalókat: ' . $ingredientsTextEscaped . ' – mosd, tisztítsd, darabold fel őket.</li>';
            $html .= '<li><strong>Pirítás:</strong> Egy lábasban vagy magas falú serpenyőben hevíts fel kevés zsiradékot, és pirítsd meg a hagymát, majd a húst.</li>';
            $html .= '<li><strong>Fűszerezés:</strong> Add hozzá a fűszereket (pl. paprika, só, bors, fokhagyma), és röviden pirítsd együtt.</li>';
            $html .= '<li><strong>Felöntés és párolás:</strong> Öntsd fel kevés vízzel vagy alaplével, majd fedő alatt, lassú tűzön párold, amíg a hús és zöldségek megpuhulnak.</li>';
            $html .= '<li><strong>Sűrítés (ha szükséges):</strong> Ha szeretnéd sűríteni, egy kevés liszttel vagy tejföllel krémesebbé teheted a szaftot.</li>';
            $html .= '<li><strong>Tálalás:</strong> Tálald körettel (pl. tésztával, rizzsel, nokedlivel), kb. ' . $servings . ' adagra elosztva.</li>';
            break;

        case 'generic':
        default:
            $html .= '<li><strong>Előkészítés:</strong> Készítsd elő az összes hozzávalót: ' . $ingredientsTextEscaped . '.</li>';
            $html .= '<li><strong>Előkészítés vágással:</strong> Mosd meg, tisztítsd meg, és darabold fel a nagyobb összetevőket egyenletes méretűre.</li>';
            $html .= '<li><strong>Hőkezelés:</strong> Melegíts fel egy serpenyőt vagy lábast kis mennyiségű olajjal, majd kezdd el pirítani a fő alapanyagokat.</li>';
            $html .= '<li><strong>Hozzávalók hozzáadása:</strong> Fokozatosan add hozzá a többi összetevőt, és időnként keverd át, hogy ne égjen le.</li>';
            $html .= '<li><strong>Ízesítés:</strong> Sózd, borsozd, és fűszerezd ízlés szerint. Ha szükséges, adj hozzá egy kevés vizet, tejet vagy tejszínt.</li>';
            $html .= '<li><strong>Befejezés:</strong> Főzd/süsd az ételt addig, amíg minden megpuhul, és az ízek összeérnek.</li>';
            $html .= '<li><strong>Tálalás:</strong> Oszd el az elkészült ételt kb. ' . $servings . ' adagba, és tálald melegen.</li>';
            break;
    }

    $html .= '</ol>';

    return $html;
}

// Összefűzött hozzávaló lista a leíráshoz
$ingredientNames = array_map(fn($row) => $row['ingredient'], $ingredients);
$ingredientsText = implode(', ', $ingredientNames);
$ingredientsTextEscaped = htmlspecialchars($ingredientsText, ENT_QUOTES, 'UTF-8');

$quantities = suggestQuantityForIngredientList($ingredients, $servings);
$type = detectRecipeType($recipe['title'], $ingredientNames);
$stepsHtml = renderProcessSteps($type, $ingredientsTextEscaped, $servings);
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['title']) ?> – Saját recept – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .recipe-img-placeholder {
            width: 100%;
            height: 180px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            background: radial-gradient(circle at top left, #6366f1, #0f172a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            font-size: 20px;
            font-weight: 600;
        }
        .ingredient {
            background: rgba(15, 23, 42, 0.9);
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            color: #e2e8f0;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .ingredient-name {
            flex: 1;
        }
        .ingredient-amount {
            white-space: nowrap;
            font-weight: 600;
        }
        ol.process-steps {
            margin-top: 10px;
            margin-left: 18px;
        }
        ol.process-steps li {
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="recipes.php">Vissza a receptekhez</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">

        <h1><?= htmlspecialchars($recipe['title']) ?></h1>

        <div class="recipe-img-placeholder">
            Saját recept
        </div>

        <h2>Hozzávalók (<?= $servings ?> főre, kb. 250 g/fő)</h2>
        <?php if (empty($quantities)): ?>
            <p>Még nincsenek hozzávalók ehhez a recepthez.</p>
        <?php else: ?>
            <?php foreach ($quantities as $row): ?>
                <div class="ingredient">
                    <span class="ingredient-name"><?= htmlspecialchars($row['name']) ?></span>
                    <span class="ingredient-amount"><?= htmlspecialchars($row['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="mt-4">Elkészítés – automatikus leírás (<?= $servings ?> főre)</h2>

        <?php if (!empty($ingredients)): ?>
            <?= $stepsHtml ?>
        <?php else: ?>
            <p>Ehhez a recepthez még nincsenek hozzávalók, ezért automatikus elkészítési leírás sem generálható.</p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
