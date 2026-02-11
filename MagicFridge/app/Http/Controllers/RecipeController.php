<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    /* =========================
       Households helpers
       ========================= */
    private function householdsForUser(int $userId): array
    {
        // nálad az owner is benne van household_members-ben (jó eséllyel), ezért elég ez
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
        if ($raw === null) return ['_error' => 'Nem elérhető a TheMealDB (cURL hiba).'];

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['meals']) || !is_array($decoded['meals'])) {
            return ['_error' => 'Nincs találat erre a keresésre.'];
        }

        $meals = array_slice($decoded['meals'], 0, $limit);
        $results = [];
        foreach ($meals as $m) {
            $results[] = [
                'id' => (int)$m['idMeal'],
                'title' => $this->huTitle((string)$m['strMeal']),
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
        if (!is_array($decoded) || !isset($decoded['meals'][0])) return null;

        return $decoded['meals'][0];
    }

    private function curlGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $ok = ($raw !== false);
        curl_close($ch);
        return $ok ? $raw : null;
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

        // mixed fraction "1 1/2"
        if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
            $unit = trim((string)$mm[4]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        // fraction "1/2"
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
        $needle = mb_strtolower(trim($needle), 'UTF-8');
        if ($needle === '') return false;

        foreach ($invNames as $n) {
            if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
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

        // saját receptek
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

        // összegyűjtjük a hozzávalókat
        $ingredients = [];
        for ($i = 1; $i <= 20; $i++) {
            $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
            $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
            if ($ingName === '') continue;

            // itt egyszerűsítünk: EN névvel ellenőrzünk raktárban
            // ha kell később HU fordítás/caching, bele lehet rakni
            $ingredients[] = [
                'name_en' => $ingName,
                'measure' => $measure,
            ];
        }
        

        $invNames = $this->householdInventoryNameList($hid);

        $missingCount = 0;
        $ingredientsChecked = [];
        foreach ($ingredients as $idx => $ing) {
            $has = $this->invContains($invNames, $ing['name_en']);
            if (!$has) $missingCount++;

            $ingredientsChecked[] = [
                'idx' => $idx,
                'name' => $ing['name_en'],
                'measure' => $ing['measure'],
                'has' => $has,
            ];
        }

        $cook = (string)$request->query('cook', '');
        $msg  = (string)$request->query('msg', '');

        return view('recipes.show', [
            'hid' => $hid,
            'households' => array_map(fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name], $households),
            'mealId' => $id,
            'title' => $this->huTitle((string)($meal['strMeal'] ?? 'Recept')),
            'image' => (string)($meal['strMealThumb'] ?? ''),
            'instructions' => (string)($meal['strInstructions'] ?? ''),
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

    $recipeTitleHu = $this->huTitle((string)($meal['strMeal'] ?? 'Recept'));
    $note = 'Recept: ' . $recipeTitleHu;

    // raktár nevek (ellenőrzéshez)
    $invNames = $this->householdInventoryNameList($hid);

    // hozzávalók összegyűjtése (TheMealDB 1..20)
    $missing = [];
    for ($i = 1; $i <= 20; $i++) {
        $ingName = trim((string)($meal["strIngredient{$i}"] ?? ''));
        $measure = trim((string)($meal["strMeasure{$i}"] ?? ''));
        if ($ingName === '') continue;

        // ha nincs a raktárban → hiányzó
        $has = $this->invContains($invNames, $ingName);
        if ($has) continue;

        [$qty, $unit] = $this->parseMeasureToQtyUnit($measure);

        // hozzávaló neve HU-ra (cache-elve lesz)
        $nameHu = $this->huTitle($ingName);

        $missing[] = [
            'name' => $nameHu,
            'qty'  => (float)($qty > 0 ? $qty : 1.0),
            'unit' => $unit,
        ];
    }

    if (empty($missing)) {
        return redirect()->route('recipes.show', ['id' => $id, 'hid' => $hid])
            ->with('success', 'Nincs hiányzó hozzávaló – minden megvan a raktárban.');
    }

    // beszúrás a shopping_list_items táblába
    foreach ($missing as $m) {
        DB::table('shopping_list_items')->insert([
            'household_id' => $hid,
            'name' => $m['name'],
            'quantity' => $m['qty'],
            'unit' => $m['unit'],
            'note' => $note,
            'is_bought' => 0,
            'created_by' => $userId,
            'location' => 'pantry', // alapértelmezett
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

        // hozzávalók begyűjtése + qty/unit
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

            // 1) előellenőrzés: legalább legyen találat mindenre
            foreach ($ings as $ing) {
                $needle = $this->normalizeForMatch($ing['name']);

                $rows = DB::select("
                    SELECT id, name, quantity, unit, expires_at
                    FROM inventory_items
                    WHERE household_id = ?
                      AND LOWER(name) LIKE ?
                    ORDER BY (expires_at IS NULL) ASC, expires_at ASC, id ASC
                ", [$hid, '%'.$needle.'%']);

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

            // 2) tényleges levonás FIFO
            foreach ($ings as $ing) {
                $needle = $this->normalizeForMatch($ing['name']);

                $rows = DB::select("
                    SELECT id, name, quantity, unit, expires_at
                    FROM inventory_items
                    WHERE household_id = ?
                      AND LOWER(name) LIKE ?
                    ORDER BY (expires_at IS NULL) ASC, expires_at ASC, id ASC
                ", [$hid, '%'.$needle.'%']);

                $remainingNeed = (float)$ing['qty'];
                if ($remainingNeed <= 0) $remainingNeed = 1.0;

                foreach ($rows as $row) {
                    $invId = (int)$row->id;
                    $invQty = (float)$row->quantity;
                    $invUnit = $row->unit ?? null;

                    [$needInInvUnit, $ok] = $this->convertQty($remainingNeed, $ing['unit'], $invUnit);
                    if (!$ok) $needInInvUnit = $remainingNeed; // fallback

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
                        // elfogy a tétel
                        $remainingNeed = $remainingNeed - $invQty; // approximáció unit mismatch esetén is
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
       Own recipes (DB dump szerinti egyszerű verzió)
       ========================= */
    public function createOwn(Request $request)
    {
        return view('recipes.own_create');
    }

    public function storeOwn(Request $request)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'title' => 'required|string|max:255',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'nullable|string|max:255',
        ]);

        $title = trim((string)$request->input('title'));
        $ingsRaw = $request->input('ingredients', []);
        $ings = [];
        foreach ($ingsRaw as $x) {
            $t = trim((string)$x);
            if ($t !== '') $ings[] = $t;
        }
        if ($title === '' || empty($ings)) {
            return back()->withErrors(['Adj meg címet és legalább 1 hozzávalót.'])->withInput();
        }

        DB::beginTransaction();
        try {
            DB::insert("INSERT INTO recipes (user_id, title, created_at) VALUES (?, ?, NOW())", [$userId, $title]);
            $rid = (int)DB::getPdo()->lastInsertId();

            foreach ($ings as $ing) {
                DB::insert("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)", [$rid, $ing]);
            }

            DB::commit();
            return redirect()->route('recipes.own.show', ['id' => $rid])->with('success', 'Recept elmentve.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Hiba: '.$e->getMessage()])->withInput();
        }
    }

    public function showOwn(Request $request, int $id)
    {
        $userId = (int) session('user_id');

        $r = DB::selectOne("SELECT * FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
        if (!$r) return redirect()->route('recipes.index')->withErrors(['Nem található a saját recept.']);

        $ings = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id ASC", [$id]);

        return view('recipes.own_show', [
            'recipe' => $r,
            'ingredients' => $ings,
        ]);
    }

    private function huTitle(string $en): string
        {
            $en = trim($en);
            if ($en === '') return $en;

            // 1) cache-ből
            $cached = DB::selectOne("SELECT target FROM translations_cache WHERE source = ? LIMIT 1", [$en]);
            if ($cached && !empty($cached->target)) return (string)$cached->target;

            // 2) gyors, ingyenes translate (MyMemory) – nem tökéletes, de működik kulcs nélkül
            $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($en) . '&langpair=en|hu';
            $raw = $this->curlGet($url);
            $hu = $en;

            if ($raw) {
                $j = json_decode($raw, true);
                if (is_array($j) && isset($j['responseData']['translatedText'])) {
                    $t = trim((string)$j['responseData']['translatedText']);
                    if ($t !== '') $hu = $t;
                }
            }

            // 3) cache-eljük
            DB::insert("INSERT IGNORE INTO translations_cache (source, target) VALUES (?, ?)", [$en, $hu]);

            return $hu;
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
