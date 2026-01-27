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
   HOUSEHOLDS: owner + member
   - több háztartás esetén ?hid=... váltás
   ================================ */
$stmt = $pdo->prepare("
    SELECT id AS household_id, name
    FROM households
    WHERE owner_id = ?
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

/* kiválasztott household: POST (műveletek) vagy GET (nézet) */
$householdId = null;
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
   Szűrők (GET)
   ================================ */
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$loc = isset($_GET['loc']) ? trim((string)$_GET['loc']) : '';

/* ================================
   CRUD: update / delete
   ================================ */
$errors = [];
$success = '';
$action = post('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'update') {
        $id = (int)post('id', '0');
        $location = post('location', 'pantry');
        $quantity = str_replace(',', '.', post('quantity', '1'));
        $expires_at = post('expires_at');

        if (!in_array($location, ['fridge','pantry','freezer'], true)) $location = 'pantry';
        if (!is_numeric($quantity) || (float)$quantity <= 0) $errors[] = "A mennyiség legyen pozitív szám.";
        if ($expires_at === '') $expires_at = null;

        if (!$errors) {
            $stmt = $pdo->prepare("
                UPDATE inventory_items
                SET location = ?, quantity = ?, expires_at = ?
                WHERE id = ? AND household_id = ?
            ");
            $stmt->execute([$location, (float)$quantity, $expires_at, $id, $householdId]);
            $success = "Mentve.";
        }
    }

    if ($action === 'delete') {
        $id = (int)post('id', '0');
        $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ? AND household_id = ?");
        $stmt->execute([$id, $householdId]);
        $success = "Törölve.";
    }

    /* POST után redirect (ne legyen duplázás frissítésre) */
    $redir = "inventory_list.php?hid=" . urlencode((string)$householdId);
    if ($q !== '') $redir .= "&q=" . urlencode($q);
    if ($loc !== '') $redir .= "&loc=" . urlencode($loc);

    // Ha volt hiba, nem redirectelünk, hogy látszódjon
    if (!$errors) {
        header("Location: " . $redir);
        exit;
    }
}

/* ================================
   Lista lekérés (hid + szűrők)
   ================================ */
$where = "household_id = ?";
$params = [$householdId];

if ($q !== '') {
    $where .= " AND name LIKE ?";
    $params[] = "%$q%";
}
if (in_array($loc, ['fridge','pantry','freezer'], true)) {
    $where .= " AND location = ?";
    $params[] = $loc;
}

$stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE $where ORDER BY expires_at IS NULL, expires_at ASC, id DESC");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTime('today');
$soon = (clone $today)->modify('+3 days');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Készlet – MagicFridge</title>
    <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">
            <a href="dashboard.php" class="brand-back">MagicFridge</a>
        </span>
    </div>
    <div class="nav-links">
        <a href="inventory.php?hid=<?= (int)$householdId ?>">Raktár</a>
        <a href="inventory_list.php?hid=<?= (int)$householdId ?>">Készlet</a>
        <a href="shopping_list.php?hid=<?= (int)$householdId ?>">Bevásárlólista</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card" style="max-width: 1200px; width: 100%;">

        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <h2 style="margin-bottom:6px;">Készlet</h2>
                <div class="small">Háztartás: <strong><?= h($householdName) ?></strong></div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <a class="btn btn-secondary" href="inventory.php?hid=<?= (int)$householdId ?>">+ Új termék</a>
            </div>
        </div>

        <!-- Households dropdown -->
        <div class="mt-3">
            <form method="get" style="margin:0;">
                <label class="small" style="opacity:.8;">Háztartás</label>
                <select name="hid" onchange="this.form.submit()">
                    <?php foreach ($households as $hh): $hidOpt = (int)$hh['household_id']; ?>
                        <option value="<?= $hidOpt ?>" <?= $hidOpt === (int)$householdId ? 'selected' : '' ?>>
                            <?= h($hh['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Szűrők megtartása household váltásnál -->
                <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif; ?>
                <?php if ($loc !== ''): ?><input type="hidden" name="loc" value="<?= h($loc) ?>"><?php endif; ?>
            </form>
        </div>

        <?php if ($success): ?>
            <div class="success mt-3"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error mt-3">
                <strong>Hiba:</strong>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach($errors as $e): ?>
                        <li><?= h($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- SZŰRÉS -->
        <form method="get" class="mt-4 inv-filters">
            <input type="hidden" name="hid" value="<?= (int)$householdId ?>">

            <div class="form-group" style="margin-top:0;">
                <label>Keresés</label>
                <input type="text" name="q" placeholder="pl. tej, tojás..." value="<?= h($q) ?>">
            </div>

            <div class="form-group" style="margin-top:0;">
                <label>Hely</label>
                <select name="loc">
                    <option value="">Minden</option>
                    <option value="fridge"  <?= $loc==='fridge'?'selected':'' ?>>Hűtő</option>
                    <option value="freezer" <?= $loc==='freezer'?'selected':'' ?>>Fagyasztó</option>
                    <option value="pantry"  <?= $loc==='pantry'?'selected':'' ?>>Kamra</option>
                </select>
            </div>

            <div style="display:flex; gap:10px; align-items:end;">
                <button type="submit">Szűrés</button>
                <a class="btn btn-secondary" href="inventory_list.php?hid=<?= (int)$householdId ?>">Reset</a>
            </div>
        </form>

        <!-- LISTA -->
        <div class="mt-4">
            <?php if (!$items): ?>
                <p class="inv-muted">Még nincs termék a raktárban.</p>
            <?php else: ?>
                <table class="inv-table">
                    <thead>
                        <tr>
                            <th>Termék</th>
                            <th>Hely</th>
                            <th>Mennyiség</th>
                            <th>Lejárat</th>
                            <th style="text-align:right;">Művelet</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($items as $it): ?>
                        <?php
                            $badgeClass = "badge-ok";
                            $badgeText = "OK";
                            if (!empty($it['expires_at'])) {
                                $d = new DateTime($it['expires_at']);
                                if ($d < $today) { $badgeClass="badge-danger"; $badgeText="Lejárt"; }
                                else if ($d <= $soon) { $badgeClass="badge-warn"; $badgeText="Hamarosan"; }
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?= h($it['name']) ?></strong>
                                <?php if (!empty($it['category'])): ?>
                                    <div class="inv-muted"><?= h($it['category']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $it['location']==='fridge' ? 'Hűtő' : ($it['location']==='freezer' ? 'Fagyasztó' : 'Kamra') ?>
                            </td>
                            <td><?= h($it['quantity']) ?> <?= h($it['unit']) ?></td>
                            <td>
                                <?php if (!empty($it['expires_at'])): ?>
                                    <span class="badge <?= $badgeClass ?>"><?= h($badgeText) ?></span>
                                    <span class="inv-muted"> <?= h($it['expires_at']) ?></span>
                                <?php else: ?>
                                    <span class="inv-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="inv-actions">

                                    <!-- UPDATE -->
                                    <form method="post" style="display:flex; gap:8px; align-items:center; margin:0;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
                                        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif; ?>
                                        <?php if ($loc !== ''): ?><input type="hidden" name="loc" value="<?= h($loc) ?>"><?php endif; ?>

                                        <select name="location" style="min-width:130px;">
                                            <option value="fridge"  <?= $it['location']==='fridge'?'selected':'' ?>>Hűtő</option>
                                            <option value="freezer" <?= $it['location']==='freezer'?'selected':'' ?>>Fagyasztó</option>
                                            <option value="pantry"  <?= $it['location']==='pantry'?'selected':'' ?>>Kamra</option>
                                        </select>

                                        <input type="number" step="0.01" name="quantity" value="<?= h($it['quantity']) ?>" style="max-width:110px;">

                                        <input type="date" name="expires_at" value="<?= h($it['expires_at']) ?>" style="max-width:150px;">

                                        <button type="submit" class="btn-mini">Mentés</button>
                                    </form>

                                    <!-- DELETE -->
                                    <form method="post" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                                        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
                                        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= h($q) ?>"><?php endif; ?>
                                        <?php if ($loc !== ''): ?><input type="hidden" name="loc" value="<?= h($loc) ?>"><?php endif; ?>
                                        <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
