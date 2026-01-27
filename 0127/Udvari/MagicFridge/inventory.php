<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

/* 1) Household kiválasztás (ugyanaz a logika, mint households.php-ben) */
$stmt = $pdo->prepare("SELECT * FROM households WHERE owner_id = ? LIMIT 1");
$stmt->execute([$userId]);
$household = $stmt->fetch();

if (!$household) {
    $stmt = $pdo->prepare("
        SELECT h.*
        FROM household_members hm
        JOIN households h ON h.id = hm.household_id
        WHERE hm.member_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $household = $stmt->fetch();
}
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

if (!$household) {
    header("Location: households.php");
    exit;
}

$householdId = (int)$household['id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function post($k, $d=''){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }

/* 2) CRUD */
$errors = [];
$success = '';
$action = post('action', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'add') {
        $name = post('name');
        $category = post('category');
        $location = post('location', 'pantry');
        $quantity = str_replace(',', '.', post('quantity', '1'));
        $unit = post('unit');
        $expires_at = post('expires_at');
        $note = post('note');

        if ($name === '') $errors[] = "A termék neve kötelező.";
        if (!in_array($location, ['fridge','pantry','freezer'], true)) $location = 'pantry';
        if (!is_numeric($quantity) || (float)$quantity <= 0) $errors[] = "A mennyiség legyen pozitív szám.";
        if ($expires_at === '') $expires_at = null;

        if (!$errors) {
            $stmt = $pdo->prepare("
                INSERT INTO inventory_items
                (household_id, name, category, location, quantity, unit, expires_at, note)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $householdId,
                $name,
                $category !== '' ? $category : null,
                $location,
                (float)$quantity,
                $unit !== '' ? $unit : null,
                $expires_at,
                $note !== '' ? $note : null
            ]);
            $success = "Hozzáadva.";
        }
    }

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
}

/* 3) Lista (keresés + szűrés) */
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$loc = isset($_GET['loc']) ? trim((string)$_GET['loc']) : '';

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
    <title>Raktár – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="bubbles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">
            <a href="dashboard.php" class="brand-back">MagicFridge</a>
        </span>
    </div>
    <div class="nav-links">
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">



        <!-- HOZZÁADÁS -->
        <h3 class="mt-5">Készlet</h3>
        <p class="inv-muted mt-2">
          A teljes készletet külön oldalon mutatjuk, hogy jobban átlátható legyen.
        </p>
        <a class="btn btn-secondary mt-3" href="inventory_list.php">Készlet megnyitása</a>


        <form method="post" class="mt-2 inv-grid">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Név</label>
                <input type="text" name="name" required placeholder="pl. Tej">
            </div>

            <div class="form-group">
                <label>Kategória (opcionális)</label>
                <input type="text" name="category" placeholder="pl. tejtermék">
            </div>

            <div class="inv-filters">
                <div class="form-group" style="margin-top:0;">
                    <label>Hely</label>
                    <select name="location">
                        <option value="fridge">Hűtő</option>
                        <option value="freezer">Fagyasztó</option>
                        <option value="pantry" selected>Kamra</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top:0;">
                    <label>Mennyiség</label>
                    <input type="number" step="0.01" name="quantity" value="1">
                </div>

                <div class="form-group" style="margin-top:0;">
                    <label>Egység (opcionális)</label>
                    <input type="text" name="unit" placeholder="db / kg / l">
                </div>
            </div>

            <div class="inv-filters">
                <div class="form-group" style="margin-top:0;">
                    <label>Lejárat (opcionális)</label>
                    <input type="date" name="expires_at">
                </div>
                <div class="form-group" style="margin-top:0; grid-column: span 2;">
                    <label>Megjegyzés (opcionális)</label>
                    <input type="text" name="note" placeholder="pl. felbontva">
                </div>
            </div>

            <div>
                <button type="submit">Hozzáadás</button>
            </div>
        </form>

     
    </div>
</div>
<script>
document.querySelectorAll('.bubbles span').forEach(b => {
  const d = parseFloat(getComputedStyle(b).animationDuration) || 20;
  b.style.animationDelay = (Math.random() * d * -1).toFixed(2) + 's';
});
</script>
</body>
</html>
