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

// --- HOUSEHOLDS: owner + member
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

// --- kiválasztott household: GET hid, vagy az első
$selectedHid = isset($_GET['hid']) ? (int)$_GET['hid'] : (int)$households[0]['household_id'];

// --- jogosultság check: benne van-e a listában?
$householdMap = [];
foreach ($households as $h) $householdMap[(int)$h['household_id']] = $h['name'];

if (!isset($householdMap[$selectedHid])) {
    // ha valaki kézzel átírja az URL-t
    $selectedHid = (int)$households[0]['household_id'];
}

$householdId = $selectedHid;
$householdName = $householdMap[$householdId];

/* Household (owner + member) – így admin + tag is működik */
$stmt = $pdo->prepare("
    SELECT id AS household_id, name FROM households WHERE owner_id = ?
    UNION
    SELECT h.id AS household_id, h.name
    FROM household_members hm
    JOIN households h ON h.id = hm.household_id
    WHERE hm.member_id = ?
");
$stmt->execute([$userId, $userId]);
$households = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$households) {
    header("Location: households.php");
    exit;
}

$householdId = (int)$households[0]['household_id'];
$householdName = $households[0]['name'];

$errors = [];
$success = '';
$action = post('action', '');





if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Új tétel */
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

    /* Megvett / vissza (Megvett -> feltölt raktárba) */
    if ($action === 'toggle') {
        $id = (int)post('id', '0');
        $to = (int)post('to', '0'); // 0 vagy 1
        if (!in_array($to, [0,1], true)) $to = 0;

        // lekérjük a tételt (hogy tudjuk mit kell feltölteni)
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
                // 1) shopping list státusz frissítés
                $stmt = $pdo->prepare("
                    UPDATE shopping_list_items
                    SET is_bought = ?,
                        bought_at = IF(? = 1, NOW(), NULL),
                        bought_by = IF(? = 1, ?, NULL)
                    WHERE id = ? AND household_id = ?
                ");
                $stmt->execute([$to, $to, $to, $userId, $id, $householdId]);

                // 2) ha megvett -> raktárba feltölt
                if ($to === 1) {
                    $name = (string)$item['name'];
                    $qty  = (float)$item['quantity'];
                    $unit = $item['unit'] !== null ? (string)$item['unit'] : null;
                    $note = $item['note'] !== null ? (string)$item['note'] : null;
                    $loc  = in_array($item['location'], ['fridge','freezer','pantry'], true) ? $item['location'] : 'pantry';

                    // Ha már van ilyen termék ugyanott, akkor összeadjuk a mennyiséget
                    $find = $pdo->prepare("
                        SELECT id, quantity
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

    /* Törlés */
    if ($action === 'delete') {
        $id = (int)post('id', '0');
        $stmt = $pdo->prepare("DELETE FROM shopping_list_items WHERE id = ? AND household_id = ?");
        $stmt->execute([$id, $householdId]);
        $success = "Törölve.";
    }

    /* Megvettek törlése */
    if ($action === 'clear_bought') {
        $stmt = $pdo->prepare("DELETE FROM shopping_list_items WHERE household_id = ? AND is_bought = 1");
        $stmt->execute([$householdId]);
        $success = "A megvett tételek törölve.";
    }
}

/* Lista (mindent megmutatunk, megvett külön “kész” jelöléssel) */
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
        <a href="shopping_list.php?hid=<?= $householdId ?>">Bevásárlólista</a>
        <a href="inventory.php">Raktár</a>
        <a href="inventory_list.php">Készlet</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<form method="get" style="margin:0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <label class="small" style="opacity:.8;">Háztartás</label>
    <select name="hid" onchange="this.form.submit()">
        <?php foreach ($households as $h): $hid = (int)$h['household_id']; ?>
            <option value="<?= $hid ?>" <?= $hid === $householdId ? 'selected' : '' ?>>
                <?= h($h['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>


<div class="main-wrapper">
    <div class="card" style="max-width: 1100px; width:100%;">

        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
            <div>
                <h2 style="margin-bottom:6px;">Bevásárlólista</h2>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>

            <div class="sl-printbar">
                <button type="button" class="btn btn-secondary" onclick="window.print()">Nyomtatás</button>

                <form method="post" style="margin:0;" onsubmit="return confirm('Törlöd az összes megvett tételt?');">
                    <input type="hidden" name="action" value="clear_bought">
                    <button type="submit" class="btn btn-secondary">Megvett törlése</button>
                </form>
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
                        <!-- Megvett gomb -->
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                            <input type="hidden" name="to" value="<?= $it['is_bought'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-secondary btn-mini" title="<?= $it['is_bought'] ? 'Visszavonás' : 'Megvett' ?>">
                                <?= $it['is_bought'] ? '↩' : '✓' ?>
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
                        <!-- Törlés -->
                        <form method="post" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
                            <input type="hidden" name="action" value="delete">
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
