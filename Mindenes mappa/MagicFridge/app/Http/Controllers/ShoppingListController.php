<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShoppingListController extends Controller
{
    /* ================================
     * Households (owner + member)
     * ================================ */
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
        $ok = DB::selectOne("
            SELECT hm.id
            FROM household_members hm
            WHERE hm.member_id = ? AND hm.household_id = ?
            LIMIT 1
        ", [$userId, $hid]);

        // tulaj is lehet
        if (!$ok) {
            $owner = DB::selectOne("SELECT id FROM households WHERE id = ? AND owner_id = ? LIMIT 1", [$hid, $userId]);
            if (!$owner) abort(403, 'Nincs jogosultság ehhez a háztartáshoz.');
        }
    }

    /* ================================
     * Inventory helpers (missing check)
     * ================================ */
    private function invNamesForHousehold(int $hid): array
    {
        return DB::select("
            SELECT LOWER(TRIM(name)) AS n
            FROM inventory_items
            WHERE household_id = ?
            GROUP BY LOWER(TRIM(name))
        ", [$hid]);
    }

    private function invContains(array $invRows, string $needle): bool
    {
        $needle = mb_strtolower(trim($needle), 'UTF-8');
        if ($needle === '') return false;

        foreach ($invRows as $r) {
            $n = (string)($r->n ?? '');
            if ($n === '') continue;

            if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    private function guessLocationForItem(string $name): string
    {
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

        foreach ($freezer as $k) if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'freezer';
        foreach ($fridge as $k) if (mb_stripos($n, $k, 0, 'UTF-8') !== false) return 'fridge';

        return 'pantry';
    }

    private function parseMeasureToQtyUnit(string $measure): array
    {
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

            if ($unit !== '' && preg_match('/^(chopped|slice|sliced|minced|pinch|handful|to|taste)$/iu', $unit)) {
                return [1.0, null];
            }

            return [$qty, $unit !== '' ? $unit : null];
        }

        return [1.0, null];
    }

    private function normalizeForMatch(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return str_replace(['á','é','í','ó','ö','ő','ú','ü','ű'], ['a','e','i','o','o','o','u','u','u'], $s);
    }

    private function toStorePack(string $name, float $recipeQty, ?string $recipeUnit): array
    {
        $n = $this->normalizeForMatch($name);
        $u = $recipeUnit ? $this->normalizeForMatch($recipeUnit) : null;

        if (str_contains($n, 'tej') || str_contains($n, 'milk')) {
            $needMl = null;
            if ($u === 'ml') $needMl = $recipeQty;
            if ($u === 'l')  $needMl = $recipeQty * 1000.0;

            $packs = 1;
            if ($needMl !== null) $packs = (int)ceil($needMl / 1000.0);
            return [$packs, 'l'];
        }

        if (str_contains($n, 'joghurt') || str_contains($n, 'yogurt')) return [1, 'pohár'];
        if (str_contains($n, 'tejfol') || str_contains($n, 'tejföl') || str_contains($n, 'sour')) return [1, 'doboz'];
        if (str_contains($n, 'tejszin') || str_contains($n, 'tejszín') || str_contains($n, 'cream')) return [1, 'doboz'];
        if (str_contains($n, 'olaj') || str_contains($n, 'oil')) return [1, 'üveg'];
        if (str_contains($n, 'ecet') || str_contains($n, 'vinegar')) return [1, 'üveg'];

        if (str_contains($n, 'tojas') || str_contains($n, 'tojás') || str_contains($n, 'egg')) {
            if ($u === 'pcs' || $u === 'db') return [max(1, (int)ceil($recipeQty)), 'db'];
            return [6, 'db'];
        }

        if (
            str_contains($n, 'csirke') || str_contains($n, 'pulyka') ||
            str_contains($n, 'marha') || str_contains($n, 'sertes') || str_contains($n, 'sertés')
        ) {
            $needG = null;
            if ($u === 'g')  $needG = $recipeQty;
            if ($u === 'kg') $needG = $recipeQty * 1000.0;

            if ($needG !== null) {
                $packs = (int)ceil($needG / 500.0);
                return [max(1, $packs), 'csomag'];
            }
            return [1, 'csomag'];
        }

        return [1, 'db'];
    }

    /* ================================
     * GET: page
     * ================================ */
    public function index(Request $request)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Előbb hozz létre vagy csatlakozz egy háztartáshoz.']);
        }

        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        // ha nem a user háztartása, fallback
        $hhMap = [];
        foreach ($households as $h) $hhMap[(int)$h->household_id] = (string)$h->name;
        if (!isset($hhMap[$hid])) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $items = DB::select("
            SELECT *
            FROM shopping_list_items
            WHERE household_id = ?
            ORDER BY is_bought ASC, id DESC
        ", [$hid]);

        // Saját receptek a dropdownhoz (ha van a DB-ben)
        $recipes = DB::select("
            SELECT id, title
            FROM recipes
            WHERE user_id = ?
            ORDER BY id DESC
        ", [$userId]);

        return view('shopping.index', [
            'households' => $households,
            'householdId' => $hid,
            'householdName' => $hhMap[$hid] ?? '',
            'items' => $items,
            'recipes' => $recipes,
        ]);
    }

    /* ================================
     * POST: actions (add/toggle/delete/clear/buy/missing)
     * ================================ */
    public function post(Request $request)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Előbb hozz létre vagy csatlakozz egy háztartáshoz.']);
        }

        $hid = (int)$request->input('hid', 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $action = trim((string)$request->input('action', ''));

        try {
            /* 0) add_missing_api */
            if ($action === 'add_missing_api') {
                $recipeTitle = trim((string)$request->input('recipe_title', ''));
                $invNames = $this->invNamesForHousehold($hid);

                $added = 0;
                $missingItems = $request->input('missing_item', null);

                if (is_array($missingItems)) {
                    foreach ($missingItems as $it) {
                        if (!is_array($it)) continue;
                        if (!isset($it['add'])) continue;

                        $nm = trim((string)($it['name'] ?? ''));
                        $measure = trim((string)($it['measure'] ?? ''));

                        if ($nm === '') continue;
                        if ($this->invContains($invNames, $nm)) continue;

                        [$rq, $ru] = $this->parseMeasureToQtyUnit($measure);
                        [$qty, $unit] = $this->toStorePack($nm, (float)$rq, $ru);

                        $noteParts = [];
                        if ($recipeTitle !== '') $noteParts[] = "Recept: " . $recipeTitle;
                        if ($measure !== '') $noteParts[] = "Mérték: " . $measure;
                        $note = $noteParts ? implode(" | ", $noteParts) : null;

                        $loc = $this->guessLocationForItem($nm);

                        DB::table('shopping_list_items')->insert([
                            'household_id' => $hid,
                            'name' => $nm,
                            'quantity' => (float)$qty,
                            'unit' => $unit !== '' ? $unit : null,
                            'note' => $note,
                            'location' => $loc,
                            'created_by' => $userId,
                        ]);
                        $added++;
                    }

                    return redirect()->route('shopping.index', ['hid' => $hid])
                        ->with('success', $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Nincs hozzáadandó hiányzó tétel.");
                }

                // régi formátum
                $names = $request->input('missing_name', []);
                if (!is_array($names)) $names = [];

                foreach ($names as $nmRaw) {
                    $nm = trim((string)$nmRaw);
                    if ($nm === '') continue;
                    if ($this->invContains($invNames, $nm)) continue;

                    $note = $recipeTitle !== '' ? ("Recept: " . $recipeTitle) : null;
                    $loc = $this->guessLocationForItem($nm);

                    DB::table('shopping_list_items')->insert([
                        'household_id' => $hid,
                        'name' => $nm,
                        'quantity' => 1,
                        'unit' => null,
                        'note' => $note,
                        'location' => $loc,
                        'created_by' => $userId,
                    ]);
                }

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', "Hiányzók hozzáadva a bevásárlólistához.");
            }

            /* 1) add_missing_for_own_recipe */
            if ($action === 'add_missing_for_own_recipe') {
                $recipeId = (int)$request->input('recipe_id', 0);

                $r = DB::selectOne("SELECT title FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$recipeId, $userId]);
                if (!$r) {
                    return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Nincs ilyen saját recept, vagy nincs jogosultságod.']);
                }

                $ings = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id", [$recipeId]);
                $invNames = $this->invNamesForHousehold($hid);

                $added = 0;
                foreach ($ings as $row) {
                    $nm = trim((string)($row->ingredient ?? ''));
                    if ($nm === '') continue;
                    if ($this->invContains($invNames, $nm)) continue;

                    DB::table('shopping_list_items')->insert([
                        'household_id' => $hid,
                        'name' => $nm,
                        'quantity' => 1,
                        'unit' => null,
                        'note' => "Recept: " . (string)$r->title,
                        'location' => 'pantry',
                        'created_by' => $userId,
                    ]);
                    $added++;
                }

                return redirect()->route('shopping.index', ['hid' => $hid])
                    ->with('success', $added > 0 ? "Hiányzók hozzáadva a bevásárlólistához." : "Minden hozzávaló megvan a raktárban.");
            }

            /* 2) add */
            if ($action === 'add') {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'quantity' => 'nullable',
                    'unit' => 'nullable|string|max:50',
                    'note' => 'nullable|string',
                    'location' => 'required|in:fridge,freezer,pantry',
                ]);

                $name = trim((string)$request->input('name'));
                $quantityRaw = str_replace(',', '.', (string)$request->input('quantity', '1'));
                $quantity = is_numeric($quantityRaw) ? (float)$quantityRaw : 1.0;
                if ($quantity <= 0) $quantity = 1.0;

                $unit = trim((string)$request->input('unit', ''));
                $note = trim((string)$request->input('note', ''));
                $location = (string)$request->input('location', 'pantry');

                DB::table('shopping_list_items')->insert([
                    'household_id' => $hid,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit !== '' ? $unit : null,
                    'note' => $note !== '' ? $note : null,
                    'location' => $location,
                    'created_by' => $userId,
                ]);

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Hozzáadva a listához.');
            }

            /* 3) toggle (megvett/vissza + inventory upsert megvettkor) */
            if ($action === 'toggle') {
                $id = (int)$request->input('id', 0);
                $to = (int)$request->input('to', 0);
                if (!in_array($to, [0,1], true)) $to = 0;

                $item = DB::selectOne("
                    SELECT id, household_id, name, quantity, unit, note, location, is_bought
                    FROM shopping_list_items
                    WHERE id = ? AND household_id = ?
                    LIMIT 1
                ", [$id, $hid]);

                if ($item) {
                    DB::transaction(function () use ($to, $userId, $id, $hid, $item) {
                        DB::update("
                            UPDATE shopping_list_items
                            SET is_bought = ?,
                                bought_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE NULL END,
                                bought_by = CASE WHEN ? = 1 THEN ? ELSE NULL END
                            WHERE id = ? AND household_id = ?
                        ", [$to, $to, $to, $userId, $id, $hid]);

                        if ($to !== 1) return;

                        $name = (string)$item->name;
                        $qty  = (float)$item->quantity;
                        $unit = $item->unit !== null ? (string)$item->unit : null;
                        $note = $item->note !== null ? (string)$item->note : null;
                        $loc  = in_array((string)$item->location, ['fridge','freezer','pantry'], true) ? (string)$item->location : 'pantry';

                        // find existing inventory item (unit-aware)
                        if ($unit !== null && trim($unit) !== '') {
                            $existing = DB::selectOne("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                  AND ((unit IS NULL AND ? IS NULL) OR unit = ?)
                                ORDER BY id DESC
                                LIMIT 1
                            ", [$hid, $name, $loc, $unit, $unit]);
                        } else {
                            $existing = DB::selectOne("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                ORDER BY id DESC
                                LIMIT 1
                            ", [$hid, $name, $loc]);
                        }

                        if ($existing) {
                            DB::update("
                                UPDATE inventory_items
                                SET quantity = quantity + ?
                                WHERE id = ? AND household_id = ?
                            ", [$qty, (int)$existing->id, $hid]);
                        } else {
                            DB::table('inventory_items')->insert([
                                'household_id' => $hid,
                                'name' => $name,
                                'category' => null,
                                'location' => $loc,
                                'quantity' => $qty,
                                'unit' => $unit,
                                'expires_at' => null,
                                'note' => $note,
                            ]);
                        }
                    });
                }

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Mentve.');
            }

            /* 4) delete */
            if ($action === 'delete') {
                $id = (int)$request->input('id', 0);
                DB::delete("DELETE FROM shopping_list_items WHERE id = ? AND household_id = ?", [$id, $hid]);
                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Törölve.');
            }

            /* 5) clear_bought */
            if ($action === 'clear_bought') {
                DB::delete("DELETE FROM shopping_list_items WHERE household_id = ? AND is_bought = 1", [$hid]);
                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'A megvett tételek törölve.');
            }

            /* 6) clear_all */
            if ($action === 'clear_all') {
                DB::delete("DELETE FROM shopping_list_items WHERE household_id = ?", [$hid]);
                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Az összes tétel törölve.');
            }

            /* 7) buy_all (mind megvett + inventory) */
            if ($action === 'buy_all') {
                $toBuy = DB::select("
                    SELECT id, name, quantity, unit, note, location
                    FROM shopping_list_items
                    WHERE household_id = ? AND is_bought = 0
                    ORDER BY id ASC
                ", [$hid]);

                if (empty($toBuy)) {
                    return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Nincs megveendő tétel.');
                }

                DB::transaction(function () use ($toBuy, $userId, $hid) {
                    foreach ($toBuy as $item) {
                        $id   = (int)$item->id;
                        $name = (string)$item->name;
                        $qty  = (float)$item->quantity;
                        $unit = $item->unit !== null ? (string)$item->unit : null;
                        $note = $item->note !== null ? (string)$item->note : null;
                        $loc  = in_array((string)$item->location, ['fridge','freezer','pantry'], true) ? (string)$item->location : 'pantry';

                        DB::update("
                            UPDATE shopping_list_items
                            SET is_bought = 1,
                                bought_at = CURRENT_TIMESTAMP,
                                bought_by = ?
                            WHERE id = ? AND household_id = ?
                        ", [$userId, $id, $hid]);

                        if ($unit !== null && trim($unit) !== '') {
                            $existing = DB::selectOne("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                  AND ((unit IS NULL AND ? IS NULL) OR unit = ?)
                                ORDER BY id DESC
                                LIMIT 1
                            ", [$hid, $name, $loc, $unit, $unit]);
                        } else {
                            $existing = DB::selectOne("
                                SELECT id
                                FROM inventory_items
                                WHERE household_id = ? AND name = ? AND location = ?
                                ORDER BY id DESC
                                LIMIT 1
                            ", [$hid, $name, $loc]);
                        }

                        if ($existing) {
                            DB::update("
                                UPDATE inventory_items
                                SET quantity = quantity + ?
                                WHERE id = ? AND household_id = ?
                            ", [$qty, (int)$existing->id, $hid]);
                        } else {
                            DB::table('inventory_items')->insert([
                                'household_id' => $hid,
                                'name' => $name,
                                'category' => null,
                                'location' => $loc,
                                'quantity' => $qty,
                                'unit' => $unit,
                                'expires_at' => null,
                                'note' => $note,
                            ]);
                        }
                    }
                });

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Minden tétel megvéve és felvéve a raktárba.');
            }

            return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Ismeretlen művelet.']);
        } catch (\Throwable $e) {
            return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Hiba: ' . $e->getMessage()]);
        }
    }
}
