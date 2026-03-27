<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        if (!$ok) abort(403, 'Nincs jogosultság ehhez a háztartáshoz.');
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
            return ['_error' => $this->lastCurlError ?? 'Nem sikerült kapcsolódni a TheMealDB-hez.'];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || !array_key_exists('meals', $decoded)) {
            return ['_error' => 'Hibás válasz a TheMealDB-től.'];
        }

        if ($decoded['meals'] === null) return [];

        if (!is_array($decoded['meals'])) {
            return ['_error' => 'Hibás találati lista a TheMealDB-től.'];
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
            $this->lastCurlError = 'A PHP cURL extension nincs engedélyezve.';
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
            $this->lastCurlError = "cURL hiba ($errno): $err";
            curl_close($ch);
            return null;
        }

        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) {
            $this->lastCurlError = "HTTP $code hiba a távoli API-tól ($url)";
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
        $s = str_replace(['á','é','í','ó','ö','ő','ú','ü','ű'], ['a','e','i','o','o','o','u','u','u'], $s);
        return $s;
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

    private function convertQty(float $qty, ?string $fromUnit, ?string $toUnit): array
    {
        $fu = $fromUnit ? $this->normalizeForMatch($fromUnit) : null;
        $tu = $toUnit ? $this->normalizeForMatch($toUnit) : null;

        if ($tu === null || $tu === '') return [$qty, true];
        if ($fu === null || $fu === '') return [$qty, true];

        $map = [
            'grams' => 'g', 'gram' => 'g',
            'kilograms' => 'kg', 'kilogram' => 'kg',
            'milliliters' => 'ml', 'milliliter' => 'ml',
            'liters' => 'l', 'liter' => 'l',
            'pcs' => 'db', 'piece' => 'db', 'pieces' => 'db'
        ];
        if (isset($map[$fu])) $fu = $map[$fu];
        if (isset($map[$tu])) $tu = $map[$tu];

        if ($fu === $tu) return [$qty, true];

        if ($fu === 'g' && $tu === 'kg') return [$qty / 1000.0, true];
        if ($fu === 'kg' && $tu === 'g') return [$qty * 1000.0, true];

        if ($fu === 'ml' && $tu === 'l') return [$qty / 1000.0, true];
        if ($fu === 'l' && $tu === 'ml') return [$qty * 1000.0, true];

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
                ->withErrors(['Előbb csatlakozz egy háztartáshoz.']);
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
            return redirect()->route('households.index')->withErrors(['Előbb csatlakozz egy háztartáshoz.']);
        }

        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $meal = $this->apiDetails($id);
        if (!$meal) {
            return redirect()->route('recipes.index', ['hid' => $hid])->withErrors(['Nem található a recept.']);
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

        $invNames = $this->householdInventoryNameList($hid);

        $missingCount = 0;
        $ingredientsChecked = [];
        foreach ($ingredients as $idx => $ing) {
            $nameEn = (string)$ing['name_en'];
            $nameHu = (string)$ing['name_hu'];

            $has = $this->invContains($invNames, $nameEn);
            if (!$has && $nameHu !== '') {
                $has = $this->invContains($invNames, $nameHu);
            }

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

        return view('recipes.show', [
            'hid' => $hid,
            'households' => array_map(fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name], $households),
            'mealId' => $id,
            'title' => $titleHu,
            'image' => (string)($meal['strMealThumb'] ?? ''),
            'instructions' => $instructionsHu,
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
                ->withErrors(['Nem található a recept.']);
        }

        $recipeTitleHu = (string)($meal['strMeal'] ?? 'Recipe');
        $note = 'Recept: ' . $recipeTitleHu;

        $invNames = $this->householdInventoryNameList($hid);

        $missing = [];
        for ($i = 1; $i <= 20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;

            $has = $this->invContains($invNames, $ingName);
            if ($has) continue;

            [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);

            $missing[] = [
                'name' => $ingName,
                'qty'  => (float)($qty > 0 ? $qty : 1.0),
                'unit' => $unit,
            ];
        }

        if (empty($missing)) {
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid])
                ->with('success', 'Nincs hiányzó hozzávaló – minden megvan a raktárban.');
        }

        foreach ($missing as $m) {
            DB::table('shopping_list_items')->insert([
                'household_id' => $hid,
                'name' => $m['name'],
                'quantity' => $m['qty'],
                'unit' => $m['unit'],
                'note' => $note,
                'is_bought' => 0,
                'created_by' => $userId,
                'location' => 'pantry',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('shopping.index', ['hid' => $hid])
            ->with('success', 'Hiányzók hozzáadva a bevásárlólistához.');
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
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Nem található a recept.']);
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
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Nincs hozzávaló a receptben.']);
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
                        'msg' => 'Nincs a raktárban: '.$ing['name']
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

                    [$needInInvUnit, $ok] = $this->convertQty($remainingNeed, $ing['unit'], $invUnit);
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
                        'msg' => 'Nincs elég a raktárban: '.$ing['name'].' ('.$ing['measure'].')'
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'ok']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid, 'cook' => 'err', 'msg' => 'Hiba: '.$e->getMessage()]);
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
            'ingredient_units' => 'nullable|array',
            'ingredient_units.*' => 'nullable|string|max:50',
        ]);

        $title = trim((string)$request->input('title'));
        $instructions = trim((string)$request->input('instructions', ''));
        $ingsRaw = $request->input('ingredients', []);
        $unitsRaw = $request->input('ingredient_units', []);

        $ings = [];
        foreach ($ingsRaw as $idx => $x) {
            $ingredient = trim((string)$x);
            if ($ingredient === '') continue;

            $unit = trim((string)($unitsRaw[$idx] ?? ''));
            $stored = $unit !== '' ? ($ingredient . ' (' . $unit . ')') : $ingredient;
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
            return back()->withErrors(['Hiba: '.$e->getMessage()])->withInput();
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
            return redirect()->route('recipes.index')->with('success', 'Recept törölve.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Hiba: '.$e->getMessage()]);
        }
    }
}