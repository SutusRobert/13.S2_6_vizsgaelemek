    <?php
    session_start();
    require 'config.php';

    // PDO error mode (ha config.php már beállítja, ez maradhat akkor is)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $userId = (int)$_SESSION['user_id'];

    $errors = [];
    $success = '';

    // Flash üzenetek (POST-Redirect-GET)
    if (isset($_SESSION['flash_success'])) {
        $success = (string)$_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_errors']) && is_array($_SESSION['flash_errors'])) {
        $errors = array_merge($errors, $_SESSION['flash_errors']);
        unset($_SESSION['flash_errors']);
    }


    /* ================================
    Helper: POST getter
    ================================ */
    function post(string $key, $default = '') {
        if (!isset($_POST[$key])) return $default;
        $v = $_POST[$key];
        if (is_string($v)) return trim($v);
        return $v; // array-t is visszaad
    }

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
            if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }
    

    function guessLocationForItem(string $name): string {
        $n = mb_strtolower(trim($name), 'UTF-8');

        $freezer = [
            'fagyaszt', 'mirelit', 'jég', 'jeg',
            'fagyasztott', 'nugget', 'hasáb', 'hasab',
            'pizza', 'spenót', 'spenot', 'borsó', 'borso'
        ];

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

    /**
     * Measure parser
     */
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

            // csak az első szó (ne legyen "thinly sliced" jellegű unit)
            if ($unit !== '') {
                $unit = preg_split('/\s+/u', $unit)[0];
            }

            // ha unit valójában szöveg, ne unitként kezeljük
            if ($unit !== '' && preg_match('/^(chopped|slice|sliced|minced|pinch|handful|to|taste)$/iu', $unit)) {
                return [1.0, null];
            }

            return [$qty, $unit !== '' ? $unit : null];
        }

        return [1.0, null];
    }
    /**
 * Recept mérték -> belső alapmérték (g/ml/pcs)
 * VISSZA: [baseQty, baseUnit]
 */
function measureToBase(string $name, float $qty, ?string $unit): array {
    $u = $unit ? normalizeForMatch($unit) : '';
    if ($u === '' || $u === null) return [$qty, 'pcs'];

    // tömeg
    if ($u === 'kg' || $u === 'kgs') return [$qty * 1000.0, 'g'];
    if ($u === 'g' || $u === 'gr' || $u === 'gram') return [$qty, 'g'];

    // térfogat
    if ($u === 'l' || $u === 'liter') return [$qty * 1000.0, 'ml'];
    if ($u === 'ml') return [$qty, 'ml'];

    // kanalak/csésze közelítések (ml)
    if ($u === 'tbsp') return [$qty * 15.0, 'ml'];
    if ($u === 'tsp')  return [$qty * 5.0, 'ml'];
    if ($u === 'cup')  return [$qty * 240.0, 'ml'];

    // darab
    if (in_array($u, ['pcs','pc','db','piece','clove'], true)) return [$qty, 'pcs'];

    // fallback
    return [$qty, 'pcs'];
}

/**
 * Bolti kiszerelés + base készlet (g/ml/db)
 * VISSZA:
 *  [displayQty, displayUnitLabel, baseBoughtQty, baseUnit, packSize, packUnit]
 */
function toStorePackWithBase(string $name, float $recipeQty, ?string $recipeUnit): array {
    $n = normalizeForMatch($name);
    [$needBaseQty, $needBaseUnit] = measureToBase($name, $recipeQty, $recipeUnit);

    // ====== GRAMM ======
    if ($needBaseUnit === 'g') {

        // fűszerek (50 g)
        if (preg_match('/(kurkuma|bors|paprika|koriander|komeny|garam|fuszer|oregano|bazsalikom|chili)/u', $n)) {
            $pack = 50.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            return [$packs, "csomag (50 g)", $packs * $pack, 'g', 50.0, 'g'];
        }

        // tészta (500 g)
        if (preg_match('/(teszta|spagetti|pasta)/u', $n)) {
            $pack = 500.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            return [$packs, "csomag (500 g)", $packs * $pack, 'g', 500.0, 'g'];
        }

        // rizs/liszt/cukor/só (1 kg)
        if (preg_match('/(rizs|liszt|cukor|so)/u', $n)) {
            $pack = 1000.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            return [$packs, "csomag (1 kg)", $packs * $pack, 'g', 1000.0, 'g'];
        }

        // hús (500 g)
        if (preg_match('/(csirke|pulyka|marha|sertes|hus|daralt)/u', $n)) {
            $pack = 500.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            return [$packs, "tálca (500 g)", $packs * $pack, 'g', 500.0, 'g'];
        }

        // default (500 g)
        $pack = 500.0;
        $packs = (int)max(1, ceil($needBaseQty / $pack));
        return [$packs, "csomag (500 g)", $packs * $pack, 'g', 500.0, 'g'];
    }

    // ====== ML ======
    if ($needBaseUnit === 'ml') {

        // tej/olaj/ecet (1 liter)
        if (preg_match('/(tej|milk|olaj|oil|ecet|vinegar)/u', $n)) {
            $pack = 1000.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            $label = (str_contains($n,'olaj') || str_contains($n,'oil') || str_contains($n,'ecet') || str_contains($n,'vinegar'))
                ? "palack (1 l)" : "doboz (1 l)";
            return [$packs, $label, $packs * $pack, 'ml', 1000.0, 'ml'];
        }

        // tejföl/tejszín/joghurt (0.5 liter – közelítés)
        if (preg_match('/(tejfol|tejszin|joghurt|cream|yogurt)/u', $n)) {
            $pack = 500.0;
            $packs = (int)max(1, ceil($needBaseQty / $pack));
            return [$packs, "doboz (0.5 l)", $packs * $pack, 'ml', 500.0, 'ml'];
        }

        // default 1 liter
        $pack = 1000.0;
        $packs = (int)max(1, ceil($needBaseQty / $pack));
        return [$packs, "palack (1 l)", $packs * $pack, 'ml', 1000.0, 'ml'];
    }

    // ====== DB ======
    $packs = (int)max(1, ceil($needBaseQty));
    return [$packs, "db", (float)$packs, 'pcs', 1.0, 'pcs'];
}



    function normalizeForMatch(string $s): string {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = str_replace(['á','é','í','ó','ö','ő','ú','ü','ű'], ['a','e','i','o','o','o','u','u','u'], $s);
        return $s;
    }

    /**
     * Bolti kiszerelés kalkuláció
     */
    function toStorePack(string $name, float $recipeQty, ?string $recipeUnit): array {
        $n = normalizeForMatch($name);
        $u = $recipeUnit ? normalizeForMatch($recipeUnit) : null;

        if (str_contains($n, 'tej') || str_contains($n, 'milk')) {
            $needMl = null;
            if ($u === 'ml') $needMl = $recipeQty;
            if ($u === 'l')  $needMl = $recipeQty * 1000.0;

            $packs = 1;
            if ($needMl !== null) $packs = (int)ceil($needMl / 1000.0);
            return [$packs, 'l'];
        }

        if (str_contains($n, 'joghurt') || str_contains($n, 'yogurt')) {
            return [1, 'pohár'];
        }

        if (str_contains($n, 'tejfol') || str_contains($n, 'tejföl') || str_contains($n, 'sour')) {
            return [1, 'doboz'];
        }

        if (str_contains($n, 'tejszin') || str_contains($n, 'tejszín') || str_contains($n, 'cream')) {
            return [1, 'doboz'];
        }

        if (str_contains($n, 'olaj') || str_contains($n, 'oil')) {
            return [1, 'üveg'];
        }

        if (str_contains($n, 'ecet') || str_contains($n, 'vinegar')) {
            return [1, 'üveg'];
        }

        if (str_contains($n, 'tojas') || str_contains($n, 'tojás') || str_contains($n, 'egg')) {
            if ($u === 'pcs' || $u === 'db') return [max(1, (int)ceil($recipeQty)), 'db'];
            return [6, 'db'];
        }

        if (str_contains($n, 'csirke') || str_contains($n, 'pulyka') || str_contains($n, 'marha') || str_contains($n, 'sertes') || str_contains($n, 'sertés')) {
            $needG = null;
            if ($u === 'g')  $needG = $recipeQty;
            if ($u === 'kg') $needG = $recipeQty * 1000.0;

            if ($needG !== null) {
                $packs = (int)ceil($needG / 500.0);
                return [max(1, $packs), 'csomag'];
            }
            return [1, 'csomag'];
        }

        // default
        return [1, 'db'];
    }

    /* (opcionális) laza kereső – itt most nem használjuk, de maradhat */
    function findInventoryByNameLoose(PDO $pdo, int $householdId, string $needle): ?array {
        $needle = mb_strtolower(trim($needle), 'UTF-8');
        if ($needle === '') return null;

        $stmt = $pdo->prepare("
            SELECT *
            FROM inventory_items
            WHERE household_id = ?
            AND LOWER(TRIM(name)) LIKE ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$householdId, '%' . $needle . '%']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /* ================================
    POST műveletek
    ================================ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $action = $_POST['action'] ?? '';
        $action = is_string($action) ? trim($action) : '';

        /* 0) Hiányzók felvétele (API receptről jön) */
        if ($action === 'add_missing_api') {
            $recipeTitle = post('recipe_title', '');

            $invNames = invNamesForHousehold($pdo, $householdId);

            $insert = $pdo->prepare("
                INSERT INTO shopping_list_items
                (household_id, name, quantity, unit, note, location, created_by, base_quantity, base_unit, pack_size, pack_unit)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $added = 0;

            $missingItems = $_POST['missing_item'] ?? null;
            if (is_array($missingItems)) {
                foreach ($missingItems as $it) {
                    if (!is_array($it)) continue;
                    if (!isset($it['add'])) continue;

                    $nm = trim((string)($it['name'] ?? ''));
                    $measure = trim((string)($it['measure'] ?? ''));

                    if ($nm === '') continue;
                    if (invContains($invNames, $nm)) continue;

                    [$rq, $ru] = parseMeasureToQtyUnit($measure);
                    [$dispQty, $dispUnit, $baseBoughtQty, $baseUnit, $packSize, $packUnit] =
    toStorePackWithBase($nm, (float)$rq, $ru);

                    $noteParts = [];
                    if ($recipeTitle !== '') $noteParts[] = "Recept: " . $recipeTitle;
                    if ($measure !== '') $noteParts[] = "Mérték: " . $measure;
                    $note = $noteParts ? implode(" | ", $noteParts) : null;

                    $loc = guessLocationForItem($nm);

                    $insert->execute([
                    $householdId,
                    $nm,
                    1,
                    null,
                    $note,
                    $loc,
                    $userId,
                    1,        // base_quantity
                    'pcs',    // base_unit
                    null,     // pack_size
                    null      // pack_unit
                    ]);

                    $added++;
                }
                $success = $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Nincs hozzáadandó hiányzó tétel.";
            } else {
                // régi formátum
                $names = $_POST['missing_name'] ?? [];
                if (!is_array($names)) $names = [];

                foreach ($names as $nmRaw) {
                    $nm = trim((string)$nmRaw);
                    if ($nm === '') continue;
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
        }

        /* 1) Saját recept hiányzóinak felvétele */
        if ($action === 'add_missing_for_own_recipe') {
    $recipeId = (int)post('recipe_id', '0');

    // ✅ jogosultság + cím
    $stmt = $pdo->prepare("SELECT title FROM recipes WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$recipeId, $userId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$r) {
        $errors[] = "Nincs ilyen saját recept, vagy nincs jogosultságod.";
    } else {
        // ✅ hozzávalók + mennyiség (amit te már használsz)
        $stmt = $pdo->prepare("SELECT ingredient, quantity FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id");
        $stmt->execute([$recipeId]);
        $ings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $invNames = invNamesForHousehold($pdo, $householdId);

        $insert = $pdo->prepare("
            INSERT INTO shopping_list_items
            (household_id, name, quantity, unit, note, location, created_by, base_quantity, base_unit, pack_size, pack_unit)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $added = 0;

        foreach ($ings as $row) {
            $nm = trim((string)($row['ingredient'] ?? ''));
            $measure = trim((string)($row['quantity'] ?? '')); // pl "250 g"
            if ($nm === '') continue;
            if (invContains($invNames, $nm)) continue;

            [$rq, $ru] = parseMeasureToQtyUnit($measure);

            [$dispQty, $dispUnit, $baseBoughtQty, $baseUnit, $packSize, $packUnit] =
                toStorePackWithBase($nm, (float)$rq, $ru);

            $insert->execute([
                $householdId,
                $nm,
                (float)$dispQty,
                $dispUnit !== '' ? $dispUnit : null,
                "Recept: " . $r['title'] . ($measure !== '' ? (" | Mérték: " . $measure) : ""),
                "pantry",
                $userId,
                (float)$baseBoughtQty,
                $baseUnit,
                $packSize,
                $packUnit
            ]);

            $added++;
        }

        $success = $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Minden hozzávaló megvan a raktárban.";
    }
}

        

        /* 2) Új tétel */
        if ($action === 'add') {
            $name = post('name', '');
            $quantity = str_replace(',', '.', post('quantity', '1'));
            $unit = post('unit', '');
            $note = post('note', '');
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
                SELECT id, household_id, name, quantity, unit, note, location, is_bought, base_quantity, base_unit
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
                            bought_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE NULL END,
                            bought_by = CASE WHEN ? = 1 THEN ? ELSE NULL END
                        WHERE id = ? AND household_id = ?
                    ");
                    $stmt->execute([$to, $to, $to, $userId, $id, $householdId]);

                    if ($to === 1) {
                        $baseQty  = (float)($item['base_quantity'] ?? 0);
                        $baseUnit = (string)($item['base_unit'] ?? 'pcs');
                        $name = (string)$item['name'];
                        $qty  = (float)$item['quantity'];
                        $unit = $item['unit'] !== null ? (string)$item['unit'] : null;
                        $note = $item['note'] !== null ? (string)$item['note'] : null;
                        $loc  = in_array($item['location'], ['fridge','freezer','pantry'], true) ? $item['location'] : 'pantry';

                        // keresés unit figyelembe vétellel (csak a shopping->inventory feltöltésnél)
                        if ($unit !== null && trim($unit) !== '') {
                            $find = $pdo->prepare("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                AND ((unit IS NULL AND ? IS NULL) OR unit = ?)
                                ORDER BY id DESC
                                LIMIT 1
                            ");
                            $find->execute([$householdId, $name, $loc, $unit, $unit]);
                        } else {
                            $find = $pdo->prepare("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                ORDER BY id DESC
                                LIMIT 1
                            ");
                            $find->execute([$householdId, $name, $loc]);
                        }

                        $existing = $find->fetch(PDO::FETCH_ASSOC);

                        if ($existing) {
                            $upd = $pdo->prepare("
                                UPDATE inventory_items
                                SET quantity = quantity + ?,
                                    base_quantity = base_quantity + ?,
                                    base_unit = ?
                                WHERE id = ? AND household_id = ?
                            ");
                            $upd->execute([$qty, $baseQty, $baseUnit, (int)$existing['id'], $householdId]);
                        } else {
                            $ins = $pdo->prepare("
                              INSERT INTO inventory_items
(household_id, name, category, location, quantity, unit, expires_at, note, base_quantity, base_unit)
VALUES (?, ?, NULL, ?, ?, ?, NULL, ?, ?, ?)  
                            ");
                            $ins->execute([$householdId, $name, $loc, $qty, $unit, $note, $baseQty, $baseUnit]);
                        }
                    }

                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $errors[] = "Hiba történt a megvett jelölésnél: " . $e->getMessage();
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

        /* 6) Összes tétel törlése a bevásárlólistából */
    if ($action === 'clear_all') {
        $stmt = $pdo->prepare("DELETE FROM shopping_list_items WHERE household_id = ?");
        $stmt->execute([$householdId]);
        $success = "Az összes tétel törölve.";
    }

    /* 7) Összes tétel megvétele (mindet megjelöli megvettnek + feltölti raktárba) */
    if ($action === 'buy_all') {

        // betöltjük az összes NEM megvett tételt
        $stmt = $pdo->prepare("
            SELECT id, name, quantity, unit, note, location, base_quantity, base_unit
            FROM shopping_list_items
            WHERE household_id = ? AND is_bought = 0
            ORDER BY id ASC
        ");
        $stmt->execute([$householdId]);
        $toBuy = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$toBuy) {
            $success = "Nincs megveendő tétel.";
        } else {
            $pdo->beginTransaction();
            try {
                // 1) mindet megjelöljük megvettnek
                $updShop = $pdo->prepare("
                    UPDATE shopping_list_items
                    SET is_bought = 1,
                        bought_at = CURRENT_TIMESTAMP,
                        bought_by = ?
                    WHERE id = ? AND household_id = ?
                ");

                // 2) inventory upsert (ugyanaz a logika, mint a toggle-nál)
                $findWithUnit = $pdo->prepare("
                    SELECT id
                    FROM inventory_items
                    WHERE household_id = ? AND name = ? AND location = ?
                      AND ((unit IS NULL AND ? IS NULL) OR unit = ?)
                    ORDER BY id DESC
                    LIMIT 1
                ");

                $findNoUnit = $pdo->prepare("
                    SELECT id
                    FROM inventory_items
                    WHERE household_id = ? AND name = ? AND location = ?
                    ORDER BY id DESC
                    LIMIT 1
                ");

                $updInv = $pdo->prepare("
                UPDATE inventory_items
                SET quantity = quantity + ?,
                    base_quantity = base_quantity + ?,
                    base_unit = ?
                WHERE id = ? AND household_id = ?
                ");

               $insInv = $pdo->prepare("
                INSERT INTO inventory_items
                (household_id, name, category, location, quantity, unit, expires_at, note, base_quantity, base_unit)
                VALUES (?, ?, NULL, ?, ?, ?, NULL, ?, ?, ?)
                ");

                foreach ($toBuy as $item) {
                    $id   = (int)$item['id'];
                    $name = (string)$item['name'];
                    $qty  = (float)$item['quantity'];
                    $unit = $item['unit'] !== null ? (string)$item['unit'] : null;
                    $note = $item['note'] !== null ? (string)$item['note'] : null;
                    $loc  = in_array($item['location'], ['fridge','freezer','pantry'], true) ? $item['location'] : 'pantry';

                    $updShop->execute([$userId, $id, $householdId]);

                    if ($unit !== null && trim($unit) !== '') {
                        $findWithUnit->execute([$householdId, $name, $loc, $unit, $unit]);
                        $existing = $findWithUnit->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $findNoUnit->execute([$householdId, $name, $loc]);
                        $existing = $findNoUnit->fetch(PDO::FETCH_ASSOC);
                    }

                    if ($existing) {
                        $updInv->execute([$qty, (int)$existing['id'], $householdId]);
                    } else {
                        $insInv->execute([$householdId, $name, $loc, $qty, $unit, $note]);
                    }
                }

                $pdo->commit();
                $success = "Minden tétel megvéve és felvéve a raktárba.";
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors[] = "Hiba történt az összes megvételénél: " . $e->getMessage();
            }
        }
    }

    
        // POST-Redirect-GET: hogy frissítésre ne ismételje a műveletet
        if (!empty($success)) {
            $_SESSION['flash_success'] = $success;
        }
        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
        }
        header("Location: shopping_list.php?hid=" . urlencode((string)$householdId));
        exit;
}

    /* ================================
    Lista betöltés
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

    function h(string $s): string {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    ?>
    <!DOCTYPE html>
    <html lang="hu">
    <head>
        <meta charset="UTF-8">
        <title>Bevásárlólista – MagicFridge</title>
        <link rel="stylesheet" href="/MagicFridge/assets/style.css?v=1">
        <style>
        /* Bubik tényleg háttér */
        .bubbles{
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .navbar, .dash-row { position: relative; z-index: 2; }

        /* ✅ EGYENLETES: középre igazított “sáv”, azonos bal/jobb padding,
            azonos gap a dobozok között */
        .dash-row{
            max-width: 1750px;
            margin: 0 auto;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 28px;
            padding: 18px 28px 40px; /* bal/jobb ugyanannyi */
            box-sizing: border-box;
        }

        /* bal/jobb fix széles, közép rugalmas */
        .dash-left, .dash-side{
            width: 420px;
            flex: 0 0 420px;
            min-width: 0;
        }

        .dash-mid{
            flex: 1 1 auto;
            min-width: 560px;
            max-width: 980px;
        }

        /* a main-wrapper ne “középre húzza” külön a cardot */
        .main-wrapper{ margin: 0; width: 100%; }

        /* jobb oldali box belső spacing */
        .side-card{ padding: 18px; }
        .side-stack{ display: grid; gap: 14px; }

        /* mobilon egymás alá */
        @media (max-width: 1200px){
            .dash-row{
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            max-width: 100%;
            }
            .dash-left, .dash-side{ width: min(520px, 100%); flex-basis: auto; }
            .dash-mid{ min-width: 0; max-width: 100%; }
        }
    </style>

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
    <div class="bubbles" aria-hidden="true" id="bubbles">
        <span></span><span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span>
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
                        <div class="sl-printbar">
    <button type="button" class="btn btn-secondary" onclick="window.print()">Nyomtatás</button>

    <form method="post" style="margin:0;" onsubmit="return confirm('Biztos megveszed AZ ÖSSZES tételt? Ez fel is tölti a raktárba.');">
        <input type="hidden" name="action" value="buy_all">
        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
        <button type="submit" class="btn btn-secondary">Összes megvétele</button>
    </form>

    <form method="post" style="margin:0;" onsubmit="return confirm('Biztos törlöd AZ ÖSSZES tételt a bevásárlólistából?');">
        <input type="hidden" name="action" value="clear_all">
        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
        <button type="submit" class="btn btn-secondary">Összes törlése</button>
    </form>

    <form method="post" style="margin:0;" onsubmit="return confirm('Törlöd az összes megvett tételt?');">
        <input type="hidden" name="action" value="clear_bought">
        <input type="hidden" name="hid" value="<?= (int)$householdId ?>">
        <button type="submit" class="btn btn-secondary">Megvett törlése</button>
    </form>
</div>

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
                                    — <?= h((string)$it['quantity']) ?> <?= h((string)($it['unit'] ?? '')) ?>
                                    </span>
                                    <span class="small" style="opacity:.75;"> • <?= h(locLabel($it['location'])) ?></span>
                                </div>

                                <?php if (!empty($it['note'])): ?>
                                    <div class="sl-meta"><?= h((string)$it['note']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($it['is_bought']) && !empty($it['bought_at'])): ?>
                                    <div class="sl-meta">Megvéve: <?= h((string)$it['bought_at']) ?></div>
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
    <script>
    /* Bubik random indulás + parallax */
    (() => {
    const bubbles = document.getElementById('bubbles');
    if (!bubbles) return;

    const items = Array.from(bubbles.querySelectorAll('span')).map((el, i) => {
        const dur = parseFloat(getComputedStyle(el).animationDuration) || 20;
        el.style.animationDelay = (Math.random() * dur * -1).toFixed(2) + 's';
        const speed = 0.6 + (i % 7) * 0.15;
        const depth = 8 + (i % 6) * 6;
        return { el, speed, depth };
    });

    let mx = 0, my = 0, tx = 0, ty = 0;
    const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

    window.addEventListener('mousemove', (e) => {
        const cx = window.innerWidth / 2;
        const cy = window.innerHeight / 2;
        mx = clamp((e.clientX - cx) / cx, -1, 1);
        my = clamp((e.clientY - cy) / cy, -1, 1);
    }, { passive: true });

    function tick() {
        tx += (mx - tx) * 0.06;
        ty += (my - ty) * 0.06;

        const sy = window.scrollY || 0;
        for (const it of items) {
        const px = tx * it.depth * it.speed;
        const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
        it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
        }
        requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    })();
    </script>



    </body>
    </html>
