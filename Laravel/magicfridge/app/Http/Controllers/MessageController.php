<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
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

    private function ensureMember(int $userId, int $householdId): bool
    {
        $row = DB::selectOne("
            SELECT id
            FROM household_members
            WHERE household_id = ? AND member_id = ?
            LIMIT 1
        ", [$householdId, $userId]);

        return (bool) $row;
    }

    public function create(Request $request)
    {
        $userId = (int) session('user_id');
        $households = $this->householdsForUser($userId);

        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Nincs háztartásod. Hozz létre egyet vagy fogadj el meghívást.']);
        }

        // kiválasztott háztartás (GET ?hid=)
        $householdId = (int) $request->query('hid', $households[0]->household_id);

        // ha olyan hid jött, amihez nincs joga: vissza az elsőre
        if (!$this->ensureMember($userId, $householdId)) {
            $householdId = (int) $households[0]->household_id;
        }

        $householdName = '';
        foreach ($households as $h) {
            if ((int)$h->household_id === $householdId) {
                $householdName = (string)$h->name;
                break;
            }
        }

        return view('inventory.create', [
            'householdId' => $householdId,
            'householdName' => $householdName,
            'households' => $households, // objektum lista
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

        $hid = (int) $request->input('hid');

        if (!$this->ensureMember($userId, $hid)) {
            return back()->withErrors(['Nincs jogosultságod ehhez a háztartáshoz.']);
        }

        DB::table('inventory_items')->insert([
            'household_id' => $hid,
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'location' => $request->input('location'),
            'quantity' => $request->input('quantity', 1),
            'unit' => $request->input('unit'),
            'expires_at' => $request->input('expires_at'),
            'note' => $request->input('note'),
            'created_at' => now(),
            'updated_at' => now(),
            'expired_notified' => 0, // ha van ilyen oszlop nálad
        ]);

        return redirect()->route('inventory.list', ['hid' => $hid])
            ->with('success', 'Termék hozzáadva a raktárhoz.');
    }

    public function list(Request $request)
    {
        $userId = (int) session('user_id');
        $households = $this->householdsForUser($userId);

        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Nincs háztartásod.']);
        }

        $householdId = (int) $request->query('hid', $households[0]->household_id);
        if (!$this->ensureMember($userId, $householdId)) {
            $householdId = (int) $households[0]->household_id;
        }

        $q = trim((string) $request->query('q', ''));
        $loc = trim((string) $request->query('loc', ''));

        $params = [$householdId];
        $where = " WHERE household_id = ? ";

        if ($q !== '') {
            $where .= " AND name LIKE ? ";
            $params[] = "%{$q}%";
        }
        if (in_array($loc, ['fridge','freezer','pantry'], true)) {
            $where .= " AND location = ? ";
            $params[] = $loc;
        }

        $items = DB::select("
            SELECT *
            FROM inventory_items
            {$where}
            ORDER BY id DESC
        ", $params);

        $householdName = '';
        foreach ($households as $h) {
            if ((int)$h->household_id === $householdId) {
                $householdName = (string)$h->name;
                break;
            }
        }

        return view('inventory.list', [
            'householdId' => $householdId,
            'householdName' => $householdName,
            'households' => $households,
            'q' => $q,
            'loc' => $loc,
            'items' => $items,
        ]);
    }

    public function listPost(Request $request)
    {
        // ezt hagyhatjuk a mentés/törlés logikád szerint (már működik nálad)
        return back()->with('success', 'Művelet OK.');
    }
}
