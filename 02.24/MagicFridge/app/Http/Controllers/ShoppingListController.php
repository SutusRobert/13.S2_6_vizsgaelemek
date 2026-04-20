<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ShoppingListController extends Controller
{
    /* ================================
     * Háztartások kezelése tulajdonosként és tagként
     * ================================ */
    private function householdsForUser(int $userId): array
    {
        // A bevásárlólista tulajdonosként és tagként is elérhető háztartásokat mutat.
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

        // Itt a tulajdonost is elfogadjuk, akkor is, ha nincs külön household_members sora.
        if (!$ok) {
            $owner = DB::selectOne("SELECT id FROM households WHERE id = ? AND owner_id = ? LIMIT 1", [$hid, $userId]);
            if (!$owner) abort(403, 'You do not have permission for this household.');
        }
    }

    /* ================================
     * Készlet segédfüggvények hiányellenőrzéshez
     * ================================ */
    private function invNamesForHousehold(int $hid): array
    {
        // Hiányzó hozzávalók felvétele előtt csak névlistára van szükség,
        // így nem kérjük le a teljes inventory sort.
        return DB::select("
            SELECT LOWER(TRIM(name)) AS n
            FROM inventory_items
            WHERE household_id = ?
            GROUP BY LOWER(TRIM(name))
        ", [$hid]);
    }

    private function invContains(array $invRows, string $needle): bool
    {
        // A víz alapból elérhető, ezért készletnév nélkül is "megtaláltnak" számít.
        if ($this->isAlwaysAvailableIngredient($needle)) return true;

        $needle = mb_strtolower(trim($needle), 'UTF-8');
        if ($needle === '') return false;

        foreach ($invRows as $r) {
            $n = (string)($r->n ?? '');
            if ($n === '') continue;

            // Részleges egyezést használunk, hogy például "tomato" és "tomato puree"
            // ne feltétlenül kerüljenek duplán a bevásárlólistára.
            if (mb_stripos($n, $needle, 0, 'UTF-8') !== false || mb_stripos($needle, $n, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }

    private function isAlwaysAvailableIngredient(string $name): bool
    {
        // Ezeket nem kezeljük valódi készletként, mert tipikusan mindig rendelkezésre állnak.
        $n = $this->normalizeForMatch($name);

        return in_array($n, ['water', 'viz'], true);
    }

    private function guessLocationForItem(string $name): string
    {
        // Kulcsszavas heurisztika: vásárlás után automatikusan a legvalószínűbb
        // inventory helyre tesszük a terméket.
        $n = $this->normalizeForMatch($name);

        $freezer = [
            'frozen', 'ice cream', 'icecream',
            'nugget', 'fries', 'fish fingers',
            'pizza'
        ];

        $fridge = [
            'milk', 'yogurt', 'cheese', 'cream', 'sour cream', 'butter', 'margarine',
            'egg', 'eggs',
            'chicken', 'turkey', 'beef', 'pork', 'fish',
            'ham', 'sausage', 'bacon',
            'lettuce', 'spinach', 'tomato', 'cucumber', 'pepper',
            'strawberry', 'berry'
        ];

        foreach ($freezer as $k) if (str_contains($n, $k)) return 'freezer';
        foreach ($fridge as $k) if (str_contains($n, $k)) return 'fridge';

        return 'pantry';
    }

    private function averageExpiryDateForItem(string $name, string $location): string
    {
        // Bevásárláskor becsült lejárati dátumot adunk, hogy a frissen készletbe
        // kerülő termékek is részt vegyenek a lejárati figyelmeztetésekben.
        $n = $this->normalizeForMatch($name);
        $days = match ($location) {
            'freezer' => 180,
            'fridge' => 7,
            default => 90,
        };

        if (str_contains($n, 'milk') || str_contains($n, 'tej')) $days = 7;
        elseif (str_contains($n, 'yogurt')) $days = 14;
        elseif (str_contains($n, 'cream') || str_contains($n, 'tejszin') || str_contains($n, 'sour')) $days = 10;
        elseif (str_contains($n, 'cheese') || str_contains($n, 'sajt')) $days = 21;
        elseif (str_contains($n, 'egg') || str_contains($n, 'tojas')) $days = 28;
        elseif (str_contains($n, 'chicken') || str_contains($n, 'csirke')) $days = $location === 'freezer' ? 180 : 3;
        elseif (str_contains($n, 'beef') || str_contains($n, 'pork') || str_contains($n, 'fish')) $days = $location === 'freezer' ? 180 : 4;
        elseif (str_contains($n, 'ham') || str_contains($n, 'sausage')) $days = 10;
        elseif (str_contains($n, 'apple') || str_contains($n, 'carrot') || str_contains($n, 'onion') || str_contains($n, 'garlic')) $days = 30;
        elseif (str_contains($n, 'banana') || str_contains($n, 'tomato') || str_contains($n, 'lettuce')) $days = 5;
        elseif (str_contains($n, 'bread')) $days = 5;
        elseif (str_contains($n, 'rice') || str_contains($n, 'pasta') || str_contains($n, 'flour') || str_contains($n, 'sugar') || str_contains($n, 'salt')) $days = 365;
        elseif (str_contains($n, 'oil') || str_contains($n, 'vinegar') || str_contains($n, 'honey')) $days = 365;
        elseif (str_contains($n, 'paprika') || str_contains($n, 'pepper') || str_contains($n, 'cumin') || str_contains($n, 'turmeric') || str_contains($n, 'cinnamon')) $days = 365;

        return date('Y-m-d', strtotime('+' . $days . ' days'));
    }

    private function parseMeasureToQtyUnit(string $measure): array
    {
        // A receptből érkező mértékek szabad szövegek, ezért több gyakori
        // formátumot kezelünk külön: vegyes tört, tört, szám + egység.
        $m = trim($measure);
        if ($m === '') return [1.0, null];

        $m = str_replace(',', '.', $m);

        // Vegyes tört: "1 1/2 cup" -> 1.5 cup.
        if (preg_match('/^\s*(\d+)\s+(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            $qty = (float)$mm[1] + ((float)$mm[2] / max(1.0, (float)$mm[3]));
            $unit = trim((string)$mm[4]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        // Egyszerű tört: "1/2 tsp" -> 0.5 tsp.
        if (preg_match('/^\s*(\d+)\s*\/\s*(\d+)\s*(.*)$/u', $m, $mm)) {
            $qty = (float)$mm[1] / max(1.0, (float)$mm[2]);
            $unit = trim((string)$mm[3]);
            return [$qty, $unit !== '' ? $unit : null];
        }

        // Sima szám opcionális egységgel.
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([^\d].*)?$/u', $m, $mm)) {
            $qty = (float)$mm[1];
            $unit = trim((string)($mm[2] ?? ''));

            if ($unit !== '') $unit = preg_split('/\s+/u', $unit)[0];

            if ($this->isPreparationWord($unit)) {
                // "5 thinly sliced" esetén a thinly nem egység, hanem elkészítési mód.
                return [$qty, 'pcs'];
            }

            if ($unit !== '' && preg_match('/^(pinch|handful|to|taste)$/iu', $unit)) {
                return [1.0, null];
            }

            // Ha nincs egység, darabnak vesszük, hogy a vásárlás és a főzés ugyanúgy számoljon.
            return [$qty, $unit !== '' ? $unit : 'pcs'];
        }

        return [1.0, null];
    }

    private function isPreparationWord(?string $word): bool
    {
        // Nem mértékegységek, hanem előkészítési/állapot szavak.
        if ($word === null) return false;

        $w = $this->normalizeForMatch($word);

        return in_array($w, [
            'chopped', 'finely', 'thinly', 'roughly', 'sliced', 'slice',
            'minced', 'diced', 'cubed', 'grated', 'crushed', 'peeled',
            'fresh', 'large', 'small', 'medium',
        ], true);
    }

    private function normalizeForMatch(string $s): string
    {
        // Egyezésekhez ékezet nélküli kisbetűs alakot használunk.
        $s = mb_strtolower(trim($s), 'UTF-8');
        return Str::ascii($s);
    }

    private function toStorePack(string $name, float $recipeQty, ?string $recipeUnit): array
    {
        // A bevásárlólistára bolti egységet teszünk, nem feltétlenül a recept
        // pontos mennyiségét. Ezért lesz például tejből 1 liter, húsból csomag.
        $n = $this->normalizeForMatch($name);
        $u = $recipeUnit ? $this->normalizeForMatch($recipeUnit) : null;

        if (str_contains($n, 'tej') || str_contains($n, 'milk')) {
            $needMl = null;
            if ($u === 'ml') $needMl = $recipeQty;
            if ($u === 'l')  $needMl = $recipeQty * 1000.0;

            $packs = 1;
            // Tejnél literes kiszerelésre kerekítünk felfelé.
            if ($needMl !== null) $packs = (int)ceil($needMl / 1000.0);
            return [$packs, 'l'];
        }

        if (str_contains($n, 'yogurt')) return [1, 'cup'];
        if (str_contains($n, 'tejfol') || str_contains($n, 'tejfĂ¶l') || str_contains($n, 'sour')) return [1, 'doboz'];
        if (str_contains($n, 'tejszin') || str_contains($n, 'tejszĂ­n') || str_contains($n, 'cream')) return [1, 'doboz'];
        if (str_contains($n, 'olaj') || str_contains($n, 'oil')) return [1, 'ĂĽveg'];
        if (str_contains($n, 'ecet') || str_contains($n, 'vinegar')) return [1, 'ĂĽveg'];

        if (str_contains($n, 'egg')) {
            if ($u === 'pcs' || $u === 'db') return [max(1, (int)ceil($recipeQty)), 'db'];
            return [6, 'db'];
        }

        if (
            str_contains($n, 'csirke') || str_contains($n, 'pulyka') ||
            str_contains($n, 'beef') || str_contains($n, 'pork')
        ) {
            $needG = null;
            if ($u === 'g')  $needG = $recipeQty;
            if ($u === 'kg') $needG = $recipeQty * 1000.0;

            if ($needG !== null) {
                // Húsnál 500 g-os csomagokkal számolunk.
                $packs = (int)ceil($needG / 500.0);
                return [max(1, $packs), 'csomag'];
            }
            return [1, 'csomag'];
        }

        return [1, 'db'];
    }

    /* ================================
     * GET: bevásárlólista oldal
     * ================================ */
    public function index(Request $request)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Create or join a household first.']);
        }

        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        // Ha a query paraméterben kapott háztartás nem a felhasználóé, visszaesünk
        // az első elérhető háztartásra.
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

        // Saját receptek listája a shopping oldali gyors "hiányzó alapanyagok" funkcióhoz.
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
     * POST: műveletválasztó hozzáadáshoz, törléshez és vásárláshoz
     * ================================ */
    public function post(Request $request)
    {
        $userId = (int) session('user_id');

        $households = $this->householdsForUser($userId);
        if (empty($households)) {
            return redirect()->route('households.index')->withErrors(['Create or join a household first.']);
        }

        $hid = (int)$request->input('hid', 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $action = trim((string)$request->input('action', ''));

        try {
            /* 0) API-s recept hiányzó tételeinek felvétele */
            if ($action === 'add_missing_api') {
                $recipeTitle = trim((string)$request->input('recipe_title', ''));
                $invNames = $this->invNamesForHousehold($hid);

                $added = 0;
                $missingItems = $request->input('missing_item', null);

                if (is_array($missingItems)) {
                    // Új formátum: minden hiányzó tétel külön tömbben jön névvel,
                    // mértékkel és checkbox állapottal.
                    foreach ($missingItems as $it) {
                        if (!is_array($it)) continue;
                        if (!isset($it['add'])) continue;

                        $nm = trim((string)($it['name'] ?? ''));
                        $measure = trim((string)($it['measure'] ?? ''));

                        if ($nm === '') continue;
                        if ($this->invContains($invNames, $nm)) continue;

                        [$rq, $ru] = $this->parseMeasureToQtyUnit($measure);
                        [$qty, $unit] = $this->toStorePack($nm, (float)$rq, $ru);

                        // A note megőrzi, melyik receptből és milyen eredeti mennyiségből készült a tétel.
                        $noteParts = [];
                        if ($recipeTitle !== '') $noteParts[] = "Recipe: " . $recipeTitle;
                        if ($measure !== '') $noteParts[] = "Measure: " . $measure;
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
                        ->with('success', $added > 0 ? "Missing ingredients added to the shopping list." : "No missing items to add.");
                }

                // Régi formátum támogatása: csak névlista érkezik, mennyiség nélkül.
                $names = $request->input('missing_name', []);
                if (!is_array($names)) $names = [];

                foreach ($names as $nmRaw) {
                    $nm = trim((string)$nmRaw);
                    if ($nm === '') continue;
                    if ($this->invContains($invNames, $nm)) continue;

                    $note = $recipeTitle !== '' ? ("Recipe: " . $recipeTitle) : null;
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

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', "Missing ingredients added to the shopping list.");
            }

            /* 1) Saját recept hiányzó tételeinek felvétele */
            if ($action === 'add_missing_for_own_recipe') {
                $recipeId = (int)$request->input('recipe_id', 0);

                $r = DB::selectOne("SELECT title FROM recipes WHERE id = ? AND user_id = ? LIMIT 1", [$recipeId, $userId]);
                if (!$r) {
                    return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Custom recipe not found, or you do not have permission.']);
                }

                $ings = DB::select("SELECT ingredient FROM recipe_ingredients WHERE recipe_id = ? ORDER BY id", [$recipeId]);
                $invNames = $this->invNamesForHousehold($hid);

                $added = 0;
                foreach ($ings as $row) {
                    // Saját recepteknél a hiányellenőrzés egyszerű névegyezésen alapul.
                    $nm = trim((string)($row->ingredient ?? ''));
                    if ($nm === '') continue;
                    if ($this->invContains($invNames, $nm)) continue;

                    DB::table('shopping_list_items')->insert([
                        'household_id' => $hid,
                        'name' => $nm,
                        'quantity' => 1,
                        'unit' => null,
                        'note' => "Recipe: " . (string)$r->title,
                        'location' => 'pantry',
                        'created_by' => $userId,
                    ]);
                    $added++;
                }

                return redirect()->route('shopping.index', ['hid' => $hid])
                    ->with('success', $added > 0 ? "Missing ingredients added to the shopping list." : "All ingredients are available in stock.");
            }

            /* 2) Kézzel megadott új tétel felvétele */
            if ($action === 'add') {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'quantity' => 'nullable',
                    'unit' => 'nullable|string|max:50',
                    'note' => 'nullable|string',
                    'location' => 'required|in:auto,fridge,freezer,pantry',
                ]);

                $name = trim((string)$request->input('name'));
                $quantityRaw = str_replace(',', '.', (string)$request->input('quantity', '1'));
                $quantity = is_numeric($quantityRaw) ? (float)$quantityRaw : 1.0;
                if ($quantity <= 0) $quantity = 1.0;

                $unit = trim((string)$request->input('unit', ''));
                $note = trim((string)$request->input('note', ''));
                $location = (string)$request->input('location', 'auto');
                if ($location === 'auto') {
                    // Auto módban nem a felhasználónak kell megmondania, hova kerüljön majd a készletben.
                    $location = $this->guessLocationForItem($name);
                }

                DB::table('shopping_list_items')->insert([
                    'household_id' => $hid,
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit !== '' ? $unit : null,
                    'note' => $note !== '' ? $note : null,
                    'location' => $location,
                    'created_by' => $userId,
                ]);

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Added to the list.');
            }

            /* 3) Megvett/visszavont állapot és megvételkor inventory upsert */
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
                    // A checkbox-szerű állapotváltás és az inventory frissítés egy tranzakció,
                    // így nem fordulhat elő, hogy megvettnek látszik, de nem kerül készletbe.
                    DB::transaction(function () use ($to, $userId, $id, $hid, $item) {
                        DB::update("
                            UPDATE shopping_list_items
                            SET is_bought = ?,
                                bought_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE NULL END,
                                bought_by = CASE WHEN ? = 1 THEN ? ELSE NULL END
                            WHERE id = ? AND household_id = ?
                        ", [$to, $to, $to, $userId, $id, $hid]);

                        // Ha visszavonjuk a vásárlást, csak a shopping státuszt állítjuk vissza.
                        // A korábban inventoryba tett mennyiséget nem vonjuk automatikusan vissza.
                        if ($to !== 1) return;

                        $name = (string)$item->name;
                        $qty  = (float)$item->quantity;
                        $unit = $item->unit !== null ? (string)$item->unit : null;
                        $note = $item->note !== null ? (string)$item->note : null;
                        $loc  = in_array((string)$item->location, ['fridge','freezer','pantry'], true) ? (string)$item->location : 'pantry';
                        $expiresAt = $this->averageExpiryDateForItem($name, $loc);

                        // Egység- és helyérzékeny upsert: azonos termék + azonos unit + azonos hely
                        // esetén mennyiséget növelünk, különben új inventory sort hozunk létre.
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
                                SET quantity = quantity + ?,
                                    expires_at = COALESCE(expires_at, ?)
                                WHERE id = ? AND household_id = ?
                            ", [$qty, $expiresAt, (int)$existing->id, $hid]);
                        } else {
                            DB::table('inventory_items')->insert([
                                'household_id' => $hid,
                                'name' => $name,
                                'category' => null,
                                'location' => $loc,
                                'quantity' => $qty,
                                'unit' => $unit,
                                'expires_at' => $expiresAt,
                                'note' => $note,
                            ]);
                        }
                    });
                }

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'Saved.');
            }

            /* 4) Egy shopping tétel törlése */
            if ($action === 'delete') {
                $id = (int)$request->input('id', 0);
                DB::delete("DELETE FROM shopping_list_items WHERE id = ? AND household_id = ?", [$id, $hid]);
                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'TĂ¶rĂ¶lve.');
            }

            /* 5) Megvett tételek törlése */
            if ($action === 'clear_bought') {
                // A régebbi adatoknál előfordulhat, hogy bought_at már ki van töltve,
                // ezért nem csak is_bought alapján törlünk.
                $deleted = DB::delete("
                    DELETE FROM shopping_list_items
                    WHERE household_id = ?
                      AND (is_bought = 1 OR bought_at IS NOT NULL)
                ", [$hid]);

                $message = $deleted > 0
                    ? $deleted . ' purchased item(s) deleted.'
                    : 'There were no purchased items to delete.';

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', $message);
            }

            /* 6) Teljes bevásárlólista törlése */
            if ($action === 'clear_all') {
                $deleted = DB::delete("DELETE FROM shopping_list_items WHERE household_id = ?", [$hid]);

                $message = $deleted > 0
                    ? $deleted . ' shopping list item(s) deleted.'
                    : 'The shopping list was already empty.';

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', $message);
            }

            /* 7) Minden tétel megvásárlása és inventoryba vezetése */
            if ($action === 'buy_all') {
                // Csak a még nem megvett tételeket dolgozzuk fel, hogy ismételt kattintásra
                // ne duplázódjon a készlet.
                $toBuy = DB::select("
                    SELECT id, name, quantity, unit, note, location
                    FROM shopping_list_items
                    WHERE household_id = ? AND is_bought = 0
                    ORDER BY id ASC
                ", [$hid]);

                if (empty($toBuy)) {
                    return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'There are no items to buy.');
                }

                DB::transaction(function () use ($toBuy, $userId, $hid) {
                    foreach ($toBuy as $item) {
                        $id   = (int)$item->id;
                        $name = (string)$item->name;
                        $qty  = (float)$item->quantity;
                        $unit = $item->unit !== null ? (string)$item->unit : null;
                        $note = $item->note !== null ? (string)$item->note : null;
                        $loc  = in_array((string)$item->location, ['fridge','freezer','pantry'], true) ? (string)$item->location : 'pantry';
                        $expiresAt = $this->averageExpiryDateForItem($name, $loc);

                        // Előbb a shopping tételt jelöljük megvettnek, majd ugyanabban a tranzakcióban
                        // inventory upsertet végzünk.
                        DB::update("
                            UPDATE shopping_list_items
                            SET is_bought = 1,
                                bought_at = CURRENT_TIMESTAMP,
                                bought_by = ?
                            WHERE id = ? AND household_id = ?
                        ", [$userId, $id, $hid]);

                        if ($unit !== null && trim($unit) !== '') {
                            // Azonos egységnél mennyiséget összevonunk, eltérő egységnél külön sort hagyunk.
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
                                SET quantity = quantity + ?,
                                    expires_at = COALESCE(expires_at, ?)
                                WHERE id = ? AND household_id = ?
                            ", [$qty, $expiresAt, (int)$existing->id, $hid]);
                        } else {
                            DB::table('inventory_items')->insert([
                                'household_id' => $hid,
                                'name' => $name,
                                'category' => null,
                                'location' => $loc,
                                'quantity' => $qty,
                                'unit' => $unit,
                                'expires_at' => $expiresAt,
                                'note' => $note,
                            ]);
                        }
                    }
                });

                return redirect()->route('shopping.index', ['hid' => $hid])->with('success', 'All items purchased and added to inventory.');
            }

            return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Ismeretlen mĹ±velet.']);
        } catch (\Throwable $e) {
            return redirect()->route('shopping.index', ['hid' => $hid])->withErrors(['Error: ' . $e->getMessage()]);
        }
    }
}
