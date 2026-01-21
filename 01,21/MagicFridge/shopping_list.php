<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$userId = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function post($k, $d=''){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }

/* ================================
   HOUSEHOLDS: owner + member + HID választás (GET/POST)
   ================================ */
$stmt = $pdo->prepare("
    SELECT id AS household_id, name FROM households WHERE owner_id = ?
    UNION
    SELECT h.id AS household_id, h.name
    FROM household_members hm
    JOIN households h ON h.id = hm.household_id
    WHERE hm.member_id = ?
    ORDER BY household_id ASC
");
$stmt->execute([$userId, $userId]);
$households = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$households) {
    header("Location: households.php");
    exit;
}

$householdMap = [];
foreach ($households as $hh) {
    $householdMap[(int)$hh['household_id']] = $hh['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hid'])) {
    $householdId = (int)$_POST['hid'];
} elseif (isset($_GET['hid'])) {
    $householdId = (int)$_GET['hid'];
} else {
    $householdId = (int)$households[0]['household_id'];
}

if (!isset($householdMap[$householdId])) {
    $householdId = (int)$households[0]['household_id'];
}
$householdName = $householdMap[$householdId];

/* ================================
   Segédek
   ================================ */
function invNamesForHousehold(PDO $pdo, int $householdId): array {
    $stmt = $pdo->prepare("
        SELECT LOWER(TRIM(name)) AS n
        FROM inventory_items
        WHERE household_id = ?
        GROUP BY LOWER(TRIM(name))
    ");
    $stmt->execute([$householdId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function invContains(array $invNames, string $needle): bool {
    $needle = mb_strtolower(trim($needle), 'UTF-8');
    if ($needle === '') return false;
    foreach ($invNames as $n) {
        // részleges egyezés is oké (pl. 'tej' megtalálja 'tej 2.8%')
        if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
            return true;
        }
    }
    return false;
}
function guessLocationForItem(string $name): string {
    $n = mb_strtolower(trim($name), 'UTF-8');

    // Fagyasztó
    $freezer = [
        'fagyaszt', 'mirelit', 'jég', 'jeg',
        'fagyasztott', 'nugget', 'hasáb', 'hasab',
        'pizza', 'spenót', 'spenot', 'borsó', 'borso'
    ];

    // Hűtő
    $fridge = [
        'tej', 'joghurt', 'sajt', 'tejszín', 'tejszin', 'vaj', 'margarin',
        'tojás', 'tojas',
        'csirke', 'pulyka', 'marha', 'sertés', 'sertes', 'hal',
        'sonka', 'kolbász', 'kolbasz'
    ];

    foreach ($freezer as $k) {
        if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'freezer';
    }
    foreach ($fridge as $k) {
        if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'fridge';
    }

    return 'pantry';
}


$errors = [];
$success = '';
$action = post('action', '');

/* ================================
   POST műveletek
   ================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 0) Egyedi hiányzók felvétele (API receptről jön) */
    if ($action === 'add_missing_api') {
        $recipeTitle = post('recipe_title', '');
        $location = post('location', 'pantry');
        if (!in_array($location, ['fridge','freezer','pantry'], true)) $location = 'pantry';

        $invNames = invNamesForHousehold($pdo, $householdId);

        $names = $_POST['missing_name'] ?? [];
        if (!is_array($names)) $names = [];

        $insert = $pdo->prepare("
            INSERT INTO shopping_list_items
            (household_id, name, quantity, unit, note, location, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $added = 0;
        foreach ($names as $nmRaw) {
            $nm = trim((string)$nmRaw);
            if ($nm === '') continue;

            // ha már van a raktárban, ne add hozzá
            if (invContains($invNames, $nm)) continue;

            $note = $recipeTitle !== '' ? ("Recept: " . $recipeTitle) : null;
            $loc = guessLocationForItem($nm);

            $insert->execute([
            $householdId,
            $nm,
            1,
            null,
            $note,
            $loc,
            $userId
             ]);
            $added++;
        }

        $success = $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Nincs hozzáadandó hiányzó tétel.";
    }

    /* 1) Saját recept hiányzóinak felvétele */
    if ($action === 'add_missing_for_own_recipe') {
        $recipeId = (int)post('recipe_id', '0');

        // recept cím a note-ba
        $stmt = $pdo->prepare("SELECT title FROM recipes WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$recipeId, $userId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$r) {
            $errors[] = "Nincs ilyen saját recept, vagy nincs jogosultságod.";
        } else {
            $stmt = $pdo->prepare("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
            $stmt->execute([$recipeId]);
            $ings = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $invNames = invNamesForHousehold($pdo, $householdId);

            $insert = $pdo->prepare("
                INSERT INTO shopping_list_items
                (household_id, name, quantity, unit, note, location, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $added = 0;
            foreach ($ings as $nmRaw) {
                $nm = trim((string)$nmRaw);
                if ($nm === '') continue;

                if (invContains($invNames, $nm)) continue;

                $insert->execute([
                    $householdId,
                    $nm,
                    1,
                    null,
                    "Recept: " . $r['title'],
                    "pantry",
                    $userId
                ]);
                $added++;
            }

            $success = $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Minden hozzávaló megvan a raktárban.";
        }
    }

    /* 2) Új tétel */
    if ($action === 'add') {
        $name = post('name');
        $quantity = str_replace(',', '.', post('quantity', '1'));
        $unit = post('unit');
        $note = post('note');
        $location = post('location', 'pantry');

        if ($name === '') $errors[] = "A termék neve kötelező.";
        if (!is_numeric($quantity) || (float)$quantity <= 0) $errors[] = "A mennyiség legyen pozitív szám.";
        if (!in_array($location, ['fridge','freezer','pantry'], true)) $location = 'pantry';

        if (!$errors) {
            $stmt = $pdo->prepare("
                INSERT INTO shopping_list_items
                (household_id, name, quantity, unit, note, location, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $householdId,
                $name,
                (float)$quantity,
                $unit !== '' ? $unit : null,
                $note !== '' ? $note : null,
                $location,
                $userId
            ]);
            $success = "Hozzáadva a listához.";
        }
    }

    /* 3) Megvett / vissza (Megvett -> feltölt raktárba) */
    if ($action === 'toggle') {
        $id = (int)post('id', '0');
        $to = (int)post('to', '0');
        if (!in_array($to, [0,1], true)) $to = 0;

        $stmt = $pdo->prepare("
            SELECT id, household_id, name, quantity, unit, note, location, is_bought
            FROM shopping_list_items
            WHERE id = ? AND household_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $householdId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    UPDATE shopping_list_items
                    SET is_bought = ?,
                        bought_at = IF(? = 1, NOW(), NULL),
                        bought_by = IF(? = 1, ?, NULL)
                    WHERE id = ? AND household_id = ?
                ");
                $stmt->execute([$to, $to, $to, $userId, $id, $householdId]);

                if ($to === 1) {
                    $name = (string)$item['name'];
                    $qty  = (float)$item['quantity'];
                    $unit = $item['unit'] !== null ? (string)$item['unit'] : null;
                    $note = $item['note'] !== null ? (string)$item['note'] : null;
                    $loc  = in_array($item['location'], ['fridge','freezer','pantry'], true) ? $item['location'] : 'pantry';

                    $find = $pdo->prepare("
                        SELECT id
                        FROM inventory_items
                        WHERE household_id = ? AND name = ? AND location = ?
                        ORDER BY id DESC
                        LIMIT 1
                    ");
                    $find->execute([$householdId, $name, $loc]);
                    $existing = $find->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        $upd = $pdo->prepare("
                            UPDATE inventory_items
                            SET quantity = quantity + ?
                            WHERE id = ? AND household_id = ?
                        ");
                        $upd->execute([$qty, (int)$existing['id'], $householdId]);
                    } else {
                        $ins = $pdo->prepare("
                            INSERT INTO inventory_items
                            (household_id, name, category, location, quantity, unit, expires_at, note)
                            VALUES (?, ?, NULL, ?, ?, ?, NULL, ?)
                        ");
                        $ins->execute([$householdId, $name, $loc, $qty, $unit, $note]);
                    }
                }

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors[] = "Hiba történt a megvett jelölésnél.";
            }
        }
    }

    /* 4) Törlés */
    if ($action === 'delete') {
        $id = (int)post('id', '0');
        $stmt = $pdo->prepare("DELETE FROM shopping_list_items WHERE id = ? AND household_id = ?");
        $stmt->execute([$id, $householdId]);
        $success = "Törölve.";
    }

    /* 5) Megvettek törlése */
    if ($action === 'clear_bought') {
        $stmt = $pdo->prepare("DELETE FROM shopping_list_items WHERE household_id = ? AND is_bought = 1");
        $stmt->execute([$householdId]);
        $success = "A megvett tételek törölve.";
    }

    // POST után redirect, hogy frissítésre ne ismételje
    if (empty($errors)) {
        header("Location: shopping_list.php?hid=" . urlencode((string)$householdId));
        exit;
    }
}

/* ================================
   Lista
   ================================ */
$stmt = $pdo->prepare("
    SELECT *
    FROM shopping_list_items
    WHERE household_id = ?
    ORDER BY is_bought ASC, id DESC
");
$stmt->execute([$householdId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function locLabel($loc){
    if ($loc === 'fridge') return 'Hűtő';
    if ($loc === 'freezer') return 'Fagyasztó';
    return 'Kamra';
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Bevásárlólista – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title"><a href="dashboard.php" class="brand-back">MagicFridge</a></span>
    </div>
    <div class="nav-links">
        <a href="shopping_list.php?hid=<?= (int)$householdId ?>">Bevásárlólista</a>
        <a href="inventory.php?hid=<?= (int)$householdId ?>">Raktár</a>
        <a href="inventory_list.php?hid=<?= (int)$householdId ?>">Készlet</a>
        <a href="recipes.php?hid=<?= (int)$householdId ?>">Receptek</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card" style="max-width: 1100px; width:100%;">

        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
            <div>
                <h2 style="margin-bottom:6px;">Bevásárlólista</h2>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>

            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <form method="get" style="margin:0; display:flex; gap:10px; align-items:center;">
                    <label class="small" style="opacity:.8;">Háztartás</label>
                    <select name="hid" onchange="this.form.submit()">
                        <?php foreach ($households as $hh): $hidOpt = (int)$hh['household_id']; ?>
                            <option value="<?= $hidOpt ?>" <?= $hidOpt === (int)$householdId ? 'selected' : '' ?>>
                                <?= h($hh['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <div class="sl-printbar">
                    <button type="button" class="btn btn-secondary" onclick="window.print()">Nyomtatás</button>

                    <form method="post" style="margin:0;" onsubmit="return confirm('Törlöd az összes megvett tételt?');">
                        <input type="hidden" name="action" value="clear_bought">
                        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
                        <button type="submit" class="btn btn-secondary">Megvett törlése</button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="success mt-3"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error mt-3">
                <strong>Hiba:</strong>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h3 class="mt-4">Új tétel</h3>
        <form method="post" class="sl-row mt-2">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">

            <div class="form-group" style="flex: 1 1 260px;">
                <label>Termék</label>
                <input type="text" name="name" placeholder="pl. kenyér" required>
            </div>

            <div class="form-group" style="flex: 0 0 140px;">
                <label>Mennyiség</label>
                <input type="number" step="0.01" name="quantity" value="1">
            </div>

            <div class="form-group" style="flex: 0 0 160px;">
                <label>Egység</label>
                <input type="text" name="unit" placeholder="db / kg / l">
            </div>

            <div class="form-group" style="flex: 0 0 170px;">
                <label>Hely (raktár)</label>
                <select name="location">
                    <option value="fridge">Hűtő</option>
                    <option value="freezer">Fagyasztó</option>
                    <option value="pantry" selected>Kamra</option>
                </select>
            </div>

            <div class="form-group" style="flex: 1 1 260px;">
                <label>Megjegyzés</label>
                <input type="text" name="note" placeholder="pl. teljes kiőrlésű">
            </div>

            <div style="flex:0 0 auto;">
                <button type="submit">Hozzáadás</button>
            </div>
        </form>

        <h3 class="mt-4">Lista</h3>

        <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
            <?php if (!$items): ?>
                <div class="small" style="opacity:.8;">Nincs tétel.</div>
            <?php endif; ?>

            <?php foreach($items as $it): ?>
                <div class="sl-item">
                    <div class="sl-left">
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="to" value="<?= $it['is_bought'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-secondary btn-mini">
                                <?= $it['is_bought'] ? 'Vissza' : 'Megvett' ?>
                            </button>
                        </form>

                        <div>
                            <div class="sl-name <?= $it['is_bought'] ? 'sl-done' : '' ?>">
                                <?= h($it['name']) ?>
                                <span class="small" style="opacity:.75;">
                                  — <?= h($it['quantity']) ?> <?= h($it['unit']) ?>
                                </span>
                                <span class="small" style="opacity:.75;"> • <?= h(locLabel($it['location'])) ?></span>
                            </div>

                            <?php if (!empty($it['note'])): ?>
                                <div class="sl-meta"><?= h($it['note']) ?></div>
                            <?php endif; ?>

                            <?php if ($it['is_bought'] && !empty($it['bought_at'])): ?>
                                <div class="sl-meta">Megvéve: <?= h($it['bought_at']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sl-actions">
                        <form method="post" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="small mt-4" style="opacity:.75;">
            Tipp: ha “Megvett”-re nyomsz, a tétel automatikusan felkerül a raktárba a kiválasztott helyre.
        </div>

    </div>
</div>

</body>
</html>
