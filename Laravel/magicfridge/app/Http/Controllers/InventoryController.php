<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function getHouseholdsForUser(int $userId): array
    {
        // owner + tagság
        $rows = DB::select("
            SELECT h.id AS household_id, h.name
            FROM households h
            WHERE h.owner_id = ?

            UNION

            SELECT h.id AS household_id, h.name
            FROM household_members hm
            JOIN households h ON h.id = hm.household_id
            WHERE hm.member_id = ?
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
    }

    public function create(Request $request)
    {
        $userId = (int)session('user_id');
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
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int)session('user_id');
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
    }

    public function list(Request $request)
    {
        $userId = (int)session('user_id');
        $households = $this->getHouseholdsForUser($userId);

        if (empty($households)) {
            return redirect()->route('households.index')
                ->withErrors(['Előbb hozz létre egy háztartást vagy fogadj el egy meghívást.']);
        }

        $hid = $this->resolveHouseholdId($request, $households);

        $q = trim((string)$request->query('q', ''));
        $loc = trim((string)$request->query('loc', ''));

        $params = [$hid];
        $where = "WHERE household_id = ?";

        if ($q !== '') {
            $where .= " AND name LIKE ?";
            $params[] = "%{$q}%";
        }
        if ($loc !== '' && in_array($loc, ['fridge', 'freezer', 'pantry'], true)) {
            $where .= " AND location = ?";
            $params[] = $loc;
        }

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
            'q' => $q,
            'loc' => $loc,
            'items' => $items,
        ]);
    }

    public function listPost(Request $request)
    {
        $userId = (int)session('user_id');
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
    }
}
