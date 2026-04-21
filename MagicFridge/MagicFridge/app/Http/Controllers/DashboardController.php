<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) session('user_id');
        $fullName = (string) (session('full_name') ?? session('user_name') ?? '');

        if ($fullName === '' && $userId > 0) {
            try {
                $u = DB::selectOne("SELECT full_name, name FROM users WHERE id = ? LIMIT 1", [$userId]);
                $fullName = (string) ($u->full_name ?? ($u->name ?? ''));
            } catch (\Throwable $e) {
                $fullName = '';
            }
        }

        $parts = preg_split('/\s+/', trim($fullName));
        // A dashboard a teljes név utolsó szavával köszön. Ez illeszkedik ahhoz,
        // hogy az adatokban a név gyakran "vezetéknév keresztnév" sorrendű.
        $firstName = $parts ? end($parts) : '';
        if (!$firstName) $firstName = 'User';

        // Összegyűjtjük a felhasználóhoz tartozó háztartásokat, mert az üzenetek
        // lehetnek háztartás szintűek és közvetlenül felhasználóhoz kötöttek is.
        $householdIds = [];
        $sessionHid = session('household_id');

        if (!empty($sessionHid)) {
            $householdIds = [(int) $sessionHid];
        } else {
            try {
                $rows = DB::select("
                    SELECT id AS household_id FROM households WHERE owner_id = ?
                    UNION
                    SELECT household_id FROM household_members WHERE member_id = ?
                ", [$userId, $userId]);

                $householdIds = array_values(array_unique(array_map(fn($r) => (int)$r->household_id, $rows)));
            } catch (\Throwable $e) {
                $householdIds = [];
            }
        }

        // Az olvasatlanságot LEFT JOIN-nal számoljuk: ha nincs message_reads sor
        // ehhez a felhasználóhoz, akkor az üzenet még olvasatlan.
        $unreadCount = 0;
        $unreadPreview = [];

        try {
            if (!empty($householdIds)) {
                // Dinamikus placeholder-listát készítünk, hogy az IN feltétel
                // változó számú háztartással is paraméterezett maradjon.
                $placeholders = implode(',', array_fill(0, count($householdIds), '?'));

                $sqlCount = "
                    SELECT COUNT(*) AS c
                    FROM messages m
                    LEFT JOIN message_reads mr
                           ON mr.message_id = m.id AND mr.user_id = ?
                    WHERE ((m.household_id IN ($placeholders)) OR (m.user_id = ?))
                      AND mr.message_id IS NULL
                ";

                $params = array_merge([$userId], $householdIds, [$userId]);
                $unreadCount = (int) (DB::selectOne($sqlCount, $params)->c ?? 0);

                $sqlPrev = "
                    SELECT m.id, m.title, m.body, m.created_at, m.type
                    FROM messages m
                    LEFT JOIN message_reads mr
                           ON mr.message_id = m.id AND mr.user_id = ?
                    WHERE ((m.household_id IN ($placeholders)) OR (m.user_id = ?))
                      AND mr.message_id IS NULL
                    ORDER BY m.created_at DESC
                    LIMIT 3
                ";

                $unreadPreview = DB::select($sqlPrev, $params);
            } else {
                $sqlCount = "
                    SELECT COUNT(*) AS c
                    FROM messages m
                    LEFT JOIN message_reads mr
                           ON mr.message_id = m.id AND mr.user_id = ?
                    WHERE m.user_id = ?
                      AND mr.message_id IS NULL
                ";
                $unreadCount = (int) (DB::selectOne($sqlCount, [$userId, $userId])->c ?? 0);

                $sqlPrev = "
                    SELECT m.id, m.title, m.body, m.created_at, m.type
                    FROM messages m
                    LEFT JOIN message_reads mr
                           ON mr.message_id = m.id AND mr.user_id = ?
                    WHERE m.user_id = ?
                      AND mr.message_id IS NULL
                    ORDER BY m.created_at DESC
                    LIMIT 3
                ";
                $unreadPreview = DB::select($sqlPrev, [$userId, $userId]);
            }
        } catch (\Throwable $e) {
            $unreadCount = 0;
            $unreadPreview = [];
        }

        return view('inventory.dashboard', [
            'firstName' => $firstName,
            'unreadCount' => $unreadCount,
            'unreadPreview' => $unreadPreview,
        ]);
    }
}
