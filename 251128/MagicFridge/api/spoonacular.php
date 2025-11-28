<?php

function fetchSpoonacularRecipes($query = 'chicken', $limit = 50) {

    $apiKey = 'e9e349c383c74a2eaf56bb38d9fba167';

    if (!$apiKey || strlen($apiKey) < 5) {
        return [];
    }

    $url =
        "https://api.spoonacular.com/recipes/complexSearch" .
        "?apiKey={$apiKey}" .
        "&query=" . urlencode($query) .
        "&number={$limit}" .
        "&addRecipeInformation=true" .
        "&instructionsRequired=true";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // ÁTÁLLÍTVA, mert nálad ez okozta a hibát
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);

    return $decoded['results'] ?? [];
}


function fetchRecipeDetails($id) {

    $apiKey = 'e9e349c383c74a2eaf56bb38d9fba167';

    $url = "https://api.spoonacular.com/recipes/{$id}/information?apiKey={$apiKey}&includeNutrition=false";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        return null;
    }

    return json_decode($raw, true);
}
