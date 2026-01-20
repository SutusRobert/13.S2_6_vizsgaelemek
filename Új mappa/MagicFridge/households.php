<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// háztartás létrehozás, ha még nincs
$stmt = $pdo->prepare("SELECT * FROM households WHERE owner_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$household = $stmt->fetch();

if (!$household) {
    $stmt = $pdo->prepare("INSERT INTO households (owner_id, name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['full_name'] . " háztartása"]);
    $householdId = $pdo->lastInsertId();

    // A tulajdonos automatikusan admin tag lesz ebben a háztartásban
    $stmt = $pdo->prepare("INSERT INTO household_members (household_id, member_id, role) VALUES (?, ?, 'admin')");
    $stmt->execute([$householdId, $_SESSION['user_id']]);

    $stmt = $pdo->prepare("SELECT * FROM households WHERE id = ?");
    $stmt->execute([$householdId]);
    $household = $stmt->fetch();
}

// tagok lekérése (user_id-t is lehúzzuk, hogy tudjuk, ki kicsoda)
$stmt = $pdo->prepare("
    SELECT hm.id AS hm_id, u.id AS user_id, u.full_name, hm.role
    FROM household_members hm
    JOIN users u ON hm.member_id = u.id
    WHERE hm.household_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$household['id']]);
$members = $stmt->fetchAll();

$info = $_GET['info'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Háztartás – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Főoldal</a>
        <a href="recipes.php">Receptek</a>
        <a href="logout.php" class="danger">Kijelentkezés</a>
    </div>
</div>

<div class="main-wrapper">
    <div class="card">
        <h2>Háztartás</h2>
        <p class="small mt-2">Háztartás neve: <strong><?= htmlspecialchars($household['name']) ?></strong></p>

        <?php if ($info): ?>
            <div class="success mt-2"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error mt-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h3 class="mt-4">Tag hozzáadása (regisztrált felhasználók közül)</h3>
        <form class="mt-2" method="post" action="add_household_member.php">
            <div class="form-group">
                <label>Teljes név (pontosan úgy, ahogy regisztrálva van)</label>
                <input type="text" name="full_name" required>
            </div>
            <button type="submit">Hozzáadás</button>
        </form>

        <h3 class="mt-4">Tagok</h3>
        <ul class="list mt-2">
            <?php if (empty($members)): ?>
                <li>Még nincsenek tagok.</li>
            <?php else: ?>
                <?php foreach ($members as $m): ?>
                    <li>
                        <span>
                            <?= htmlspecialchars($m['full_name']) ?>
                            <?php if ($m['role'] === 'admin'): ?>
                                <span class="badge badge-primary">admin</span>
                            <?php elseif ($m['role'] === 'alap felhasználó'): ?>
                                <span class="badge badge-primary">alap felhasználó</span>
                            <?php else: ?>
                                <span class="badge">tag</span>
                            <?php endif; ?>
                        </span>

                        <?php if ($m['role'] !== 'admin'): ?>
                            <form method="post" action="toggle_role.php" style="margin:0;">
                                <input type="hidden" name="hm_id" value="<?= $m['hm_id'] ?>">
                                <button type="submit" class="btn btn-secondary" style="font-size:12px;">
                                    <?php if ($m['role'] === 'alap felhasználó'): ?>
                                        Rang eltávolítása
                                    <?php else: ?>
                                        Rang hozzáadása
                                    <?php endif; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Admin rang nem módosítható, nincs gomb -->
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

</body>
</html>