<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    private ?string $lastCurlError = null;

    /* =========================
       Households helpers
       ========================= */
    private function householdsForUser(int $userId): array
    {
        return DB::select("
            SELECT h.id AS household_id, h.name
            FROM households h
            INNER JOIN household_members hm ON hm.household_id = h.id
            WHERE hm.member_id = ?
            ORDER BY h.name ASC
        ", [$userId]);
    }

    private function assertMember(int $userId, int $hid): void
    {
        $ok = DB::selectOne("
            SELECT id
            FROM household_members
            WHERE member_id = ? AND household_id = ?
            LIMIT 1
        ", [$userId, $hid]);

        if (!$ok) abort(403, 'You do not have permission for this household.');
    }

    /* =========================
       TheMealDB API
       ========================= */
    private function apiSearch(string $query, int $limit = 50): array
    {
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
       String helpers
       ========================= */
    private function normalizeForMatch(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return Str::ascii($s);
    }

    private function parseMeasureToQtyUnit(string $measure): array
    {
        $m = trim((string)$measure);
        if ($m === '') return [1.0, null];

        $m = str_replace(',', '.', $m);

        if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
            $unit = trim((string)$mm[4]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        if (preg_match('/^\s*(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            $qty = (float)$mm[1] / max(1.0, (float)$mm[2]);
            $unit = trim((string)$mm[3]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([^\d].*)?$/u', $m, $mm)) {
            $qty = (float)$mm[1];
            $unit = trim((string)($mm[2] ?? ''));
            if ($unit !== '') $unit = preg_split('/\s+/u', $unit)[0];
            return [$qty, $unit !== '' ? $unit : null];
        }

        return [1.0, null];
    }

    private function canonicalUnit(?string $unit): ?string
    {
        if ($unit === null) return null;

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
        $n = $this->normalizeForMatch($name);

        if (str_contains($n, 'honey') || str_contains($n, 'mez')) return 1.4;
        if (str_contains($n, 'sugar') || str_contains($n, 'cukor')) return 0.85;
        if (str_contains($n, 'salt') || str_contains($n, 'so')) return 1.2;
        if (str_contains($n, 'flour') || str_contains($n, 'liszt')) return 0.53;
        if (str_contains($n, 'butter') || str_contains($n, 'vaj')) return 0.96;

        return null;
    }

    private function recipeAmountToBase(string $name, float $qty, ?string $unit): array
    {
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

        if ($baseUnit === 'ml' && $density !== null) {
            return [$baseQty * $density, 'g'];
        }

        return [$baseQty, $baseUnit];
    }

    private function hasEnoughIngredient(int $hid, string $name, float $needQty, ?string $needUnit): bool
    {
        $rows = $this->inventoryRowsForIngredient($hid, $name);
        if (empty($rows)) return false;

        [$requiredQty, $requiredUnit] = $this->comparableAmount($name, $needQty, $needUnit);
        if ($requiredUnit === null || $requiredUnit === '') return true;

        $availableQty = 0.0;
        $sawComparable = false;

        foreach ($rows as $row) {
            [$rowQty, $rowUnit] = $this->comparableAmount($name, (float)$row->quantity, $row->unit ?? null);

            if ($rowUnit === null && $requiredUnit === 'db') {
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
        $n = $this->normalizeForMatch($name);
        [$needQty, $needUnit] = $this->recipeAmountToBase($name, $recipeQty, $recipeUnit);

        $density = $this->ingredientDensityGPerMl($name);
        if ($needUnit === 'ml' && $density !== null) {
            $needQty *= $density;
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
            return $this->convertQty($baseQty * $density, 'g', $tu, $name);
        }

        if ($baseUnit === 'g' && ($tu === 'g' || $tu === 'kg')) {
            return $this->convertQty($baseQty, 'g', $tu, $name);
        }

        return [$qty, false];
    }

    private function householdInventoryNameList(int $hid): array
    {
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

    private function inventoryRowsForIngredient(int $hid, string $ingredientEn): array
    {
        $ingredientEn = trim((string)$ingredientEn);
        if ($ingredientEn === '') return [];

        $enNeedle = $this->normalizeForMatch($ingredientEn);

        $likes = [];
        $params = [$hid];

        if ($enNeedle !== '') { $likes[] = "LOWER(name) LIKE ?"; $params[] = '%'.$enNeedle.'%'; }

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

    /* =========================
       Pages
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

        $own = DB::select("
            SELECT r.id, r.title, r.created_at
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
        for ($i = 1; $i <= 20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;

            [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);
            $has = $this->hasEnoughIngredient($hid, $ingName, (float)($qty > 0 ? $qty : 1.0), $unit);
            if ($has) continue;

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
        for ($i=1; $i<=20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;

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

            foreach ($ings as $ing) {
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

            foreach ($ings as $ing) {
                $rows = $this->inventoryRowsForIngredient($hid, $ing['name']);

                $remainingNeed = (float)$ing['qty'];
                if ($remainingNeed <= 0) $remainingNeed = 1.0;

                foreach ($rows as $row) {
                    $invId = (int)$row->id;
                    $invQty = (float)$row->quantity;
                    $invUnit = $row->unit ?? null;

                    [$needInInvUnit, $ok] = $this->convertQty($remainingNeed, $ing['unit'], $invUnit, $ing['name']);
                    if (!$ok) $needInInvUnit = $remainingNeed;

                    if ($invQty <= 0) continue;

                    if ($invQty >= $needInInvUnit) {
                        $newQty = $invQty - $needInInvUnit;

                        if ($newQty <= 0.00001) {
                            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);
                        } else {
                            DB::update("UPDATE inventory_items SET quantity = ? WHERE id = ? AND household_id = ?", [$newQty, $invId, $hid]);
                        }

                        $remainingNeed = 0.0;
                        break;
                    } else {
                        $remainingNeed = $remainingNeed - $invQty;
                        DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$invId, $hid]);

                        if ($remainingNeed <= 0.00001) {
                            $remainingNeed = 0.0;
                            break;
                        }
                    }
                }

                if ($remainingNeed > 0.00001) {
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
       Own recipes
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

        DB::beginTransaction();
        try {
            $hasInstructionsColumn = false;
            try {
                $hasInstructionsColumn = DB::getSchemaBuilder()->hasColumn('recipes', 'instructions');
            } catch (\Throwable $e) {
                $hasInstructionsColumn = false;
            }

            if ($hasInstructionsColumn) {
                DB::insert(
                    "INSERT INTO recipes (user_id, title, instructions, created_at) VALUES (?, ?, ?, NOW())",
                    [$userId, $title, ($instructions !== '' ? $instructions : null)]
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

        $ings = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id ASC", [$id]);

        $hid = (int) $request->query('hid', 0);

        return view('recipes.own_show', [
            'hid' => $hid,
            'recipe' => $r,
            'ingredients' => $ings,
        ]);
    }

    public function deleteOwn(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        DB::beginTransaction();
        try {
            DB::delete("DELETE FROM recipe_ingredients WHERE recipe_id = ?", [$id]);
            DB::delete("DELETE FROM recipes WHERE id = ? AND user_id = ?", [$id, $userId]);
            DB::commit();
            return redirect()->route('recipes.index')->with('success', 'Recipe deleted.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Error: '.$e->getMessage()]);
        }
    }
}
