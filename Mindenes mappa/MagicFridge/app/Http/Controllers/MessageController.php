<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        $userId = (int) session('user_id');

        // 1) lejáró / lejárt cuccokból automata üzenet generálás
        $this->createExpiryMessagesForUser($userId);

        // 2) üzenetek listázása
        $messages = DB::select("
            SELECT *
            FROM messages
            WHERE user_id = ?
            ORDER BY is_read ASC, id DESC
        ", [$userId]);

        return view('messages.index', [
            'messages' => $messages
        ]);
    }

    private function createExpiryMessagesForUser(int $userId): void
    {
        // user háztartásai
        $households = DB::select("
            SELECT hm.household_id
            FROM household_members hm
            WHERE hm.member_id = ?
        ", [$userId]);

        if (empty($households)) return;

        $hidList = array_map(fn($r) => (int)$r->household_id, $households);

        // IN (?, ?, ?) placeholder
        $in = implode(',', array_fill(0, count($hidList), '?'));

        // lejárt vagy 2 napon belül lejár
        $items = DB::select("
            SELECT id, household_id, name, expires_at
            FROM inventory_items
            WHERE expires_at IS NOT NULL
              AND expires_at <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
              AND household_id IN ($in)
              AND expired_notified = 0
            ORDER BY expires_at ASC
        ", $hidList);

        foreach ($items as $it) {
            $dateStr = (string)$it->expires_at;

            $title = 'Lejárat';
            $body  = "Lejár/lejárt: {$it->name} (dátum: {$dateStr}).";

            DB::table('messages')->insert([
                'user_id'     => $userId,
                'title'       => $title,
                'body'        => $body,
                'link_url'    => 'inventory:'.$it->household_id, // csak jelzés (ha akarod később kattinthatóvá)
                'is_read'     => 0,
                'created_at'  => now(),
            ]);

            DB::update("UPDATE inventory_items SET expired_notified = 1 WHERE id = ?", [(int)$it->id]);
        }
    }

    public function markRead(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $userId = (int) session('user_id');

        DB::update(
            "UPDATE messages SET is_read=1 WHERE id = ? AND user_id = ?",
            [(int)$request->id, $userId]
        );

        return back()->with('success', 'Üzenet megjelölve olvasottként.');
    }

    public function delete(Request $request)
{
    $request->validate(['id' => 'required|integer']);
    $userId = (int) session('user_id');

    DB::delete(
        "DELETE FROM messages WHERE id = ? AND user_id = ?",
        [(int)$request->id, $userId]
    );

    return back()->with('success', 'Üzenet eltüntetve.');
}


    public function respondInvite(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'action' => 'required|in:accept,decline'
        ]);

        $userId = (int) session('user_id');
        $msgId = (int) $request->input('id');
        $action = (string) $request->input('action');

        $msg = DB::selectOne("
            SELECT * FROM messages
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ", [$msgId, $userId]);

        if (!$msg) return back()->withErrors(['Az üzenet nem található.']);

        $link = (string)($msg->link_url ?? '');
        if (!str_starts_with($link, 'invite:')) {
            return back()->withErrors(['Ez az üzenet nem meghívó.']);
        }

        $inviteId = (int) substr($link, strlen('invite:'));
        if ($inviteId <= 0) return back()->withErrors(['Hibás meghívó azonosító.']);

        $invite = DB::selectOne("
            SELECT * FROM household_invites
            WHERE id = ? AND invited_user_id = ?
            LIMIT 1
        ", [$inviteId, $userId]);

        if (!$invite) return back()->withErrors(['A meghívó nem található.']);
        if ($invite->status !== 'pending') return back()->withErrors(['Ez a meghívó már le lett kezelve.']);

        if ($action === 'accept') {
            // már tag?
            $exists = DB::selectOne("
                SELECT id FROM household_members
                WHERE household_id = ? AND member_id = ?
                LIMIT 1
            ", [(int)$invite->household_id, $userId]);

            if (!$exists) {
                DB::table('household_members')->insert([
                    'household_id' => (int)$invite->household_id,
                    'member_id' => $userId,
                    'role' => 'tag',
                ]);
            }

            DB::update("UPDATE household_invites SET status='accepted' WHERE id = ?", [$inviteId]);
            DB::update("UPDATE messages SET is_read=1 WHERE id = ? AND user_id = ?", [$msgId, $userId]);

            return back()->with('success', 'Meghívás elfogadva!');
        }

        DB::update("UPDATE household_invites SET status='declined' WHERE id = ?", [$inviteId]);
        DB::update("UPDATE messages SET is_read=1 WHERE id = ? AND user_id = ?", [$msgId, $userId]);

        return back()->with('success', 'Meghívás elutasítva.');
    }
}
