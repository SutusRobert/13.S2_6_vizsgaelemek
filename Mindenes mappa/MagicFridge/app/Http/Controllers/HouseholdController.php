<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseholdController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) session('user_id');
        $fullName = (string) (session('full_name') ?? 'Felhasználó');

        // 1) ha van saját (owner) háztartás
        $household = DB::selectOne("SELECT * FROM households WHERE owner_id = ? LIMIT 1", [$userId]);

        // 2) ha nincs saját, tagként keressünk
        if (!$household) {
            $household = DB::selectOne("
                SELECT h.*
                FROM household_members hm
                JOIN households h ON h.id = hm.household_id
                WHERE hm.member_id = ?
                LIMIT 1
            ", [$userId]);
        }

        // 3) ha még mindig nincs: hozzuk létre
        if (!$household) {
            DB::table('households')->insert([
                'owner_id' => $userId,
                'name' => $fullName . ' háztartása',
            ]);
            $householdId = (int) DB::getPdo()->lastInsertId();

            // tulaj admin tag
            DB::table('household_members')->insert([
                'household_id' => $householdId,
                'member_id' => $userId,
                'role' => 'tag',

            ]);

            $household = DB::selectOne("SELECT * FROM households WHERE id = ? LIMIT 1", [$householdId]);
        }

        // tagok
        $members = DB::select("
            SELECT hm.id AS hm_id, u.id AS user_id, u.full_name, hm.role
            FROM household_members hm
            JOIN users u ON hm.member_id = u.id
            WHERE hm.household_id = ?
            ORDER BY u.full_name
        ", [(int)$household->id]);

        return view('households.index', [
            'household' => $household,
            'members' => $members,
        ]);
    }

    public function invite(Request $request)
    {
        $userId = (int) session('user_id');
        $email = trim((string)$request->input('email', ''));

        if ($email === '') {
            return redirect()->route('households.index')->withErrors(['Adj meg egy email címet.']);
        }

        // csak az owner háztartásából engedünk meghívni (mint a régi)
        $household = DB::selectOne("SELECT id, name FROM households WHERE owner_id = ? LIMIT 1", [$userId]);
        if (!$household) {
            return redirect()->route('households.index')->withErrors(['Nincs háztartás.']);
        }

        $user = DB::selectOne("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1", [$email]);
        if (!$user) {
            return redirect()->route('households.index')->withErrors(['Nincs ilyen emaillel regisztrált felhasználó.']);
        }

        if ((int)$user->id === $userId) {
            return redirect()->route('households.index')->withErrors(['Magadat nem tudod meghívni.']);
        }

        // már tag?
        $exists = DB::selectOne("
            SELECT id FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1
        ", [(int)$household->id, (int)$user->id]);

        if ($exists) {
            return redirect()->route('households.index')->withErrors(['Ez a felhasználó már tag.']);
        }

        // pending invite?
        $pending = DB::selectOne("
            SELECT id FROM household_invites
            WHERE household_id = ? AND invited_user_id = ? AND status = 'pending'
            LIMIT 1
        ", [(int)$household->id, (int)$user->id]);

        if ($pending) {
            return redirect()->route('households.index')->withErrors(['Már van függőben lévő meghívó ennek a felhasználónak.']);
        }

        // invite létrehozása
        DB::table('household_invites')->insert([
            'household_id' => (int)$household->id,
            'invited_user_id' => (int)$user->id,
            'invited_by_user_id' => $userId,
            'status' => 'pending',
        ]);
        $inviteId = (int) DB::getPdo()->lastInsertId();

        // message létrehozása (pont mint a régi)
        $inviterName = (string) (session('full_name') ?? 'Valaki');
        $title = "Háztartás meghívó";
        $body  = $inviterName . " meghívott a(z) \"" . $household->name . "\" háztartásba.";

        DB::table('messages')->insert([
            'user_id' => (int)$user->id,
            'type' => 'info',
            'title' => $title,
            'body' => $body,
            'link_url' => "invite:" . $inviteId,
            'is_read' => 0,
        ]);

        return redirect()->route('households.index')->with('success', 'Meghívó elküldve: ' . $email);
    }

    public function toggleRole(Request $request)
    {
        $userId = (int) session('user_id');
        $hmId = (int) $request->input('hm_id', 0);

        if (!$hmId) {
            return redirect()->route('households.index')->withErrors(['Hiányzó azonosító.']);
        }

        $row = DB::selectOne("
            SELECT hm.id, hm.role, h.owner_id
            FROM household_members hm
            JOIN households h ON hm.household_id = h.id
            WHERE hm.id = ?
            LIMIT 1
        ", [$hmId]);

        if (!$row || (int)$row->owner_id !== $userId) {
            return redirect()->route('households.index')->withErrors(['Nincs jogosultság.']);
        }

        $newRole = ($row->role === 'alap felhasználó') ? 'tag' : 'alap felhasználó';


        $newRole = ($row->role === 'alap felhasználó') ? 'tag' : 'alap felhasználó';

        DB::update("UPDATE household_members SET role = ? WHERE id = ?", [$newRole, $hmId]);

        return redirect()->route('households.index')->with('success', 'Rang frissítve.');
    }
}
