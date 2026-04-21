<?php
function fail($msg){ fwrite(STDERR, "ERROR: $msg\n"); exit(1); }

$ctrlPath = 'app/Http/Controllers/RecipeController.php';
$bladePath = 'resources/views/recipes/own_create.blade.php';

if (!file_exists($ctrlPath)) fail("Missing $ctrlPath");
if (!file_exists($bladePath)) fail("Missing $bladePath");

$ctrl = file_get_contents($ctrlPath);
$blade = file_get_contents($bladePath);

$newStoreOwn = <<<'PHP'
public function storeOwn(Request $request)
    {
        $userId = (int) session('user_id');

        $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'nullable|string|max:255',
            'ingredient_units' => 'nullable|array',
            'ingredient_units.*' => 'nullable|string|max:50',
        ]);

        $title = trim((string)$request->input('title'));
        $instructions = trim((string)$request->input('instructions', ''));
        $ingsRaw = $request->input('ingredients', []);
        $unitsRaw = $request->input('ingredient_units', []);
        $ings = [];
        foreach ($ingsRaw as $idx => $x) {
            $ingredient = trim((string)$x);
            if ($ingredient === '') continue;

            $unit = trim((string)($unitsRaw[$idx] ?? ''));
            $stored = $unit !== '' ? ($ingredient . ' (' . $unit . ')') : $ingredient;
            $ings[] = mb_substr($stored, 0, 255, 'UTF-8');
        }

        if ($title === '' || empty($ings)) {
            return back()->withErrors(['Enter a title and at least one ingredient.'])->withInput();
        }

        DB::beginTransaction();
        try {
            DB::insert(
                "INSERT INTO recipes (user_id, title, instructions, created_at) VALUES (?, ?, ?, NOW())",
                [$userId, $title, ($instructions !== '' ? $instructions : null)]
            );

            $rid = (int)DB::getPdo()->lastInsertId();

            foreach ($ings as $ing) {
                DB::insert("INSERT INTO recipe_ingredients (recipe_id, ingredient) VALUES (?, ?)", [$rid, $ing]);
            }

            DB::commit();

            $hid = (int) $request->input('hid', 0);

            return redirect()->route('recipes.own.show', ['id' => $rid, 'hid' => $hid])
                ->with('success', 'Recipe saved.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['Hiba: '.$e->getMessage()])->withInput();
        }
    }
PHP;

$ctrl = preg_replace(
    '/public function storeOwn\(Request \$request\)\s*\{.*?\n\s*\}\n\s*\n\s*public function showOwn/s',
    $newStoreOwn . "\n\n    public function showOwn",
    $ctrl, 1, $countStore
);
if ($countStore !== 1) fail("Could not replace storeOwn()");

# own_create.blade.php fixes
$blade = str_replace(
    'style="width:100%; resize:vertical;"></textarea>',
    'style="width:100%; resize:vertical;">{{ old(\'instructions\') }}</textarea>',
    $blade
);

$blade = str_replace(
    '.ing-row input{ flex: 1 1 auto; width: 100%; }',
    ".ing-row input{ flex: 1 1 auto; width: 100%; }\n  .ing-row .ing-unit{\n    flex: 0 0 120px;\n    max-width: 120px;\n  }",
    $blade
);

$blade = str_replace(
    "function createIngredientRow(value = '') {",
    "function createIngredientRow(value = '', unit = '') {",
    $blade
);

$blade = str_replace(
    "input.addEventListener('input', updatePreview);",
    "input.addEventListener('input', updatePreview);\n\n    const unitInput = document.createElement('input');\n    unitInput.type = 'text';\n    unitInput.name = 'ingredient_units[]';\n    unitInput.placeholder = 'pl. g / ml / db';\n    unitInput.value = unit;\n    unitInput.className = 'ing-unit';\n    unitInput.addEventListener('input', updatePreview);",
    $blade
);

$blade = str_replace(
    "row.appendChild(input);\n    row.appendChild(btn);",
    "row.appendChild(input);\n    row.appendChild(unitInput);\n    row.appendChild(btn);",
    $blade
);

$blade = str_replace(
    "const inputs = Array.from(document.querySelectorAll('#ingredients input[name=\"ingredients[]\"]'));\n    const cleaned = inputs.map(i => (i.value || '').trim()).filter(v => v.length > 0);",
    "const rows = Array.from(document.querySelectorAll('#ingredients .ing-row'));\n    const cleaned = rows\n      .map(row => ((row.querySelector('input[name=\"ingredients[]\"]')?.value) || '').trim())\n      .filter(v => v.length > 0);",
    $blade
);

$blade = str_replace(
    "const inputs = Array.from(document.querySelectorAll('#ingredients input[name=\"ingredients[]\"]'));\n    const vals = inputs.map(i => (i.value || '').trim());\n    const cleaned = vals.filter(v => v.length > 0);",
    "const rows = Array.from(document.querySelectorAll('#ingredients .ing-row'));\n    const vals = rows.map(row => ({\n      ingredient: ((row.querySelector('input[name=\"ingredients[]\"]')?.value) || '').trim(),\n      unit: ((row.querySelector('input[name=\"ingredient_units[]\"]')?.value) || '').trim(),\n    }));\n    const cleaned = vals.filter(v => v.ingredient.length > 0);",
    $blade
);

$blade = str_replace(
    "cleaned.slice(0, 12).forEach((v, idx) => {\n      const row = document.createElement('div');",
    "cleaned.slice(0, 12).forEach((v, idx) => {\n      const display = v.unit !== '' ? `${v.ingredient} (${v.unit})` : v.ingredient;\n      const row = document.createElement('div');",
    $blade
);

$blade = str_replace(
    "'<div><b>' + escapeHtml(v) + '</b><br><small class=\"muted\">#' + (idx+1) + '</small></div>' +",
    "'<div><b>' + escapeHtml(display) + '</b><br><small class=\"muted\">#' + (idx+1) + '</small></div>' +",
    $blade
);

$blade = str_replace(
    "const oldIngredients = @json(old('ingredients', []));",
    "const oldIngredients = @json(old('ingredients', []));\n    const oldUnits = @json(old('ingredient_units', []));",
    $blade
);

$blade = str_replace(
    "oldIngredients.forEach(v => cont.appendChild(createIngredientRow(String(v ?? ''))));",
    "oldIngredients.forEach((v, idx) => cont.appendChild(createIngredientRow(String(v ?? ''), String(oldUnits[idx] ?? ''))));",
    $blade
);

file_put_contents($ctrlPath, $ctrl);
file_put_contents($bladePath, $blade);

echo "OK: patched controller + blade\n";
