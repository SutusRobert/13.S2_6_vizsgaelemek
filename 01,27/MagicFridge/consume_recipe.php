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

function userHasHouseholdAccess(PDO $pdo, int $userId, int $hid): bool {
    $stmt = $pdo->prepare("
        SELECT 1
        FROM households
        WHERE id = ? AND owner_id = ?
        UNION
        SELECT 1
        FROM household_members
        WHERE household_id = ? AND member_id = ?
        LIMIT 1
    ");
    $stmt->execute([$hid, $userId, $hid, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Measure parse: pl. "1 1/2 cup", "300g", "0.5 l", "2 tbsp"
 * return: [qty(float|null), unit(string|null)]
 */
function parseMeasure(string $measure): array {
    $m = trim($measure);
    if ($m === '') return [null, null];

    // comma -> dot
    $m = str_replace(',', '.', $m);

    $qty = null;
    $unit = null;

    // Mixed fraction: "1 1/2"
    if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)/u', $m, $mm)) {
        $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
        $unit = trim(preg_replace('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)/u', '', $m));
        return [$qty, normalizeUnit($unit)];
    }

    // Fraction: "1/2"
    if (preg_match('/^\s*(\d+)\s*\/\s*(\d+)/u', $m, $mm)) {
        $qty = (float)$mm[1] / max(1.0, (float)$mm[2]);
        $unit = trim(preg_replace('/^\s*(\d+)\s*\/\s*(\d+)/u', '', $m));
        return [$qty, normalizeUnit($unit)];
    }

    // Decimal/int: "2", "2.5"
    if (preg_match('/^\s*(\d+(?:\.\d+)?)/u', $m, $mm)) {
        $qty = (float)$mm[1];
        $unit = trim(preg_replace('/^\s*(\d+(?:\.\d+)?)/u', '', $m));
        return [$qty, normalizeUnit($unit)];
    }

    // no numeric
    return [null, normalizeUnit($m)];
}

function normalizeUnit(?string $unit): ?string {
    $u = trim((string)$unit);
    if ($u === '') return null;

    $u = mb_strtolower($u, 'UTF-8');

    // töröljük a felesleges pontokat
    $u = str_replace(['.', ','], '', $u);

    $map = [
        'g' => 'g', 'gram' => 'g', 'grams' => 'g',
        'kg' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'ml' => 'ml', 'milliliter' => 'ml', 'milliliters' => 'ml',
        'l' => 'l', 'liter' => 'l', 'liters' => 'l',

        'tbsp' => 'tbsp', 'tbs' => 'tbsp', 'tablespoon' => 'tbsp', 'tablespoons' => 'tbsp',
        'tsp' => 'tsp', 'teaspoon' => 'tsp', 'teaspoons' => 'tsp',

        'cup' => 'cup', 'cups' => 'cup',

        'pcs' => 'pcs', 'pc' => 'pcs', 'piece' => 'pcs', 'pieces' => 'pcs',
        'db' => 'pcs',
    ];

    // ha több szó van (pl. "tbsp sugar"), az első token unit
    $parts = preg_split('/\s+/u', $u);
    $first = $parts[0] ?? $u;

    return $map[$first] ?? $first;
}

/**
 * Konverzió base egységre:
 * - tömeg: g (kg -> g)
 * - térfogat: ml (l -> ml)
 * - darab: pcs
 * - egyéb: változatlan (nem konvertálható)
 */
function toBaseUnit(float $qty, ?string $unit): array {
    $u = normalizeUnit($unit);

    // tömeg -> g
    if ($u === 'kg') return [$qty * 1000.0, 'g'];
    if ($u === 'g')  return [$qty, 'g'];

    // térfogat -> ml
    if ($u === 'l')  return [$qty * 1000.0, 'ml'];
    if ($u === 'ml') return [$qty, 'ml'];

    // darab
    if ($u === 'pcs') return [$qty, 'pcs'];

    // nem konvertálható (tbsp/tsp/cup stb. itt marad)
    return [$qty, $u];
}

function unitsConvertible(?string $a, ?string $b): bool {
    $ua = normalizeUnit($a);
    $ub = normalizeUnit($b);

    if ($ua === null || $ub === null) return false;
    if ($ua === $ub) return true;

    $mass = ['g','kg'];
    $vol  = ['ml','l'];

    if (in_array($ua, $mass, true) && in_array($ub, $mass, true)) return true;
    if (in_array($ua, $vol, true)  && in_array($ub, $vol, true))  return true;

    return false;
}

/**
 * Fuzzy egyezés: "needle benne van name-ben" vagy fordítva.
 */
function isNameMatch(string $invName, string $needle): bool {
    $a = mb_strtolower(trim($invName), 'UTF-8');
    $b = mb_strtolower(trim($needle), 'UTF-8');
    if ($a === '' || $b === '') return false;

    return (mb_stripos($a, $b, 0, 'UTF-8') !== false) || (mb_stripos($b, $a, 0, 'UTF-8') !== false);
}

/**
 * Kikeresi a household inventory sorait, amik név alapján passzolnak.
 * Lehetőleg a leghamarabb lejárókból fogyasszon.
 */
function findInventoryRows(PDO $pdo, int $hid, string $needle): array {
    $needle = trim($needle);
    if ($needle === '') return [];

    $like = '%' . mb_strtolower($needle, 'UTF-8') . '%';

    $stmt = $pdo->prepare("
        SELECT id, name, quantity, unit, expires_at
        FROM inventory_items
        WHERE household_id = ?
          AND LOWER(name) LIKE ?
        ORDER BY
          CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END,
          expires_at ASC,
          id ASC
    ");
    $stmt->execute([$hid, $like]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        if (isNameMatch($r['name'] ?? '', $needle)) {
            $out[] = $r;
        }
    }
    return $out;
}

function unitCompatible(?string $invUnit, ?string $needUnit): bool {
    $iu = normalizeUnit($invUnit);
    $nu = normalizeUnit($needUnit);

    // ha nincs unit a receptben: engedjük (pl. "10")
    if ($nu === null) return true;

    // ha receptben van unit, inventory-ben nincs: ne
    if ($iu === null) return false;

    // egyezés vagy átváltható (kg<->g, l<->ml)
    return unitsConvertible($iu, $nu);
}

// -------------------- MAIN --------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recipes.php");
    exit;
}

$hid = (int)($_POST['hid'] ?? 0);
$mealId = (int)($_POST['meal_id'] ?? 0);

if ($hid <= 0 || $mealId <= 0) {
    header("Location: recipes.php");
    exit;
}

if (!userHasHouseholdAccess($pdo, $userId, $hid)) {
    header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Nincs jogosultság ehhez a háztartáshoz."));
    exit;
}

$meal = fetchRecipeDetails($mealId);
if (!$meal) {
    header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("A recept nem található."));
    exit;
}

// összes hozzávaló összeszedése (HU + EN név)
$ingredients = [];
for ($i = 1; $i <= 20; $i++) {
    $ingNameEn = trim($meal["strIngredient{$i}"] ?? '');
    $measure   = trim($meal["strMeasure{$i}"] ?? '');
    if ($ingNameEn === '') continue;

    $ingNameHu = translateToHungarian($ingNameEn);
    [$needQty, $needUnit] = parseMeasure($measure);

    $ingredients[] = [
        'en' => $ingNameEn,
        'hu' => $ingNameHu,
        'measure' => $measure,
        'need_qty' => $needQty,     // lehet null
        'need_unit' => $needUnit,   // lehet null
    ];
}

if (!$ingredients) {
    header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Nincsenek hozzávalók a receptben."));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1) ellenőrzés: mindenhez van-e elég
    $plan = [];

    foreach ($ingredients as $ing) {
        $needQty = $ing['need_qty'];
        $needUnit = $ing['need_unit'];

        // ha nincs szám, 1-nek vesszük (pl. "pinch", "to taste")
        if ($needQty === null) $needQty = 1.0;

        // találatok: HU név alapján, ha nincs akkor EN
        $rows = findInventoryRows($pdo, $hid, $ing['hu']);
        if (!$rows) $rows = findInventoryRows($pdo, $hid, $ing['en']);

        // unit szerinti szűrés
        $filtered = [];
        foreach ($rows as $r) {
            if (unitCompatible($r['unit'] ?? null, $needUnit)) {
                $filtered[] = $r;
            }
        }

        // ha unit miatt üres, de a receptben sincs unit, engedjük
        if (!$filtered && $needUnit === null) {
            $filtered = $rows;
        }

        if (!$filtered) {
            $pdo->rollBack();
            header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Hiányzik: {$ing['hu']} ({$ing['measure']})"));
            exit;
        }

        // össz mennyiség kiszámítása konvertálva
        [$needBase, $needBaseUnit] = toBaseUnit((float)$needQty, $needUnit);

        $totalBase = 0.0;
        foreach ($filtered as $r) {
            [$invBase, $invBaseUnit] = toBaseUnit((float)($r['quantity'] ?? 0), $r['unit'] ?? null);

            // csak akkor számoljuk, ha ugyanabba a "base unit"-ba esik
            if ($invBaseUnit === $needBaseUnit) {
                $totalBase += $invBase;
            }
        }

        // ha nem konvertálható egység (pl tsp/tbsp/cup), akkor marad a régi logika (azonos unit esetén)
        // toBaseUnit ilyenkor baseUnit = 'tsp'/'tbsp' stb, így a fenti összeadás is működik, ha inventory ugyanazzal tárol.
        if ($totalBase + 1e-9 < (float)$needBase) {
            $pdo->rollBack();
            header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Nincs elég: {$ing['hu']} ({$ing['measure']})"));
            exit;
        }

        $plan[] = [
            'ing' => $ing,
            'need_base' => (float)$needBase,
            'need_base_unit' => $needBaseUnit,
            'rows' => $filtered,
        ];
    }

    // 2) levonás (konverzióval)
    foreach ($plan as $p) {
        $needBase = (float)$p['need_base'];
        $needBaseUnit = $p['need_base_unit'];

        foreach ($p['rows'] as $r) {
            if ($needBase <= 1e-9) break;

            $id = (int)$r['id'];
            $invQty = (float)$r['quantity'];
            $invUnit = $r['unit'] ?? null;

            [$invBase, $invBaseUnit] = toBaseUnit($invQty, $invUnit);

            if ($invBaseUnit !== $needBaseUnit) {
                continue;
            }

            $takeBase = min($invBase, $needBase);
            $newInvBase = $invBase - $takeBase;

            // visszaszámolás az inventory eredeti unitjára
            $iu = normalizeUnit($invUnit);
            $newInvQty = $invQty;

            if ($invBaseUnit === 'g') {
                $newInvQty = ($iu === 'kg') ? ($newInvBase / 1000.0) : $newInvBase;
            } elseif ($invBaseUnit === 'ml') {
                $newInvQty = ($iu === 'l') ? ($newInvBase / 1000.0) : $newInvBase;
            } else {
                // pcs vagy tsp/tbsp/cup stb.
                $newInvQty = $newInvBase;
            }

            if ($newInvQty <= 1e-6) {
                $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ? AND household_id = ?");
                $stmt->execute([$id, $hid]);
            } else {
                $stmt = $pdo->prepare("UPDATE inventory_items SET quantity = ? WHERE id = ? AND household_id = ?");
                $stmt->execute([$newInvQty, $id, $hid]);
            }

            $needBase -= $takeBase;
        }

        if ($needBase > 1e-6) {
            $pdo->rollBack();
            header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Nem sikerült mindent levonni: {$p['ing']['hu']}"));
            exit;
        }
    }

    $pdo->commit();
    header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=ok");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: recipe_details.php?id={$mealId}&hid={$hid}&cook=err&msg=" . urlencode("Hiba: " . $e->getMessage()));
    exit;
}
