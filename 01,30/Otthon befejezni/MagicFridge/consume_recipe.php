<?php
session_start();
require 'config.php';
require 'api/spoonacular.php';
require 'api/translate.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$userId = (int)$_SESSION['user_id'];

function norm($s){
  $s = mb_strtolower(trim((string)$s), 'UTF-8');
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

/** 1) Recept mérték parse -> base (g/ml/pcs) */
function parseMeasureToBase(string $measure, string $ingredientName): array {
  $m = norm($measure);
  $ing = norm($ingredientName);

  // alapértelmezés: 1 db
  if ($m === '' || $m === 'to taste' || $m === 'taste') return ['qty'=>1.0,'unit'=>'pcs'];

  // szám kinyerés: pl "1 1/2", "1/2", "2"
  $num = 0.0;

  // "1 1/2"
  if (preg_match('/(\d+)\s+(\d+)\/(\d+)/', $m, $mm)) {
    $num = (float)$mm[1] + ((float)$mm[2] / (float)$mm[3]);
  } elseif (preg_match('/(\d+)\/(\d+)/', $m, $mm)) {
    $num = (float)$mm[1] / (float)$mm[2];
  } elseif (preg_match('/(\d+(\.\d+)?)/', $m, $mm)) {
    $num = (float)$mm[1];
  } else {
    $num = 1.0;
  }

  // unit felismerés
  // tömeg
  if (str_contains($m, 'kg')) return ['qty'=>$num*1000,'unit'=>'g'];
  if (preg_match('/\bg\b/', $m)) return ['qty'=>$num,'unit'=>'g'];

  // térfogat
  if (str_contains($m, 'l'))  return ['qty'=>$num*1000,'unit'=>'ml']; // 1 l = 1000 ml
  if (str_contains($m, 'ml')) return ['qty'=>$num,'unit'=>'ml'];

  // kanalak/csészék (közelítés)
  if (str_contains($m, 'tbsp')) return ['qty'=>$num*15,'unit'=>'ml'];
  if (str_contains($m, 'tsp'))  return ['qty'=>$num*5,'unit'=>'ml'];
  if (str_contains($m, 'cup'))  return ['qty'=>$num*240,'unit'=>'ml'];

  // darab jelleg
  if (str_contains($m, 'clove') || str_contains($m, 'gerezd')) return ['qty'=>$num,'unit'=>'pcs'];
  if (str_contains($m, 'pc') || str_contains($m, 'db') || str_contains($m, 'piece')) return ['qty'=>$num,'unit'=>'pcs'];

  // ha nem ismert: darabként kezeljük
  return ['qty'=>$num,'unit'=>'pcs'];
}

/** 2) Készlet sor kiválasztása név alapján (egyszerű fuzzy) */
function findInventoryRow(PDO $pdo, int $hid, string $ingredient): ?array {
  $needle = norm($ingredient);

  $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE household_id=? ORDER BY id ASC");
  $stmt->execute([$hid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as $r) {
    $n = norm($r['name'] ?? '');
    if ($n === '') continue;
    if (str_contains($n, $needle) || str_contains($needle, $n)) {
      return $r; // első találat
    }
  }
  return null;
}

/** 3) Egység kompatibilitás */
function canSubtract(string $invUnit, string $needUnit): bool {
  return $invUnit === $needUnit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: dashboard.php");
  exit;
}

$householdId = (int)($_POST['hid'] ?? 0);
$mealId      = (int)($_POST['meal_id'] ?? 0);

if ($householdId <= 0 || $mealId <= 0) {
  header("Location: recipes.php?hid=".$householdId);
  exit;
}

// háztartás jogosultság
$stmt = $pdo->prepare("
  SELECT 1
  FROM households h
  LEFT JOIN household_members hm ON hm.household_id = h.id AND hm.member_id = ?
  WHERE h.id = ? AND (h.owner_id = ? OR hm.member_id IS NOT NULL)
  LIMIT 1
");
$stmt->execute([$userId, $householdId, $userId]);
if (!$stmt->fetchColumn()) {
  header("Location: recipes.php?hid=".$householdId);
  exit;
}

$meal = fetchRecipeDetails($mealId);
if (!$meal) {
  header("Location: recipe_details.php?id=".$mealId."&hid=".$householdId."&cook=err&msg=Recept%20nem%20tal%C3%A1lhat%C3%B3");
  exit;
}

// hozzávalók lista
$ings = [];
for ($i=1; $i<=20; $i++){
  $ingName = trim($meal["strIngredient{$i}"] ?? '');
  $measure = trim($meal["strMeasure{$i}"] ?? '');
  if ($ingName === '') continue;

  $huName = translateToHungarian($ingName);
  $need = parseMeasureToBase($measure, $huName);

  $ings[] = [
    'name_hu' => $huName,
    'name_en' => $ingName,
    'need_qty' => (float)$need['qty'],
    'need_unit'=> $need['unit'],
    'raw_measure' => $measure
  ];
}

try {
  $pdo->beginTransaction();

  // 1) ellenőrzés: minden hozzávaló megvan és levonható
  foreach ($ings as $ing) {
    $row = findInventoryRow($pdo, $householdId, $ing['name_hu']) ?? findInventoryRow($pdo, $householdId, $ing['name_en']);
    if (!$row) {
      throw new Exception("Hiányzik a raktárból: ".$ing['name_hu']);
    }

    $invBaseUnit = $row['base_unit'] ?? 'pcs';
    $invBaseQty  = (float)($row['base_quantity'] ?? 0);

    // ha még nincs base mennyiség felvéve a régi tételeknél:
    if ($invBaseQty <= 0) {
      // fallback: ha van quantity+unit, próbáljuk
      $q = (float)($row['quantity'] ?? 0);
      $u = norm($row['unit'] ?? '');
      if ($u === 'kg') { $invBaseQty = $q*1000; $invBaseUnit = 'g'; }
      elseif ($u === 'g') { $invBaseQty = $q; $invBaseUnit = 'g'; }
      elseif ($u === 'l') { $invBaseQty = $q*1000; $invBaseUnit = 'ml'; }
      elseif ($u === 'ml') { $invBaseQty = $q; $invBaseUnit = 'ml'; }
      else { $invBaseQty = max(0, $q); $invBaseUnit = 'pcs'; }
    }

    if (!canSubtract($invBaseUnit, $ing['need_unit'])) {
      // itt még lehetne “okos” átváltás, de már base-ban vagyunk, így csak egyezhet.
      throw new Exception("Nem egyezik a mértékegység: ".$ing['name_hu']." (kell: ".$ing['need_unit'].", raktár: ".$invBaseUnit.")");
    }

    if ($invBaseQty < $ing['need_qty']) {
      throw new Exception("Nincs elég: ".$ing['name_hu']." (kell ".$ing['need_qty']." ".$ing['need_unit'].", van ".$invBaseQty." ".$invBaseUnit.")");
    }
  }

  // 2) levonás
  foreach ($ings as $ing) {
    $row = findInventoryRow($pdo, $householdId, $ing['name_hu']) ?? findInventoryRow($pdo, $householdId, $ing['name_en']);
    if (!$row) continue;

    $invId = (int)$row['id'];

    // újra betöltjük pontosan az aktuális base mennyiséget
    $stmt = $pdo->prepare("SELECT base_quantity, base_unit, quantity, unit FROM inventory_items WHERE id=? FOR UPDATE");
    $stmt->execute([$invId]);
    $cur = $stmt->fetch(PDO::FETCH_ASSOC);

    $invBaseUnit = $cur['base_unit'] ?? 'pcs';
    $invBaseQty  = (float)($cur['base_quantity'] ?? 0);

    if ($invBaseQty <= 0) {
      $q = (float)($cur['quantity'] ?? 0);
      $u = norm($cur['unit'] ?? '');
      if ($u === 'kg') { $invBaseQty = $q*1000; $invBaseUnit = 'g'; }
      elseif ($u === 'g') { $invBaseQty = $q; $invBaseUnit = 'g'; }
      elseif ($u === 'l') { $invBaseQty = $q*1000; $invBaseUnit = 'ml'; }
      elseif ($u === 'ml') { $invBaseQty = $q; $invBaseUnit = 'ml'; }
      else { $invBaseQty = max(0, $q); $invBaseUnit = 'pcs'; }
    }

    $newQty = $invBaseQty - $ing['need_qty'];

    $upd = $pdo->prepare("UPDATE inventory_items SET base_quantity=? , base_unit=? WHERE id=?");
    $upd->execute([$newQty, $invBaseUnit, $invId]);

    // ha elfogyott, akár törölheted is (opcionális):
    // if ($newQty <= 0.0001) { $pdo->prepare("DELETE FROM inventory_items WHERE id=?")->execute([$invId]); }
  }

  $pdo->commit();

  header("Location: recipe_details.php?id=".$mealId."&hid=".$householdId."&cook=ok");
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  $msg = urlencode($e->getMessage());
  header("Location: recipe_details.php?id=".$mealId."&hid=".$householdId."&cook=err&msg=".$msg);
  exit;
}
