<?php

/**
 * Fontos: bár a függvény neve "Spoonacular",
 * valójában most már a TheMealDB ingyenes API-t hívjuk.
 *
 * Listázó végpont:
 *   https://www.themealdb.com/api/json/v1/1/search.php?s=chicken
 *
 * Részletező végpont:
 *   https://www.themealdb.com/api/json/v1/1/lookup.php?i=ID
 */

function fetchSpoonacularRecipes($query = 'chicken', $limit = 50)
{
    $url = 'https://www.themealdb.com/api/json/v1/1/search.php?s=' . urlencode($query);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        return ['_error' => 'cURL hiba (TheMealDB): ' . curl_error($ch)];
    }

    $decoded = json_decode($raw, true);

    if (!isset($decoded['meals']) || !is_array($decoded['meals'])) {
        return ['_error' => 'TheMealDB: nincs találat erre a keresésre.'];
    }

    $meals = array_slice($decoded['meals'], 0, $limit);

    // Visszaalakítjuk olyan struktúrára, amit a régi kód is ért:
    // id, title, image
    $results = [];
    foreach ($meals as $meal) {
        $results[] = [
            'id'    => $meal['idMeal'],
            'title' => $meal['strMeal'],
            'image' => $meal['strMealThumb'],
            // ha később akarod, ide tehetünk még mezőket
        ];
    }

    return $results;
}


/**
 * Részletek lekérése egy adott recepthez (TheMealDB)
 * Visszatér: egyetlen $meal asszociatív tömbbel, vagy null.
 */
function fetchRecipeDetails($id)
{
    $url = 'https://www.themealdb.com/api/json/v1/1/lookup.php?i=' . urlencode($id);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        return null;
    }

    $decoded = json_decode($raw, true);

    if (!isset($decoded['meals'][0])) {
        return null;
    }

    return $decoded['meals'][0]; // egyetlen recept adatai
}
