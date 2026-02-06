<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function getHouseholdsForUser(int $userId): array
    {
        $rows = DB::select("
            SELECT id AS household_id, name FROM households WHERE owner_id = ?
            UNION
            SELECT h.id AS household_id, h.name
            FROM household_members hm
            JOIN households h ON h.id = hm.household_id
            WHERE hm.member_id = ?
            ORDER BY household_id ASC
        ", [$userId, $userId]);

        $households = array_map(fn($r) => [
            'household_id' => (int)$r->household_id,
            'name' => (string)$r->name,
        ], $rows);

        $map = [];
        foreach ($households as $h) $map[$h['household_id']] = $h['name'];

        return [$households, $map];
    }

    private function resolveHouseholdId(Request $request, array $households, array $map): int
    {
        $hid = (int)($request->query('hid') ?? $request->input('hid') ?? ($households[0]['household_id'] ?? 0));
        if (!$hid || !isset($map[$hid])) {
            $hid = (int)$households[0]['household_id'];
        }
        return $hid;
    }

    public function create(Request $request)
    {
        $userId = (int)session('user_id');
        [$households, $map] = $this->getHouseholdsForUser($userId);

        if (!$households) {
    return redirect()
        ->route('dashboard')
        ->withErrors(['Nincs háztartásod még. Először hozz létre egyet (Households modul).']);
}


        $householdId = $this->resolveHouseholdId($request, $households, $map);

        return view('inventory.create', [
            'households' => $households,
            'householdId' => $householdId,
            'householdName' => $map[$householdId] ?? '',
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int)session('user_id');
        [$households, $map] = $this->getHouseholdsForUser($userId);
        if (!$households) return redirect()->route('inventory.create');

        $householdId = $this->resolveHouseholdId($request, $households, $map);

        $location = (string)$request->input('location', 'pantry');
        if (!in_array($location, ['fridge','pantry','freezer'], true)) $location = 'pantry';

        $quantityRaw = str_replace(',', '.', (string)$request->input('quantity', '1'));
        $quantity = is_numeric($quantityRaw) ? (float)$quantityRaw : 0;

        $request->validate([
            'name' => ['required','string','max:255'],
            'category' => ['nullable','string','max:255'],
            'unit' => ['nullable','string','max:50'],
            'note' => ['nullable','string','max:255'],
            'expires_at' => ['nullable','date'],
        ]);

        if ($quantity <= 0) {
            return back()->withErrors(['quantity' => 'A mennyiség legyen pozitív szám.'])->withInput();
        }

        DB::table('inventory_items')->insert([
            'household_id' => $householdId,
            'name' => trim((string)$request->input('name')),
            'category' => trim((string)$request->input('category')) ?: null,
            'location' => $location,
            'quantity' => $quantity,
            'unit' => trim((string)$request->input('unit')) ?: null,
            'expires_at' => $request->input('expires_at') ?: null,
            'note' => trim((string)$request->input('note')) ?: null,
        ]);

        return redirect()
            ->route('inventory.create', ['hid' => $householdId])
            ->with('success', 'Hozzáadva.');
    }

    public function list(Request $request)
    {
        $userId = (int)session('user_id');
        [$households, $map] = $this->getHouseholdsForUser($userId);
        if (!$households) return redirect()->route('households.index');

        $householdId = $this->resolveHouseholdId($request, $households, $map);

        $q = trim((string)$request->query('q', ''));
        $loc = trim((string)$request->query('loc', ''));

        $where = "household_id = ?";
        $params = [$householdId];

        if ($q !== '') {
            $where .= " AND name LIKE ?";
            $params[] = "%{$q}%";
        }
        if (in_array($loc, ['fridge','pantry','freezer'], true)) {
            $where .= " AND location = ?";
            $params[] = $loc;
        }

        $items = DB::select("SELECT * FROM inventory_items WHERE {$where} ORDER BY expires_at IS NULL, expires_at ASC, id DESC", $params);

        return view('inventory.list', [
            'households' => $households,
            'householdId' => $householdId,
            'householdName' => $map[$householdId] ?? '',
            'q' => $q,
            'loc' => $loc,
            'items' => $items,
        ]);
    }

    public function listPost(Request $request)
    {
        $userId = (int)session('user_id');
        [$households, $map] = $this->getHouseholdsForUser($userId);
        if (!$households) return redirect()->route('inventory.list');

        $householdId = (int)$request->input('hid', 0);
        if (!$householdId || !isset($map[$householdId])) {
            $householdId = (int)$households[0]['household_id'];
        }

        $action = (string)$request->input('action', '');
        $id = (int)$request->input('id', 0);

        $q = trim((string)$request->input('q', ''));
        $loc = trim((string)$request->input('loc', ''));

        $errors = [];

        if ($action === 'update') {
            $location = (string)$request->input('location', 'pantry');
            if (!in_array($location, ['fridge','pantry','freezer'], true)) $location = 'pantry';

            $quantityRaw = str_replace(',', '.', (string)$request->input('quantity', '1'));
            $quantity = is_numeric($quantityRaw) ? (float)$quantityRaw : 0;

            $expires = $request->input('expires_at') ?: null;

            if ($quantity <= 0) $errors[] = "A mennyiség legyen pozitív szám.";

            if (!$errors) {
                DB::update("
                    UPDATE inventory_items
                    SET location = ?, quantity = ?, expires_at = ?
                    WHERE id = ? AND household_id = ?
                ", [$location, $quantity, $expires, $id, $householdId]);
            }
        }

        if ($action === 'delete') {
            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$id, $householdId]);
        }

        // redirect: tartsa meg a szűrőket
        $redirParams = ['hid' => $householdId];
        if ($q !== '') $redirParams['q'] = $q;
        if ($loc !== '') $redirParams['loc'] = $loc;

        if ($errors) {
            return redirect()->route('inventory.list', $redirParams)->withErrors($errors);
        }

        $msg = $action === 'delete' ? 'Törölve.' : 'Mentve.';
        return redirect()->route('inventory.list', $redirParams)->with('success', $msg);
    }
}
