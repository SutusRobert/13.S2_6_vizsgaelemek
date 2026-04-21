<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function householdsForUser(int $userId): array
    {
        // Csak azokat a háztartásokat adjuk vissza, ahol a felhasználó tag;
        // az inventory minden későbbi lekérdezése erre a határra épül.
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
        // Központi jogosultságellenőrzés inventory műveletekhez.
        // A household_id szerinti szűrés védi más háztartásának készletét.
        $ok = DB::selectOne("
            SELECT id
            FROM household_members
            WHERE member_id = ? AND household_id = ?
            LIMIT 1
        ", [$userId, $hid]);

        if (!$ok) abort(403, 'You do not have permission for this household.');
    }

    /**
    * Ha nincs háztartás, létrehozunk egy alapértelmezettet és tagként hozzáadjuk a felhasználót.
    * Így az /inventory oldal nem akad el háztartás nélkül.
     */
    private function ensureDefaultHousehold(int $userId): void
    {
        $households = $this->householdsForUser($userId);
        if (!empty($households)) return;

        // Először a sessionből próbálunk nevet olvasni, majd fallbackként a users táblából.
        $fullName = (string)(session('full_name') ?? session('user_name') ?? '');

        if ($fullName === '') {
            // if missing from session, try users table (safe fallback)
            $u = DB::selectOne("SELECT full_name, name FROM users WHERE id = ? LIMIT 1", [$userId]);
            $fullName = (string)($u->full_name ?? ($u->name ?? 'User'));
        }

        $householdName = trim($fullName) !== '' ? ($fullName . ' household') : 'Household';

        DB::table('households')->insert([
            'owner_id' => $userId,
            'name' => $householdName,
        ]);

        $hid = (int) DB::getPdo()->lastInsertId();

        DB::table('household_members')->insert([
            'household_id' => $hid,
            'member_id' => $userId,
            'role' => 'tag', // valid by DB enum
        ]);
    }

    public function create(Request $request)
    {
        $userId = (int) session('user_id');

        // Nem irányítunk át háztartás oldalra: ha kell, itt hozzuk létre az alap háztartást.
        $this->ensureDefaultHousehold($userId);

        $households = $this->householdsForUser($userId);

        $hid = (int) ($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        // A megjelenítendő háztartásnevet a már betöltött listából keressük ki,
        // így nem kell külön adatbázis-lekérdezés.
        $householdName = '';
        foreach ($households as $h) {
            if ((int)$h->household_id === $hid) $householdName = (string)$h->name;
        }

        return view('inventory.create', [
            'householdId' => $hid,
            'householdName' => $householdName,
            'households' => array_map(
                fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name],
                $households
            ),
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'hid' => 'required|integer',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'required|in:fridge,freezer,pantry',
            'quantity' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
            'note' => 'nullable|string|max:255',
        ]);

        $hid = (int)$request->input('hid');
        $this->assertMember($userId, $hid);

        DB::table('inventory_items')->insert([
            'household_id' => $hid,
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'location' => $request->input('location'),
            'quantity' => $request->input('quantity') ?? 1,
            'unit' => $request->input('unit'),
            'expires_at' => $request->input('expires_at'),
            'note' => $request->input('note'),
            'expired_notified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->route('inventory.list', ['hid' => $hid])->with('success', 'Item added succesfully .');
    }

    public function list(Request $request)
    {
        $userId = (int) session('user_id');

        // A listaoldal is önjavító: háztartás hiányában létrehozza az alapértelmezettet.
        $this->ensureDefaultHousehold($userId);

        $households = $this->householdsForUser($userId);

        $hid = (int) ($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)$households[0]->household_id;

        $this->assertMember($userId, $hid);

        $q = trim((string)$request->query('q', ''));
        $loc = trim((string)$request->query('loc', ''));

        // A lista SQL-t lépésenként építjük fel, így a keresés és a hely szűrés
        // bármilyen kombinációban működik külön SQL ágak ismétlése nélkül.
        $sql = "SELECT * FROM inventory_items WHERE household_id = ?";
        $params = [$hid];

        if ($q !== '') {
            $sql .= " AND name LIKE ?";
            $params[] = "%$q%";
        }
        if ($loc !== '') {
            $sql .= " AND location = ?";
            $params[] = $loc;
        }

        $sql .= " ORDER BY id DESC";
        $items = DB::select($sql, $params);

        $householdName = '';
        foreach ($households as $h) {
            if ((int)$h->household_id === $hid) $householdName = (string)$h->name;
        }

        return view('inventory.list', [
            'householdId' => $hid,
            'householdName' => $householdName,
            'households' => array_map(
                fn($h) => ['household_id' => (int)$h->household_id, 'name' => (string)$h->name],
                $households
            ),
            'q' => $q,
            'loc' => $loc,
            'items' => $items,
        ]);
    }

    public function listPost(Request $request)
    {
        $userId = (int) session('user_id');
        $hid = (int) $request->input('hid', 0);
        $action = (string) $request->input('action', '');

        if ($hid <= 0) {
            return redirect()->route('inventory.list')->withErrors(['Missing household.']);
        }

        $this->assertMember($userId, $hid);

        $redirectParams = ['hid' => $hid];
        $q = trim((string) $request->input('q', ''));
        $loc = trim((string) $request->input('loc', ''));
        // Frissítés/törlés után megtartjuk az aktuális szűrőket, hogy a felhasználó
        // ugyanabba a listanézetbe térjen vissza.
        if ($q !== '') $redirectParams['q'] = $q;
        if ($loc !== '') $redirectParams['loc'] = $loc;

        if ($action === 'delete_all') {
            DB::delete("DELETE FROM inventory_items WHERE household_id = ?", [$hid]);

            return redirect()->route('inventory.list', ['hid' => $hid])
                ->with('success', 'All inventory items deleted.');
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return redirect()->route('inventory.list', $redirectParams)->withErrors(['Missing item.']);
        }

        if ($action === 'delete') {
            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$id, $hid]);

            return redirect()->route('inventory.list', $redirectParams)
                ->with('success', 'Item deleted.');
        }

        if ($action === 'update') {
            $request->validate([
                'location' => 'required|in:fridge,freezer,pantry',
                'quantity' => 'nullable|numeric',
                'expires_at' => 'nullable|date',
            ]);

            // Itt csak a gyorsan szerkeszthető készletmezőket módosítjuk.
            // A név/egység átírását nem engedjük inline, mert az megváltoztatná a termék azonosságát.
            DB::update(
                "UPDATE inventory_items
                 SET location = ?, quantity = ?, expires_at = ?, updated_at = ?
                 WHERE id = ? AND household_id = ?",
                [
                    $request->input('location'),
                    $request->input('quantity') ?? 1,
                    $request->input('expires_at') ?: null,
                    date('Y-m-d H:i:s'),
                    $id,
                    $hid,
                ]
            );

            return redirect()->route('inventory.list', $redirectParams)
                ->with('success', 'Item updated.');
        }

        return redirect()->route('inventory.list', $redirectParams)->withErrors(['Unknown action.']);
    }
}
