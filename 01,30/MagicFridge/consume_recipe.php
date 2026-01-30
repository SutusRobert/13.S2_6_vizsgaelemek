<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';
require 'api/translate.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$userId = (int)$_SESSION['user_id'];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function normalizeForMatch(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = str_replace(['á','é','í','ó','ö','ő','ú','ü','ű'], ['a','e','i','o','o','o','u','u','u'], $s);
    return $s;
}

/** measure -> (qty, unit) best effort */
function parseMeasureToQtyUnit(string $measure): array {
    $m = trim($measure);
    if ($m === '') return [1.0, null];

    $m = str_replace(',', '.', $m);

    // mixed fraction: "1 1/2"
    if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
        $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
        $unit = trim((string)$mm[4]);
        return [$qty, $unit !== '' ? $unit : null];
    }

    // fraction: "1/2"
    if (preg_match('/^\s*(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
        $qty = (float)$mm[1] / max(1.0, (float)$mm[2]);
        $unit = trim((string)$mm[3]);
        return [$qty, $unit !== '' ? $unit : null];
    }

    // number + unit
    if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([^\d].*)?$/u', $m, $mm)) {
        $qty = (float)$mm[1];
        $unit = trim((string)($mm[2] ?? ''));
        if ($unit !== '') $unit = preg_split('/\s+/u', $unit)[0];
        return [$qty, $unit !== '' ? $unit : null];
    }

    return [1.0, null];
}

/** convert qty between units if possible; returns [qty_in_inventory_unit, ok] */
function convertQty(float $qty, ?string $fromUnit, ?string $toUnit): array {
    $fu = $fromUnit ? normalizeForMatch($fromUnit) : null;
    $tu = $toUnit ? normalizeForMatch($toUnit) : null;

    if ($tu === null || $tu === '') {
        // inventory unit unknown -> treat as "db"
        return [$qty, true];
    }
    if ($fu === null || $fu === '') {
        // recipe has no unit -> treat as db
        return [$qty, true];
    }

    // normalize common aliases
    $map = [
        'grams' => 'g', 'gram' => 'g',
        'kilograms' => 'kg', 'kilogram' => 'kg',
        'milliliters' => 'ml', 'milliliter' => 'ml',
        'liters' => 'l', 'liter' => 'l',
        'pcs' => 'db', 'piece' => 'db', 'pieces' => 'db'
    ];
    if (isset($map[$fu])) $fu = $map[$fu];
    if (isset($map[$tu])) $tu = $map[$tu];

    // same
    if ($fu === $tu) return [$qty, true];

    // g <-> kg
    if ($fu === 'g' && $tu === 'kg') return [$qty / 1000.0, true];
    if ($fu === 'kg' && $tu === 'g') return [$qty * 1000.0, true];

    // ml <-> l
    if ($fu === 'ml' && $tu === 'l') return [$qty / 1000.0, true];
    if ($fu === 'l' && $tu === 'ml') return [$qty * 1000.0, true];

    // fallback: can't convert, but try as-is
    return [$qty, false];
}

function redirectBack(int $hid, int $mealId, string $cook, string $msg='') {
    $q = "recipe_details.php?id=".(int)$mealId."&hid=".(int)$hid."&cook=".$cook;
    if ($msg !== '') $q .= "&msg=".urlencode($msg);
    header("Location: ".$q);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recipes.php");
    exit;
}

$householdId = (int)($_POST['hid'] ?? 0);
$mealId = (int)($_POST['meal_id'] ?? 0);
if ($householdId <= 0 || $mealId <= 0) {
    header("Location: recipes.php");
    exit;
}

$meal = fetchRecipeDetails($mealId);
if (!$meal) {
    redirectBack($householdId, $mealId, 'err', 'Nem található a recept.');
}

// összegyűjtjük a hozzávalókat
$ings = [];
for ($i=1; $i<=20; $i++){
    $ingName = trim($meal["strIngredient{$i}"] ?? '');
    $measure = trim($meal["strMeasure{$i}"] ?? '');
    if ($ingName === '') continue;

    // fordítás HU-ra, hogy jobban egyezzen a raktárral (de EN-t is próbáljuk)
    $huName = translateToHungarian($ingName);

    [$qty, $unit] = parseMeasureToQtyUnit($measure);

    $ings[] = [
        'hu' => $huName,
        'en' => $ingName,
        'qty' => (float)$qty,
        'unit' => $unit,
        'measure' => $measure
    ];
}

if (!$ings) {
    redirectBack($householdId, $mealId, 'err', 'Nincs hozzávaló a receptben.');
}

try {
    $pdo->beginTransaction();

    // 1) előellenőrzés: mindenhez legyen elegendő mennyiség összesen
    foreach ($ings as $ing) {
        $needNameA = normalizeForMatch($ing['hu']);
        $needNameB = normalizeForMatch($ing['en']);

        // releváns raktár tételek (lehetőleg lejárat szerint)
        $st = $pdo->prepare("
            SELECT id, name, quantity, unit, expires_at
            FROM inventory_items
            WHERE household_id = ?
              AND (LOWER(name) LIKE ? OR LOWER(name) LIKE ?)
            ORDER BY (expires_at IS NULL) ASC, expires_at ASC, id ASC
        ");
        $st->execute([
            $householdId,
            '%'.$needNameA.'%',
            '%'.$needNameB.'%',
        ]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            $pdo->rollBack();
            redirectBack($householdId, $mealId, 'err', 'Nincs a raktárban: '.$ing['hu']);
        }

        $needQty = (float)$ing['qty'];
        $needUnit = $ing['unit']; // recipe unit

        // számoljuk össze mennyi áll rendelkezésre inventory unit-ok szerint (konverzióval)
        $available = 0.0;
        $okAny = false;

        foreach ($rows as $row) {
            $invQty = (float)$row['quantity'];
            $invUnit = $row['unit'] ?? null;

            [$convertedNeedInInvUnit, $ok] = convertQty($needQty, $needUnit, $invUnit);
            // itt csak azt nézzük: tudnánk-e konvertálni legalább valami értelmesen
            if ($ok) $okAny = true;

            // availability: inventory qty is already in invUnit -> just sum it
            $available += $invQty;
        }

        // ha nincs értelmes egység-azonosság, akkor is megpróbáljuk darabként
        if (!$okAny && $available <= 0) {
            $pdo->rollBack();
            redirectBack($householdId, $mealId, 'err', 'Egység probléma ennél: '.$ing['hu']);
        }

        // durva check: ha az inventory unit kompatibilis, a levonás úgyis pontosabb lesz alább
        // itt azt nézzük: van-e legalább 1 tétel
        // (a tényleges levonásnál, ha kevés -> err)
    }

    // 2) tényleges levonás: FIFO lejárat szerint
    foreach ($ings as $ing) {
        $needNameA = normalizeForMatch($ing['hu']);
        $needNameB = normalizeForMatch($ing['en']);

        $st = $pdo->prepare("
            SELECT id, name, quantity, unit, expires_at
            FROM inventory_items
            WHERE household_id = ?
              AND (LOWER(name) LIKE ? OR LOWER(name) LIKE ?)
            ORDER BY (expires_at IS NULL) ASC, expires_at ASC, id ASC
        ");
        $st->execute([
            $householdId,
            '%'.$needNameA.'%',
            '%'.$needNameB.'%',
        ]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $needQty = (float)$ing['qty'];
        $needUnit = $ing['unit'];

        // ha nincs unit/qty értelmesen: legyen 1 db
        if ($needQty <= 0) $needQty = 1.0;

        // Levonás tételenként
        $remainingNeed = $needQty;

        foreach ($rows as $row) {
            $invId = (int)$row['id'];
            $invQty = (float)$row['quantity'];
            $invUnit = $row['unit'] ?? null;

            // ha kompatibilis unit: akkor a need-et inventory unit-ba konvertáljuk
            [$needInInvUnit, $ok] = convertQty($remainingNeed, $needUnit, $invUnit);
            if (!$ok) {
                // ha nem ok, akkor próbáljuk darabként (1 egység = 1)
                $needInInvUnit = $remainingNeed;
            }

            if ($invQty <= 0) continue;

            if ($invQty >= $needInInvUnit) {
                $newQty = $invQty - $needInInvUnit;

                if ($newQty <= 0.00001) {
                    $del = $pdo->prepare("DELETE FROM inventory_items WHERE id = ? AND household_id = ?");
                    $del->execute([$invId, $householdId]);
                } else {
                    $up = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ? AND household_id = ?");
                    $up->execute([$newQty, $invId, $householdId]);
                }

                $remainingNeed = 0.0;
                break;
            } else {
                // elfogy a tétel
                $remainingNeed = $remainingNeed - $invQty; // kb (ha unit mismatch, ez approximáció)
                $del = $pdo->prepare("DELETE FROM inventory_items WHERE id = ? AND household_id = ?");
                $del->execute([$invId, $householdId]);

                if ($remainingNeed <= 0.00001) {
                    $remainingNeed = 0.0;
                    break;
                }
            }
        }

        if ($remainingNeed > 0.00001) {
            $pdo->rollBack();
            redirectBack($householdId, $mealId, 'err', 'Nincs elég a raktárban: '.$ing['hu'].' ('.$ing['measure'].')');
        }
    }

    $pdo->commit();
    redirectBack($householdId, $mealId, 'ok');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    redirectBack($householdId, $mealId, 'err', 'Hiba: '.$e->getMessage());
}
