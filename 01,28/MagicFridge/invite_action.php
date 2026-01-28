<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';

$userId = (int)$_SESSION['user_id'];

$inviteId = isset($_POST['invite_id']) ? (int)$_POST['invite_id'] : 0;
$action   = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

if ($inviteId <= 0 || !in_array($action, ['accept', 'decline'], true)) {
    $_SESSION['flash_error'] = 'Érvénytelen kérés.';
    header('Location: messages.php');
    exit;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ellenőrizzük, hogy létezik-e a tábla
    $chk = $pdo->query("SHOW TABLES LIKE 'household_invitations'")->fetchColumn();
    if (!$chk) {
        $_SESSION['flash_error'] = 'A meghívás funkció nincs bekapcsolva (hiányzó tábla: household_invitations).';
        header('Location: messages.php');
        exit;
    }

    $pdo->beginTransaction();

    // Meghívó betöltése (és lock, hogy ne lehessen kétszer elfogadni)
    $stmt = $pdo->prepare(
        "SELECT id, household_id, invited_user_id, invited_by, status
         FROM household_invitations
         WHERE id = ?
         FOR UPDATE"
    );
    $stmt->execute([$inviteId]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inv) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'A meghívó nem található.';
        header('Location: messages.php');
        exit;
    }

    if ((int)$inv['invited_user_id'] !== $userId) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Ehhez a meghívóhoz nincs jogosultságod.';
        header('Location: messages.php');
        exit;
    }

    if (($inv['status'] ?? '') !== 'pending') {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Ez a meghívó már nem függőben van.';
        header('Location: messages.php');
        exit;
    }

    $householdId = (int)$inv['household_id'];

    if ($action === 'accept') {
        // Tagként felvétel (ha még nincs bent)
        $exists = $pdo->prepare(
            "SELECT 1 FROM household_members WHERE household_id = ? AND member_id = ? LIMIT 1"
        );
        $exists->execute([$householdId, $userId]);
        $already = (bool)$exists->fetchColumn();

        if (!$already) {
            $ins = $pdo->prepare(
                "INSERT INTO household_members (household_id, member_id, role, created_at)
                 VALUES (?, ?, 'member', NOW())"
            );
            $ins->execute([$householdId, $userId]);
        }

        // Meghívó státusz frissítése
        $up = $pdo->prepare(
            "UPDATE household_invitations
             SET status = 'accepted', responded_at = NOW()
             WHERE id = ?"
        );
        $up->execute([$inviteId]);

        // Opcionális: rendszerüzenet beszúrás
        try {
            $msg = $pdo->prepare(
                "INSERT INTO messages (household_id, user_id, type, title, body, link_url, is_read, created_at)
                 VALUES (?, ?, 'success', 'Meghívás elfogadva', 'Sikeresen csatlakoztál a háztartáshoz.', 'dashboard.php', 0, NOW())"
            );
            $msg->execute([$householdId, $userId]);
        } catch (Throwable $e) {
            // ha nincs messages tábla / oszlop eltérés, ne akadjon el
        }

        $pdo->commit();
        $_SESSION['flash_success'] = 'Meghívás elfogadva! Mostantól tagja vagy a háztartásnak.';
        header('Location: messages.php');
        exit;
    }

    if ($action === 'decline') {
        $up = $pdo->prepare(
            "UPDATE household_invitations
             SET status = 'declined', responded_at = NOW()
             WHERE id = ?"
        );
        $up->execute([$inviteId]);

        $pdo->commit();
        $_SESSION['flash_success'] = 'Meghívás elutasítva.';
        header('Location: messages.php');
        exit;
    }

    $pdo->rollBack();
    $_SESSION['flash_error'] = 'Ismeretlen művelet.';
    header('Location: messages.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Hiba történt: ' . $e->getMessage();
    header('Location: messages.php');
    exit;
}
