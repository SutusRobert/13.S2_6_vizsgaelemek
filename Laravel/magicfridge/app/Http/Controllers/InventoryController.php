<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function getHouseholdsForUser(int $userId): array
    {
<<<<<<< HEAD
        // owner + tagság
        $rows = DB::select("
            SELECT h.id AS household_id, h.name
            FROM households h
            WHERE h.owner_id = ?

            UNION

=======
        $rows = DB::select("
            SELECT id AS household_id, name FROM households WHERE owner_id = ?
            UNION
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
            SELECT h.id AS household_id, h.name
            FROM household_members hm
            JOIN households h ON h.id = hm.household_id
            WHERE hm.member_id = ?
<<<<<<< HEAD
        ", [$userId, $userId]);

        // normalizálás
        $households = [];
        foreach ($rows as $r) {
            $households[] = [
                'household_id' => (int)$r->household_id,
                'name' => (string)$r->name,
            ];
        }

        // egyediség biztosra
        $uniq = [];
        foreach ($households as $h) $uniq[$h['household_id']] = $h;
        return array_values($uniq);
    }

    private function resolveHouseholdId(Request $request, array $households): int
    {
        $hid = (int)($request->query('hid') ?? 0);
        if ($hid <= 0) $hid = (int)(session('household_id') ?? 0);

        $allowed = array_map(fn($h) => (int)$h['household_id'], $households);

        if ($hid > 0 && in_array($hid, $allowed, true)) {
            session(['household_id' => $hid]);
            return $hid;
        }

        // fallback: első elérhető
        $first = $households[0]['household_id'] ?? 0;
        if ($first > 0) {
            session(['household_id' => $first]);
            return (int)$first;
        }

        return 0;
    }

    private function householdNameById(array $households, int $hid): string
    {
        foreach ($households as $h) {
            if ((int)$h['household_id'] === $hid) return (string)$h['name'];
        }
        return '';
=======
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
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
    }

    public function create(Request $request)
    {
        $userId = (int)session('user_id');
<<<<<<< HEAD
        $households = $this->getHouseholdsForUser($userId);

        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Előbb hozz létre egy háztartást vagy fogadj el egy meghívást.']);
        }

        $householdId = $this->resolveHouseholdId($request, $households);

        return view('inventory.create', [
            'householdId' => $householdId,
            'householdName' => $this->householdNameById($households, $householdId),
            'households' => $households,
=======
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
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int)session('user_id');
<<<<<<< HEAD
        $households = $this->getHouseholdsForUser($userId);

        if (empty($households)) {
            return back()->withErrors(['Nincs háztartásod.']);
        }

        $request->validate([
            'hid' => 'required|integer',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'required|in:fridge,freezer,pantry',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);

        $hid = (int)$request->input('hid');

        // jogosultság ellenőrzés
        $allowed = array_map(fn($h) => (int)$h['household_id'], $households);
        if (!in_array($hid, $allowed, true)) {
            return back()->withErrors(['Ehhez a háztartáshoz nincs hozzáférésed.']);
        }

        DB::table('inventory_items')->insert([
            'household_id' => $hid,
            'name' => (string)$request->input('name'),
            'category' => $request->input('category'),
            'location' => (string)$request->input('location'),
            'quantity' => (float)$request->input('quantity'),
            'unit' => $request->input('unit'),
            'expires_at' => $request->input('expires_at'),
            'note' => $request->input('note'),
            'expired_notified' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session(['household_id' => $hid]);

        return redirect()->route('inventory.list', ['hid' => $hid])
            ->with('success', 'Termék hozzáadva a raktárhoz.');
=======
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
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
    }

    public function list(Request $request)
    {
        $userId = (int)session('user_id');
<<<<<<< HEAD
        $households = $this->getHouseholdsForUser($userId);

        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Előbb hozz létre egy háztartást vagy fogadj el egy meghívást.']);
        }

        $hid = $this->resolveHouseholdId($request, $households);
=======
        [$households, $map] = $this->getHouseholdsForUser($userId);
        if (!$households) return redirect()->route('households.index');

        $householdId = $this->resolveHouseholdId($request, $households, $map);
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7

        $q = trim((string)$request->query('q', ''));
        $loc = trim((string)$request->query('loc', ''));

<<<<<<< HEAD
        $params = [$hid];
        $where = "WHERE household_id = ?";
=======
        $where = "household_id = ?";
        $params = [$householdId];
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7

        if ($q !== '') {
            $where .= " AND name LIKE ?";
            $params[] = "%{$q}%";
        }
<<<<<<< HEAD
        if ($loc !== '' && in_array($loc, ['fridge', 'freezer', 'pantry'], true)) {
=======
        if (in_array($loc, ['fridge','pantry','freezer'], true)) {
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
            $where .= " AND location = ?";
            $params[] = $loc;
        }

<<<<<<< HEAD
        $items = DB::select("
            SELECT id, household_id, name, category, location, quantity, unit, expires_at, note
            FROM inventory_items
            {$where}
            ORDER BY
                CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END,
                expires_at ASC,
                id DESC
        ", $params);

        return view('inventory.list', [
            'householdId' => $hid,
            'householdName' => $this->householdNameById($households, $hid),
            'households' => $households,
=======
        $items = DB::select("SELECT * FROM inventory_items WHERE {$where} ORDER BY expires_at IS NULL, expires_at ASC, id DESC", $params);

        return view('inventory.list', [
            'households' => $households,
            'householdId' => $householdId,
            'householdName' => $map[$householdId] ?? '',
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
            'q' => $q,
            'loc' => $loc,
            'items' => $items,
        ]);
    }

    public function listPost(Request $request)
    {
        $userId = (int)session('user_id');
<<<<<<< HEAD
        $households = $this->getHouseholdsForUser($userId);
        $allowed = array_map(fn($h) => (int)$h['household_id'], $households);

        $request->validate([
            'action' => 'required|in:update,delete,filter,reset',
            'hid' => 'required|integer',
        ]);

        $hid = (int)$request->input('hid');
        if (!in_array($hid, $allowed, true)) {
            return back()->withErrors(['Ehhez a háztartáshoz nincs hozzáférésed.']);
        }

        session(['household_id' => $hid]);

        $action = (string)$request->input('action');

        if ($action === 'filter') {
            $q = trim((string)$request->input('q', ''));
            $loc = trim((string)$request->input('loc', ''));
            return redirect()->route('inventory.list', ['hid' => $hid, 'q' => $q, 'loc' => $loc]);
        }

        if ($action === 'reset') {
            return redirect()->route('inventory.list', ['hid' => $hid]);
        }

        if ($action === 'delete') {
            $request->validate(['id' => 'required|integer']);
            $id = (int)$request->input('id');

            // csak a saját háztartásból törölhet
            DB::delete("DELETE FROM inventory_items WHERE id = ? AND household_id = ?", [$id, $hid]);

            return back()->with('success', 'Törölve.');
        }

        // update
        $request->validate([
            'id' => 'required|integer',
            'location' => 'required|in:fridge,freezer,pantry',
            'quantity' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
        ]);

        $id = (int)$request->input('id');

        DB::update("
            UPDATE inventory_items
            SET location = ?, quantity = ?, expires_at = ?, updated_at = ?
            WHERE id = ? AND household_id = ?
        ", [
            (string)$request->input('location'),
            (float)$request->input('quantity'),
            $request->input('expires_at'),
            now(),
            $id,
            $hid
        ]);

        return back()->with('success', 'Mentve.');
=======
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
>>>>>>> 81242963927eb215250866a44ca43f844f7085d7
    }
}
