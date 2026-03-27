<?php
$p = 'resources/views/recipes/own_create.blade.php';
$c = file_get_contents($p);

$c = str_replace(
"function createIngredientRow(value = '') {",
"function createIngredientRow(value = '', unit = '') {",
$c
);

$c = str_replace(
"input.addEventListener('input', updatePreview);",
"input.addEventListener('input', updatePreview);

    const unitInput = document.createElement('input');
    unitInput.type = 'text';
    unitInput.name = 'ingredient_units[]';
    unitInput.placeholder = 'pl. g / ml / db';
    unitInput.value = unit;
    unitInput.className = 'ing-unit';
    unitInput.addEventListener('input', updatePreview);",
$c
);

$c = str_replace(
"row.appendChild(input);
    row.appendChild(btn);",
"row.appendChild(input);
    row.appendChild(unitInput);
    row.appendChild(btn);",
$c
);

$c = str_replace(
".ing-row input{ flex: 1 1 auto; width: 100%; }",
".ing-row input{ flex: 1 1 auto; width: 100%; }
  .ing-row .ing-unit{
    flex: 0 0 120px;
    max-width: 120px;
  }",
$c
);

$c = str_replace(
"const oldIngredients = @json(old('ingredients', []));",
"const oldIngredients = @json(old('ingredients', []));
    const oldUnits = @json(old('ingredient_units', []));",
$c
);

$c = str_replace(
"oldIngredients.forEach(v => cont.appendChild(createIngredientRow(String(v ?? ''))));",
"oldIngredients.forEach((v, idx) => cont.appendChild(createIngredientRow(String(v ?? ''), String(oldUnits[idx] ?? ''))));",
$c
);

file_put_contents($p, $c);
echo \"own_create patched\n\";
