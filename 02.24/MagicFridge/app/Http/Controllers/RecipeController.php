<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecipeController extends Controller
{
    private ?string $lastCurlError = null;

    private function ensureRecipeImagePathColumn(): bool
    {
        try {
            if (!Schema::hasTable('recipes')) return false;
            if (Schema::hasColumn('recipes', 'image_path')) return true;

            // A projektben több tábla kézzel/SQL dumpból is létrejöhetett,
            // ezért a képoszlopot futásidőben is pótoljuk, ha a migration nem futott le.
            DB::statement("ALTER TABLE recipes ADD image_path VARCHAR(255) NULL");
            return Schema::hasColumn('recipes', 'image_path');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* =========================
       Háztartás segédfüggvények
       ========================= */
    private function householdsForUser(int $userId): array
    {
        return DB::select("
            SELECT id AS household_id, name
            FROM households
            WHERE owner_id = ?
            UNION
            SELECT h.id AS household_id, h.name
            FROM household_members hm
            JOIN households h ON h.id = hm.household_id
            WHERE hm.member_id = ?
            ORDER BY household_id ASC
        ", [$userId, $userId]);
    }

    private function assertMember(int $userId, int $hid): void
    {
        // Minden recept/készlet művelet előtt ellenőrizzük, hogy a felhasználó
        // tényleg tagja-e annak a háztartásnak, amelynek a készletével dolgozik.
        $ok = DB::selectOne("
            SELECT id
            FROM household_members
            WHERE member_id = ? AND household_id = ?
            LIMIT 1
        ", [$userId, $hid]);

        if (!$ok) {
            $owner = DB::selectOne("SELECT id FROM households WHERE id = ? AND owner_id = ? LIMIT 1", [$hid, $userId]);
            if (!$owner) abort(403, 'You do not have permission for this household.');
        }
    }

    /* =========================
       TheMealDB API-kapcsolat
       ========================= */
    private function apiSearch(string $query, int $limit = 50): array
    {
        // Üres keresésnél adunk egy alap kulcsszót, hogy a receptlista ne üres
        // képernyővel induljon.
        $query = trim($query) !== '' ? trim($query) : 'chicken';
        $url = 'https://www.themealdb.com/api/json/v1/1/search.php?s=' . urlencode($query);

        $raw = $this->curlGet($url);
        if ($raw === null) {
            return ['_error' => $this->lastCurlError ?? 'Failed to connect to TheMealDB.'];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || !array_key_exists('meals', $decoded)) {
            return ['_error' => 'Invalid response from TheMealDB.'];
        }

        if ($decoded['meals'] === null) return [];

        if (!is_array($decoded['meals'])) {
            return ['_error' => 'Invalid result list from TheMealDB.'];
        }

        $meals = array_slice($decoded['meals'], 0, $limit);

        // A külső API nagy objektumából csak azt a kis, stabil adatcsomagot adjuk
        // tovább a view-nak, amire a felületnek szüksége van.
        $results = [];
        foreach ($meals as $m) {
            $titleEn = (string)($m['strMeal'] ?? '');

            $results[] = [
                'id' => (int)($m['idMeal'] ?? 0),
                'title' => $titleEn,
                'title_en' => $titleEn,
                'image' => (string)($m['strMealThumb'] ?? ''),
            ];
        }

        return $results;
    }

    private function apiDetails(int $id): ?array
    {
        $url = 'https://www.themealdb.com/api/json/v1/1/lookup.php?i=' . urlencode((string)$id);

        $raw = $this->curlGet($url);
        if ($raw === null) return null;

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !array_key_exists('meals', $decoded)) return null;
        if ($decoded['meals'] === null) return null;
        if (!isset($decoded['meals'][0]) || !is_array($decoded['meals'][0])) return null;

        return $decoded['meals'][0];
    }

    private function curlGet(string $url): ?string
    {
        $this->lastCurlError = null;

        if (!function_exists('curl_init')) {
            $this->lastCurlError = 'The PHP cURL extension is not enabled.';
            return null;
        }

        $ch = curl_init($url);
        // A wrapper egységesíti a külső API hívást: timeout, JSON elfogadás,
        // hibatárolás és HTTP státuszkód ellenőrzés egy helyen van.
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: MagicFridge/1.0',
            ],
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $errno = curl_errno($ch);
            $err   = curl_error($ch);
            $this->lastCurlError = "cURL error ($errno): $err";
            curl_close($ch);
            return null;
        }

        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) {
            $this->lastCurlError = "HTTP $code error from remote API ($url)";
            return null;
        }

        return $raw;
    }

    /* =========================
       Szöveg- és mennyiségkezelő segédek
       ========================= */
    private function normalizeForMatch(string $s): string
    {
        // Egyezéshez kisbetűs, ékezet nélküli alakot használunk, így pl. "víz"
        // és "viz" ugyanabba az összehasonlítható formába kerül.
        $s = mb_strtolower(trim($s), 'UTF-8');
        return Str::ascii($s);
    }

    private function parseMeasureToQtyUnit(string $measure): array
    {
        // TheMealDB mérték mezői szabad szövegek, ezért előbb több gyakori
        // mintára bontjuk őket: vegyes tört, tört, majd sima szám + egység.
        $m = trim((string)$measure);
        if ($m === '') return [1.0, null];

        $m = str_replace(',', '.', $m);

        if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            // Példa: "1 1/2 cup" -> 1.5 cup.
            $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
            $unit = trim((string)$mm[4]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        if (preg_match('/^\s*(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            // Példa: "1/2 tsp" -> 0.5 tsp.
            $qty = (float)$mm[1] / max(1.0, (float)$mm[2]);
            $unit = trim((string)$mm[3]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([^\d].*)?$/u', $m, $mm)) {
            $qty = (float)$mm[1];
            $unit = trim((string)($mm[2] ?? ''));
            if ($unit !== '') $unit = preg_split('/\s+/u', $unit)[0];
            if ($this->isPreparationWord($unit)) {
                // Ha az első szó elkészítési mód ("finely", "chopped"), akkor
                // a szám valójában darabszám: pl. "2 finely chopped" -> 2 pcs.
                return [$qty, 'pcs'];
            }
            // Egység nélküli számot darabnak vesszük, hogy az ellenőrzés és a
            // főzés levonása ugyanúgy értelmezze.
            return [$qty, $unit !== '' ? $unit : 'pcs'];
        }

        return [1.0, null];
    }

    private function isPreparationWord(?string $word): bool
    {
        // Ezek a szavak nem mértékegységek, hanem az alapanyag feldolgozási módjai.
        if ($word === null) return false;

        $w = $this->normalizeForMatch($word);

        return in_array($w, [
            'chopped', 'finely', 'thinly', 'roughly', 'sliced', 'slice',
            'minced', 'diced', 'cubed', 'grated', 'crushed', 'peeled',
            'fresh', 'large', 'small', 'medium',
        ], true);
    }

    private function isAlwaysAvailableIngredient(string $name): bool
    {
        // A víz nem készletfüggő alapanyag: ne kerüljön bevásárlólistára,
        // ne legyen hiányzó, és főzéskor se vonjuk le.
        $n = $this->normalizeForMatch($name);

        return in_array($n, ['water', 'viz'], true);
    }

    private function canonicalUnit(?string $unit): ?string
    {
        if ($unit === null) return null;

        // A különböző API/űrlap egységneveket közös belső alakra fordítjuk.
        $u = $this->normalizeForMatch($unit);
        $u = rtrim($u, '.');

        $map = [
            'grams' => 'g', 'gram' => 'g',
            'kilograms' => 'kg', 'kilogram' => 'kg', 'kgs' => 'kg',
            'milliliters' => 'ml', 'milliliter' => 'ml',
            'liters' => 'l', 'liter' => 'l',
            'tablespoon' => 'tbsp', 'tablespoons' => 'tbsp', 'tblsp' => 'tbsp', 'tbs' => 'tbsp',
            'teaspoon' => 'tsp', 'teaspoons' => 'tsp',
            'cups' => 'cup',
            'ounces' => 'oz', 'ounce' => 'oz',
            'pounds' => 'lb', 'pound' => 'lb', 'lbs' => 'lb',
            'pcs' => 'db', 'piece' => 'db', 'pieces' => 'db',
            'clove' => 'db', 'cloves' => 'db',
            'large' => 'db', 'small' => 'db',
        ];

        return $map[$u] ?? $u;
    }

    private function ingredientDensityGPerMl(string $name): ?float
    {
        // Egyes hozzávalóknál a recept térfogatot ad (cup/tsp), a készlet pedig
        // grammot tárol. A közelítő sűrűség ezt teszi összehasonlíthatóvá.
        $n = $this->normalizeForMatch($name);

        if (str_contains($n, 'honey') || str_contains($n, 'mez')) return 1.4;
        if (str_contains($n, 'sugar') || str_contains($n, 'cukor')) return 0.85;
        if (str_contains($n, 'salt') || str_contains($n, 'so')) return 1.2;
        if (str_contains($n, 'flour') || str_contains($n, 'liszt')) return 0.53;
        if (str_contains($n, 'butter') || str_contains($n, 'vaj')) return 0.96;
        if (str_contains($n, 'rice') || str_contains($n, 'rizs')) return 0.85;
        if (str_contains($n, 'yogurt') || str_contains($n, 'joghurt')) return 1.03;
        if (
            str_contains($n, 'cumin') || str_contains($n, 'coriander') ||
            str_contains($n, 'turmeric') || str_contains($n, 'paprika') ||
            str_contains($n, 'pepper') || str_contains($n, 'chilli') ||
            str_contains($n, 'chili') || str_contains($n, 'cinnamon') ||
            str_contains($n, 'masala') || str_contains($n, 'spice') ||
            str_contains($n, 'seeds')
        ) return 0.5;

        return null;
    }

    private function ingredientPieceWeightG(string $name): ?float
    {
        // Ha a recept darabszámot ad, de a készlet grammot tárol,
        // néhány gyakori alapanyagnál átlagos darabsúllyal hasonlítunk.
        $n = $this->normalizeForMatch($name);

        if (str_contains($n, 'drumstick')) return 125.0;
        if (str_contains($n, 'chicken breast')) return 200.0;
        if (str_contains($n, 'chicken thigh')) return 150.0;
        if (str_contains($n, 'chicken wing')) return 75.0;
        if (str_contains($n, 'egg') || str_contains($n, 'tojas')) return 50.0;

        return null;
    }

    private function recipeAmountToBase(string $name, float $qty, ?string $unit): array
    {
        // Minden mértéket alapegységre hozunk: tömeg -> g, térfogat -> ml,
        // darab -> db. Így később már azonos alapegységeket hasonlítunk.
        $u = $this->canonicalUnit($unit);
        if ($u === null || $u === '') return [$qty, null];

        if ($u === 'kg') return [$qty * 1000.0, 'g'];
        if ($u === 'g') return [$qty, 'g'];
        if ($u === 'l') return [$qty * 1000.0, 'ml'];
        if ($u === 'ml') return [$qty, 'ml'];
        if ($u === 'oz') return [$qty * 28.35, 'g'];
        if ($u === 'lb') return [$qty * 453.59, 'g'];
        if ($u === 'tsp') return [$qty * 5.0, 'ml'];
        if ($u === 'tbsp') return [$qty * 15.0, 'ml'];
        if ($u === 'cup') return [$qty * 240.0, 'ml'];
        if ($u === 'db') return [$qty, 'db'];

        return [$qty, $u];
    }

    private function comparableAmount(string $name, float $qty, ?string $unit): array
    {
        [$baseQty, $baseUnit] = $this->recipeAmountToBase($name, $qty, $unit);
        $density = $this->ingredientDensityGPerMl($name);

        // Ha a recept térfogatot ad, de az alapanyaghoz van sűrűségünk,
        // grammra váltunk, mert sok inventory tétel grammos egységben van.
        if ($baseUnit === 'ml' && $density !== null) {
            return [$baseQty * $density, 'g'];
        }

        $pieceWeight = $this->ingredientPieceWeightG($name);
        if ($baseUnit === 'db' && $pieceWeight !== null) {
            return [$baseQty * $pieceWeight, 'g'];
        }

        return [$baseQty, $baseUnit];
    }

    private function hasEnoughIngredient(int $hid, string $name, float $needQty, ?string $needUnit): bool
    {
        if ($this->isAlwaysAvailableIngredient($name)) return true;

        $rows = $this->inventoryRowsForIngredient($hid, $name);
        if (empty($rows)) return false;

        [$requiredQty, $requiredUnit] = $this->comparableAmount($name, $needQty, $needUnit);
        if ($requiredUnit === null || $requiredUnit === '') return true;

        // Több azonos alapanyag-sor összeadódhat, például két félig megmaradt
        // csomag rizs együtt elég lehet egy recepthez.
        $availableQty = 0.0;
        $sawComparable = false;

        foreach ($rows as $row) {
            [$rowQty, $rowUnit] = $this->comparableAmount($name, (float)$row->quantity, $row->unit ?? null);

            if ($rowUnit === null && $requiredUnit === 'db') {
                // Régi vagy kézzel felvitt darabos tételeknél előfordulhat hiányzó unit.
                $rowUnit = 'db';
            }

            if ($rowUnit !== $requiredUnit) continue;

            $availableQty += $rowQty;
            $sawComparable = true;
        }

        if (!$sawComparable) return false;

        return $availableQty + 0.00001 >= $requiredQty;
    }

    private function storePackForIngredient(string $name, float $recipeQty, ?string $recipeUnit): array
    {
        // Hiányzó alapanyagnál nem a recept pontos mennyiségét, hanem reális
        // bolti csomagméretet teszünk a bevásárlólistára.
        $n = $this->normalizeForMatch($name);
        [$needQty, $needUnit] = $this->recipeAmountToBase($name, $recipeQty, $recipeUnit);

        $density = $this->ingredientDensityGPerMl($name);
        if ($needUnit === 'ml' && $density !== null) {
            // Ha a recept térfogatot ad, de a terméket jellemzően tömegben vesszük,
            // előbb átváltjuk grammra, majd arra kerekítünk csomagméretet.
            $needQty *= $density;
            $needUnit = 'g';
        }

        $pieceWeight = $this->ingredientPieceWeightG($name);
        if ($needUnit === 'db' && $pieceWeight !== null) {
            $needQty *= $pieceWeight;
            $needUnit = 'g';
        }

        if (str_contains($n, 'honey') || str_contains($n, 'mez')) return [max(500.0, ceil(max($needQty, 1.0) / 500.0) * 500.0), 'g'];
        if (str_contains($n, 'salt') || str_contains($n, 'so')) return [max(500.0, ceil(max($needQty, 1.0) / 500.0) * 500.0), 'g'];
        if (str_contains($n, 'sugar') || str_contains($n, 'cukor')) return [max(1000.0, ceil(max($needQty, 1.0) / 1000.0) * 1000.0), 'g'];
        if (str_contains($n, 'flour') || str_contains($n, 'liszt')) return [max(1000.0, ceil(max($needQty, 1.0) / 1000.0) * 1000.0), 'g'];
        if (str_contains($n, 'rice') || str_contains($n, 'rizs') || str_contains($n, 'pasta')) return [max(500.0, ceil(max($needQty, 1.0) / 500.0) * 500.0), 'g'];
        if (str_contains($n, 'paprika') || str_contains($n, 'pepper') || str_contains($n, 'cumin') || str_contains($n, 'turmeric') || str_contains($n, 'cinnamon')) return [max(50.0, ceil(max($needQty, 1.0) / 50.0) * 50.0), 'g'];
        if (str_contains($n, 'oil') || str_contains($n, 'olaj') || str_contains($n, 'vinegar') || str_contains($n, 'ecet')) return [max(1000.0, ceil(max($needQty, 1.0) / 1000.0) * 1000.0), 'ml'];
        if (str_contains($n, 'milk') || str_contains($n, 'tej')) return [max(1000.0, ceil(max($needQty, 1.0) / 1000.0) * 1000.0), 'ml'];
        if (str_contains($n, 'cream') || str_contains($n, 'tejszin')) return [max(200.0, ceil(max($needQty, 1.0) / 200.0) * 200.0), 'ml'];
        if (str_contains($n, 'yogurt')) return [max(150.0, ceil(max($needQty, 1.0) / 150.0) * 150.0), 'g'];

        if (str_contains($n, 'egg')) {
            return [max(6.0, ceil($needQty > 0 ? $needQty : 1.0)), 'pcs'];
        }

        if (str_contains($n, 'chicken') || str_contains($n, 'beef') || str_contains($n, 'pork') || str_contains($n, 'fish')) {
            $packs = ($needUnit === 'g' && $needQty > 0) ? ceil($needQty / 500.0) : 1.0;
            return [max(1.0, $packs) * 500.0, 'g'];
        }

        if ($needUnit === 'g') return [max(100.0, ceil(max($needQty, 1.0) / 100.0) * 100.0), 'g'];
        if ($needUnit === 'ml') return [max(250.0, ceil(max($needQty, 1.0) / 250.0) * 250.0), 'ml'];
        if ($needUnit === 'db') return [max(1.0, ceil($needQty)), 'pcs'];

        return [1.0, 'pcs'];
    }

    private function convertQty(float $qty, ?string $fromUnit, ?string $toUnit, string $name = ''): array
    {
        // Főzéskor a receptben szereplő mennyiséget az inventory sor egységébe
        // váltjuk, hogy pontosan annyit tudjunk levonni abból a sorból.
        $fu = $this->canonicalUnit($fromUnit);
        $tu = $this->canonicalUnit($toUnit);

        if ($tu === null || $tu === '') return [$qty, true];
        if ($fu === null || $fu === '') return [$qty, true];

        if ($fu === $tu) return [$qty, true];

        if ($fu === 'g' && $tu === 'kg') return [$qty / 1000.0, true];
        if ($fu === 'kg' && $tu === 'g') return [$qty * 1000.0, true];

        if ($fu === 'ml' && $tu === 'l') return [$qty / 1000.0, true];
        if ($fu === 'l' && $tu === 'ml') return [$qty * 1000.0, true];

        [$baseQty, $baseUnit] = $this->recipeAmountToBase($name, $qty, $fu);
        $density = $this->ingredientDensityGPerMl($name);

        if ($baseUnit === 'ml' && ($tu === 'ml' || $tu === 'l')) {
            return $this->convertQty($baseQty, 'ml', $tu, $name);
        }

        if ($baseUnit === 'ml' && $density !== null && ($tu === 'g' || $tu === 'kg')) {
            // Térfogat -> tömeg átváltás sűrűséggel, majd szükség esetén g/kg váltás.
            return $this->convertQty($baseQty * $density, 'g', $tu, $name);
        }

        if ($baseUnit === 'g' && ($tu === 'g' || $tu === 'kg')) {
            return $this->convertQty($baseQty, 'g', $tu, $name);
        }

        $pieceWeight = $this->ingredientPieceWeightG($name);
        if ($baseUnit === 'db' && $pieceWeight !== null && ($tu === 'g' || $tu === 'kg')) {
            return $this->convertQty($baseQty * $pieceWeight, 'g', $tu, $name);
        }

        if (($fu === 'g' || $fu === 'kg') && $tu === 'db' && $pieceWeight !== null) {
            [$grams, $ok] = $this->convertQty($qty, $fu, 'g', $name);
            if ($ok) return [$grams / $pieceWeight, true];
        }

        return [$qty, false];
    }

    private function householdInventoryNameList(int $hid): array
    {
        // Gyors névlista régi hiány-ellenőrző útvonalakhoz.
        $rows = DB::select("
            SELECT LOWER(TRIM(name)) AS n
            FROM inventory_items
            WHERE household_id = ?
            GROUP BY LOWER(TRIM(name))
        ", [$hid]);

        $list = [];
        foreach ($rows as $r) $list[] = (string)$r->n;
        return $list;
    }

    private function invContains(array $invNames, string $needle): bool
    {
        // Részleges egyezés is elég: "tomato" és "tomato puree" egymásra talál.
        $needle = trim((string)$needle);
        if ($needle === '') return false;

        $needleN = $this->normalizeForMatch($needle);

        foreach ($invNames as $n) {
            $n = (string)$n;
            $nN = $this->normalizeForMatch($n);
            if ($nN === '' || $needleN === '') continue;

            if (mb_stripos($nN, $needleN, 0, 'UTF-8') !== false || mb_stripos($needleN, $nN, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    private function guessLocationForItem(string $name): string
    {
        // Egyszerű kulcsszavas heurisztika, hogy a bevásárolt tétel alapból
        // a legvalószínűbb tárolási helyre kerüljön.
        $n = $this->normalizeForMatch($name);

        $freezer = [
            'frozen', 'ice cream', 'icecream',
            'nugget', 'fries', 'fish fingers',
            'pizza',
        ];

        $fridge = [
            'milk', 'yogurt', 'cheese', 'cream', 'sour cream', 'butter', 'margarine',
            'egg', 'eggs',
            'chicken', 'turkey', 'beef', 'pork', 'fish',
            'ham', 'sausage', 'bacon',
            'lettuce', 'spinach', 'tomato', 'cucumber', 'pepper',
            'strawberry', 'berry',
        ];

        foreach ($freezer as $k) if (str_contains($n, $k)) return 'freezer';
        foreach ($fridge as $k) if (str_contains($n, $k)) return 'fridge';

        return 'pantry';
    }

    private function ingredientSearchTerms(string $ingredientEn): array
    {
        // Egyes alapanyagoknál a recept konkrét típust kér, de a készletben
        // általánosabb név szerepelhet. Ilyenkor kiegészítő keresőszavakat adunk.
        $needle = $this->normalizeForMatch($ingredientEn);
        $terms = [$needle];

        if (str_contains($needle, 'rice') || str_contains($needle, 'rizs')) {
            $terms[] = 'rice';
            $terms[] = 'rizs';
        }

        return array_values(array_unique(array_filter($terms, fn($term) => trim($term) !== '')));
    }

    private function inventoryRowsForIngredient(int $hid, string $ingredientEn): array
    {
        // Az alapanyaghoz illő készletsorokat lejárati sorrendben adjuk vissza,
        // hogy főzéskor először a leghamarabb lejáró tételből vonjunk le.
        $ingredientEn = trim((string)$ingredientEn);
        if ($ingredientEn === '') return [];

        $likes = [];
        $params = [$hid];

        foreach ($this->ingredientSearchTerms($ingredientEn) as $term) {
            $likes[] = "LOWER(name) LIKE ?";
            $params[] = '%'.$term.'%';
        }

        if (empty($likes)) return [];

        $sql = "
            SELECT id, name, quantity, unit, expires_at
            FROM inventory_items
            WHERE household_id = ?
              AND (".implode(" OR ", $likes).")
            ORDER BY (expires_at IS NULL) ASC, expires_at ASC, id ASC
        ";

        return DB::select($sql, $params);
    }

    private function parseOwnIngredientLine(string $line): array
    {
        $raw = trim($line);
        $name = $raw;
        $measure = '';

        // A saját receptek hozzávalói "Név (2 cup)" formában vannak mentve,
        // ezért a zárójeles részt visszabontjuk receptmértékké.
        if (preg_match('/^(.*?)\s*\((.*?)\)\s*$/u', $raw, $m)) {
            $name = trim((string)$m[1]);
            $measure = trim((string)$m[2]);
        }

        [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);

        return [
            'raw' => $raw,
            'name' => $name !== '' ? $name : $raw,
            'measure' => $measure,
            'qty' => (float)($qty > 0 ? $qty : 1.0),
            'unit' => $unit,
        ];
    }

    private function ownRecipeIngredientsWithStock(int $hid, array $rows): array
    {
        $checked = [];
        $missingCount = 0;

        foreach ($rows as $idx => $row) {
            $parsed = $this->parseOwnIngredientLine((string)($row->ingredient ?? ''));
            if (trim($parsed['name']) === '') continue;

            $has = $this->hasEnoughIngredient($hid, $parsed['name'], (float)$parsed['qty'], $parsed['unit']);
            if (!$has) $missingCount++;

            $checked[] = [
                'idx' => $idx,
                'raw' => $parsed['raw'],
                'name' => $parsed['name'],
                'measure' => $parsed['measure'],
                'qty' => $parsed['qty'],
                'unit' => $parsed['unit'],
                'has' => $has,
            ];
        }

        return [$checked, $missingCount];
    }

    /* =========================
       Oldalak és receptműveletek
       ========================= */
    public function index(Request $request)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Join a household first.']);
        }

        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $q = trim((string)$request->query('q', 'chicken'));

        $api = $this->apiSearch($q, 50);

        $ownImageSelect = '';
        try {
            $ownImageSelect = $this->ensureRecipeImagePathColumn() ? ', r.image_path' : '';
        } catch (\Throwable $e) {
            $ownImageSelect = '';
        }

        $own = DB::select("
            SELECT r.id, r.title, r.created_at{$ownImageSelect}
            FROM recipes r
            WHERE r.user_id = ?
            ORDER BY r.id DESC
        ", [$userId]);

        return view('recipes.index', [
            'hid' => $hid,
            'households' => array_map(fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name], $households),
            'q' => $q,
            'api' => $api,
            'own' => $own,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Join a household first.']);
        }

        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $meal = $this->apiDetails($id);
        if (!$meal) {
            return redirect()->route('recipes.index', ['hid' => $hid])->withErrors(['Recipe not found.']);
        }

        // TheMealDB fixen strIngredient1..20 és strMeasure1..20 mezőket ad.
        // Ezeket előbb egységes tömbbé alakítjuk, hogy a view és az ellenőrzés egyszerűbb legyen.
        $ingredients = [];
        for ($i = 1; $i <= 20; $i++) {
            $ingNameEn = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure   = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingNameEn === '') continue;

            $ingredients[] = [
                'name_en' => $ingNameEn,
                'name_hu' => $ingNameEn,
                'measure' => $measure,
            ];
        }

        $missingCount = 0;
        $ingredientsChecked = [];
        foreach ($ingredients as $idx => $ing) {
            $nameEn = (string)$ing['name_en'];
            $nameHu = (string)$ing['name_hu'];

            [$needQty, $needUnit] = $this->parseMeasureToQtyUnit((string)$ing['measure']);
            $has = $this->hasEnoughIngredient($hid, $nameEn, (float)($needQty > 0 ? $needQty : 1.0), $needUnit);

            // A view már kész, eldöntött állapotot kap: minden sorhoz megmondjuk,
            // hogy elérhető-e, és közben számoljuk a hiányzó tételeket.
            if (!$has) $missingCount++;

            $ingredientsChecked[] = [
                'idx' => $idx,
                'name' => $nameEn,
                'name_hu' => $nameHu,
                'measure' => (string)$ing['measure'],
                'has' => $has,
            ];
        }

        $cook = (string)$request->query('cook', '');
        $msg  = (string)$request->query('msg', '');

        $titleHu = (string)($meal['strMeal'] ?? 'Recipe');
        $instructionsEn = (string)($meal['strInstructions'] ?? '');
        $instructionsHu = $instructionsEn;
        $instructionText = trim(preg_replace('/\s+/u', ' ', $instructionsHu) ?? '');
        $instructionSentenceCount = preg_match_all('/[.!?](\s|$)/u', $instructionText);
        // Ha a külső recept túl rövid instrukciót ad, a view kiegészítő checklistet jelenít meg.
        $needsMoreInstructions = mb_strlen($instructionText, 'UTF-8') < 450 || $instructionSentenceCount < 4;

        return view('recipes.show', [
            'hid' => $hid,
            'households' => array_map(fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name], $households),
            'mealId' => $id,
            'title' => $titleHu,
            'image' => (string)($meal['strMealThumb'] ?? ''),
            'instructions' => $instructionsHu,
            'sourceUrl' => trim((string)($meal['strSource'] ?? '')),
            'youtubeUrl' => trim((string)($meal['strYoutube'] ?? '')),
            'needsMoreInstructions' => $needsMoreInstructions,
            'ingredients' => $ingredientsChecked,
            'missingCount' => $missingCount,
            'cook' => $cook,
            'msg' => $msg,
        ]);
    }

    public function addMissingToShopping(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'hid' => 'required|integer',
        ]);

        $hid = (int) $request->input('hid');
        $this->assertMember($userId, $hid);

        $meal = $this->apiDetails($id);
        if (!$meal) {
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid])
                ->withErrors(['Recipe not found.']);
        }

        $recipeTitleHu = (string)($meal['strMeal'] ?? 'Recipe');
        $missing = [];
        // Újraszámoljuk a hiányt szerveroldalon, nem bízunk a böngészőből érkező checkbox állapotban.
        for ($i = 1; $i <= 20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;

            [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);
            $has = $this->hasEnoughIngredient($hid, $ingName, (float)($qty > 0 ? $qty : 1.0), $unit);
            if ($has) continue;

            // A bevásárlólistára bolti csomagméret kerül, a note megőrzi az eredeti receptmértéket.
            [$storeQty, $storeUnit] = $this->storePackForIngredient($ingName, (float)($qty > 0 ? $qty : 1.0), $unit);

            $missing[] = [
                'name' => $ingName,
                'qty'  => (float)$storeQty,
                'unit' => $storeUnit,
                'measure' => $measure,
            ];
        }

        if (empty($missing)) {
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid])
                ->with('success', 'No missing ingredients - everything is in stock.');
        }

        foreach ($missing as $m) {
            DB::table('shopping_list_items')->insert([
                'household_id' => $hid,
                'name' => $m['name'],
                'quantity' => $m['qty'],
                'unit' => $m['unit'],
                'note' => 'Recipe: ' . $recipeTitleHu . (!empty($m['measure']) ? ' | Needed for recipe: ' . $m['measure'] : ''),
                'is_bought' => 0,
                'created_by' => $userId,
                'location' => $this->guessLocationForItem((string)$m['name']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('shopping.index', ['hid' => $hid])
            ->with('success', 'Missing ingredients added to the shopping list.');
    }

    public function consume(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'hid' => 'required|integer',
        ]);

        $hid = (int)$request->input('hid');
        $this->assertMember($userId, $hid);

        $meal = $this->apiDetails($id);
        if (!$meal) {
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Recipe not found.']);
        }

        $ings = [];
        // Főzés előtt ugyanúgy parse-oljuk a recept hozzávalóit, mint a készletellenőrzésnél,
        // hogy a levonás ugyanazzal a mennyiségértelmezéssel dolgozzon.
        for ($i=1; $i<=20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;
            if ($this->isAlwaysAvailableIngredient($ingName)) continue;

            [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);

            $ings[] = [
                'name' => $ingName,
                'qty' => (float)$qty,
                'unit' => $unit,
                'measure' => $measure,
            ];
        }

        if (empty($ings)) {
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'No ingredients in this recipe.']);
        }

        try {
            DB::beginTransaction();

            // Első körben csak azt nézzük meg, hogy minden hozzávalóhoz van-e készletsor.
            // Ha valamelyik teljesen hiányzik, nem kezdünk részleges levonásba.
            foreach ($ings as $ing) {
                if ($this->isAlwaysAvailableIngredient((string)$ing['name'])) continue;

                $rows = $this->inventoryRowsForIngredient($hid, $ing['name']);
                if (empty($rows)) {
                    DB::rollBack();
                    return redirect()->route('recipes.show', [
                        'id' => $id,
                        'hid' => $hid,
                        'cook' => 'err',
                        'msg' => 'Not in stock: '.$ing['name']
                    ]);
                }
            }

            // Második körben ténylegesen levonjuk a mennyiségeket. A sorokat lejárati sorrendben
            // kaptuk, ezért a régebbi/hamarabb lejáró készlet fogy először.
            foreach ($ings as $ing) {
                if ($this->isAlwaysAvailableIngredient((string)$ing['name'])) continue;

                $rows = $this->inventoryRowsForIngredient($hid, $ing['name']);

                $remainingNeed = (float)$ing['qty'];
                if ($remainingNeed <= 0) $remainingNeed = 1.0;

                foreach ($rows as $row) {
                    $invId = (int)$row->id;
                    $invQty = (float)$row->quantity;
                    $invUnit = $row->unit ?? null;

                    [$needInInvUnit, $ok] = $this->convertQty($remainingNeed, $ing['unit'], $invUnit, $ing['name']);
                    if (!$ok) continue;

                    if ($invQty <= 0) continue;

                    if ($invQty >= $needInInvUnit) {
                        $newQty = $invQty - $needInInvUnit;

                        // Ha a sor gyakorlatilag elfogyott, töröljük; különben csak csökkentjük.
                        if ($newQty <= 0.00001) {
                            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);
                        } else {
                            DB::update("UPDATE inventory_items SET quantity = ? WHERE id = ? AND household_id = ?", [$newQty, $invId, $hid]);
                        }

                        $remainingNeed = 0.0;
                        break;
                    } else {
                        // Ha egy sor nem elég, teljesen elfogyasztjuk és a maradék igényt
                        // továbbvisszük a következő készletsorra.
                        [$consumedInRecipeUnit, $backOk] = $this->convertQty($invQty, $invUnit, $ing['unit'], $ing['name']);
                        if (!$backOk) continue;

                        $remainingNeed = $remainingNeed - $consumedInRecipeUnit;
                        DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);

                        if ($remainingNeed <= 0.00001) {
                            $remainingNeed = 0.0;
                            break;
                        }
                    }
                }

                if ($remainingNeed > 0.00001) {
                    // Bármilyen hiány esetén rollback: vagy minden hozzávaló levonódik,
                    // vagy semmi sem változik.
                    DB::rollBack();
                    return redirect()->route('recipes.show', [
                        'id' => $id,
                        'hid' => $hid,
                        'cook' => 'err',
                        'msg' => 'Not enough in stock: '.$ing['name'].' ('.$ing['measure'].')'
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'ok']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Error: '.$e->getMessage()]);
        }
    }

    /* =========================
       Saját receptek
       ========================= */
    public function createOwn(Request $request)
    {
        $hid = (int) $request->query('hid', 0);

        return view('recipes.own_create', [
            'hid' => $hid,
        ]);
    }

    public function storeOwn(Request $request)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'nullable|string|max:255',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|string|max:20',
            'units' => 'nullable|array',
            'units.*' => 'nullable|string|max:20',
        ]);

        $title = trim((string)$request->input('title'));
        $instructions = trim((string)$request->input('instructions', ''));
        $ingsRaw    = $request->input('ingredients', []);
        $amountsRaw = $request->input('amounts', []);
        $unitsRaw   = $request->input('units', []);

        $ings = [];
        foreach ($ingsRaw as $idx => $x) {
            $ingredient = trim((string)$x);
            if ($ingredient === '') continue;

            $amount = trim((string)($amountsRaw[$idx] ?? ''));
            $unit   = trim((string)($unitsRaw[$idx]   ?? ''));

            // A saját recepteknél az alapanyag neve mellé zárójelbe mentjük az opcionális
            // mennyiséget/egységet, így egyetlen recipe_ingredients szövegmező is elég.
            if ($amount !== '' || $unit !== '') {
                $stored = $ingredient . ' (' . ltrim($amount . ' ' . $unit) . ')';
            } else {
                $stored = $ingredient;
            }
            $ings[] = mb_substr($stored, 0, 255, 'UTF-8');
        }

        if ($title === '' || empty($ings)) {
            return back()->withErrors(['Enter a title and at least one ingredient.'])->withInput();
        }

        $hasImagePathColumn = $this->ensureRecipeImagePathColumn();
        if ($request->hasFile('image') && !$hasImagePathColumn) {
            return back()->withErrors(['Could not prepare the recipe image column. Try again, or run the database update.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $hasInstructionsColumn = false;
            try {
                // Régebbi adatbázis-dumpokban nem mindig volt instructions oszlop,
                // ezért mentés előtt futásidőben ellenőrizzük.
                $hasInstructionsColumn = DB::getSchemaBuilder()->hasColumn('recipes', 'instructions');
            } catch (\Throwable $e) {
                $hasInstructionsColumn = false;
            }

            $imagePath = null;
            if ($hasImagePathColumn && $request->hasFile('image')) {
                $file = $request->file('image');
                if ($file && $file->isValid()) {
                    // A saját recept képeit közvetlenül public alá mentjük,
                    // így külön storage link nélkül is megjeleníthetők.
                    $dir = public_path('uploads/recipes');
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }

                    $ext = strtolower((string)$file->getClientOriginalExtension());
                    $filename = 'own-recipe-' . $userId . '-' . time() . '-' . Str::random(10) . '.' . $ext;
                    $file->move($dir, $filename);
                    $imagePath = 'uploads/recipes/' . $filename;
                }
            }

            if ($hasInstructionsColumn && $hasImagePathColumn) {
                DB::insert(
                    "INSERT INTO recipes (user_id, title, instructions, image_path, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [$userId, $title, ($instructions !== '' ? $instructions : null), $imagePath]
                );
            } elseif ($hasInstructionsColumn) {
                DB::insert(
                    "INSERT INTO recipes (user_id, title, instructions, created_at) VALUES (?, ?, ?, NOW())",
                    [$userId, $title, ($instructions !== '' ? $instructions : null)]
                );
            } elseif ($hasImagePathColumn) {
                DB::insert(
                    "INSERT INTO recipes (user_id, title, image_path, created_at) VALUES (?, ?, ?, NOW())",
                    [$userId, $title, $imagePath]
                );
            } else {
                DB::insert(
                    "INSERT INTO recipes (user_id, title, created_at) VALUES (?, ?, NOW())",
                    [$userId, $title]
                );
            }

            $rid = (int) DB::getPdo()->lastInsertId();

            foreach ($ings as $ing) {
                DB::insert("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)", [$rid, $ing]);
            }

            DB::commit();

            $hid = (int) $request->input('hid', 0);

            return redirect()->route('recipes.own.show', ['id' => $rid, 'hid' => $hid])
                ->with('success', 'Recipe saved.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Error: '.$e->getMessage()])->withInput();
        }
    }

    public function showOwn(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $r = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
        if (!$r) return redirect()->route('recipes.index')->withErrors(['Own recipe not found.']);

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Join a household first.']);
        }

        $hid = (int) $request->query('hid', 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $ings = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id ASC", [$id]);
        [$ingredientsChecked, $missingCount] = $this->ownRecipeIngredientsWithStock($hid, $ings);

        return view('recipes.own_show', [
            'hid' => $hid,
            'households' => array_map(fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name], $households),
            'recipe' => $r,
            'ingredients' => $ingredientsChecked,
            'missingCount' => $missingCount,
            'cook' => (string)$request->query('cook', ''),
            'msg' => (string)$request->query('msg', ''),
        ]);
    }

    public function addMissingOwnToShopping(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'hid' => 'required|integer',
        ]);

        $hid = (int)$request->input('hid');
        $this->assertMember($userId, $hid);

        $r = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
        if (!$r) {
            return redirect()->route('recipes.index', ['hid' => $hid])->withErrors(['Own recipe not found.']);
        }

        $rows = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id ASC", [$id]);

        $added = 0;
        foreach ($rows as $row) {
            $ing = $this->parseOwnIngredientLine((string)($row->ingredient ?? ''));
            if (trim($ing['name']) === '') continue;
            if ($this->hasEnoughIngredient($hid, $ing['name'], (float)$ing['qty'], $ing['unit'])) continue;

            [$storeQty, $storeUnit] = $this->storePackForIngredient($ing['name'], (float)$ing['qty'], $ing['unit']);

            DB::table('shopping_list_items')->insert([
                'household_id' => $hid,
                'name' => $ing['name'],
                'quantity' => (float)$storeQty,
                'unit' => $storeUnit,
                'note' => 'Own recipe: ' . (string)$r->title . ($ing['measure'] !== '' ? ' | Needed for recipe: ' . $ing['measure'] : ''),
                'is_bought' => 0,
                'created_by' => $userId,
                'location' => $this->guessLocationForItem((string)$ing['name']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $added++;
        }

        if ($added === 0) {
            return redirect()->route('recipes.own.show', ['id' => $id, 'hid' => $hid])
                ->with('success', 'All ingredients are available in stock.');
        }

        return redirect()->route('shopping.index', ['hid' => $hid])
            ->with('success', 'Missing ingredients added to the shopping list.');
    }

    public function consumeOwn(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'hid' => 'required|integer',
        ]);

        $hid = (int)$request->input('hid');
        $this->assertMember($userId, $hid);

        $r = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
        if (!$r) {
            return redirect()->route('recipes.index', ['hid' => $hid])->withErrors(['Own recipe not found.']);
        }

        $rows = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id ASC", [$id]);
        $ings = [];
        foreach ($rows as $row) {
            $ing = $this->parseOwnIngredientLine((string)($row->ingredient ?? ''));
            if (trim($ing['name']) === '') continue;
            if ($this->isAlwaysAvailableIngredient($ing['name'])) continue;
            $ings[] = $ing;
        }

        if (empty($ings)) {
            return redirect()->route('recipes.own.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'No ingredients in this recipe.']);
        }

        try {
            DB::beginTransaction();

            foreach ($ings as $ing) {
                if (!$this->hasEnoughIngredient($hid, $ing['name'], (float)$ing['qty'], $ing['unit'])) {
                    DB::rollBack();
                    return redirect()->route('recipes.own.show', [
                        'id' => $id,
                        'hid' => $hid,
                        'cook' => 'err',
                        'msg' => 'Not enough in stock: '.$ing['name'].($ing['measure'] !== '' ? ' ('.$ing['measure'].')' : ''),
                    ]);
                }
            }

            foreach ($ings as $ing) {
                $rows = $this->inventoryRowsForIngredient($hid, $ing['name']);
                $remainingNeed = (float)$ing['qty'];
                if ($remainingNeed <= 0) $remainingNeed = 1.0;

                foreach ($rows as $row) {
                    $invId = (int)$row->id;
                    $invQty = (float)$row->quantity;
                    $invUnit = $row->unit ?? null;

                    [$needInInvUnit, $ok] = $this->convertQty($remainingNeed, $ing['unit'], $invUnit, $ing['name']);
                    if (!$ok) continue;
                    if ($invQty <= 0) continue;

                    if ($invQty + 0.00001 >= $needInInvUnit) {
                        $newQty = $invQty - $needInInvUnit;

                        if ($newQty <= 0.00001) {
                            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);
                        } else {
                            DB::update("UPDATE inventory_items SET quantity = ? WHERE id = ? AND household_id = ?", [$newQty, $invId, $hid]);
                        }

                        $remainingNeed = 0.0;
                        break;
                    }

                    [$consumedInRecipeUnit, $backOk] = $this->convertQty($invQty, $invUnit, $ing['unit'], $ing['name']);
                    if (!$backOk) continue;

                    DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);
                    $remainingNeed -= $consumedInRecipeUnit;

                    if ($remainingNeed <= 0.00001) {
                        $remainingNeed = 0.0;
                        break;
                    }
                }

                if ($remainingNeed > 0.00001) {
                    DB::rollBack();
                    return redirect()->route('recipes.own.show', [
                        'id' => $id,
                        'hid' => $hid,
                        'cook' => 'err',
                        'msg' => 'Not enough in stock: '.$ing['name'].($ing['measure'] !== '' ? ' ('.$ing['measure'].')' : ''),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('recipes.own.show', ['id' => $id, 'hid' => $hid, 'cook' => 'ok']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('recipes.own.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Error: '.$e->getMessage()]);
        }
    }

    public function updateOwnImage(Request $request, int $id)
    {
        $userId = (int) session('user_id');
        $hid = (int)$request->input('hid', 0);

        $request->validate([
            'hid' => 'required|integer',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        $this->assertMember($userId, $hid);

        if (!$this->ensureRecipeImagePathColumn()) {
            return back()->withErrors(['Could not prepare the recipe image column.']);
        }

        $recipe = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
        if (!$recipe) {
            return redirect()->route('recipes.index', ['hid' => $hid])->withErrors(['Own recipe not found.']);
        }

        $file = $request->file('image');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['Invalid image file.']);
        }

        $dir = public_path('uploads/recipes');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $ext = strtolower((string)$file->getClientOriginalExtension());
        $filename = 'own-recipe-' . $userId . '-' . time() . '-' . Str::random(10) . '.' . $ext;
        $file->move($dir, $filename);
        $imagePath = 'uploads/recipes/' . $filename;

        DB::update("UPDATE recipes SET image_path = ? WHERE id = ? AND user_id = ?", [$imagePath, $id, $userId]);

        if (!empty($recipe->image_path)) {
            $oldPath = public_path((string)$recipe->image_path);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return redirect()->route('recipes.own.show', ['id' => $id, 'hid' => $hid])
            ->with('success', 'Recipe image saved.');
    }

    public function deleteOwn(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        DB::beginTransaction();
        try {
            $recipe = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);

            // Először a gyermek hozzávalókat töröljük, utána magát a receptet,
            // hogy ne maradjanak árva recipe_ingredients sorok.
            DB::delete("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);
            DB::delete("DELETE FROM recipes WHERE id = ? AND user_id = ?", [$id, $userId]);
            DB::commit();

            if ($recipe && !empty($recipe->image_path)) {
                $path = public_path((string)$recipe->image_path);
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            return redirect()->route('recipes.index')->with('success', 'Recipe deleted.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Error: '.$e->getMessage()]);
        }
    }
}
