<?php

/**
 * Egyszerű fordító rövid szövegre.
 * MyMemory ingyenes API-t használ: en -> hu.
 */
function translateToHungarian($text)
{
    $text = trim((string)$text);
    if ($text === '') {
        return $text;
    }

    // MyMemory 500 karakter körüli limit - itt még direktben hívjuk
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=en|hu";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $raw = curl_exec($ch);
    curl_close($ch);

    if ($raw === false) {
        return $text; // hiba esetén az eredetit adjuk vissza
    }

    $data = json_decode($raw, true);

    if (!is_array($data) || !isset($data['responseData']['translatedText'])) {
        return $text;
    }

    return $data['responseData']['translatedText'];
}


/**
 * Hosszú szöveg fordítása magyarra.
 * A MyMemory ~500 karakteres limitje miatt a szöveget darabokra vágjuk,
 * és blokkonként fordítjuk.
 */
function translateLongTextToHungarian($text)
{
    $text = trim((string)$text);
    if ($text === '') {
        return $text;
    }

    // Ha eleve rövid, mehet a sima fordítón
    if (mb_strlen($text, 'UTF-8') <= 450) {
        return translateToHungarian($text);
    }

    $chunks = splitTextForTranslation($text, 400); // biztonságosan 400 karakter körül
    $translatedParts = [];

    foreach ($chunks as $chunk) {
        $translatedParts[] = translateToHungarian($chunk);
        // Kicsi szünet az API kímélése érdekében (0.2 mp)
        usleep(200000);
    }

    return trim(implode("\n\n", $translatedParts));
}


/**
 * Hosszú szöveg felosztása több, max. $maxLen hosszú darabra,
 * lehetőleg mondathatároknál vágva.
 */
function splitTextForTranslation(string $text, int $maxLen = 400): array
{
    $text = trim($text);
    $len  = mb_strlen($text, 'UTF-8');

    if ($len <= $maxLen) {
        return [$text];
    }

    $chunks = [];

    while (mb_strlen($text, 'UTF-8') > $maxLen) {
        // Vágjunk egy ideiglenes szeletet
        $slice = mb_substr($text, 0, $maxLen, 'UTF-8');

        // Próbáljunk mondatvégnél elvágni (., ?, !)
        $lastDot  = mb_strrpos($slice, '.', 0, 'UTF-8');
        $lastQ    = mb_strrpos($slice, '?', 0, 'UTF-8');
        $lastExc  = mb_strrpos($slice, '!', 0, 'UTF-8');

        $cutPos = max($lastDot !== false ? $lastDot : -1,
                      $lastQ   !== false ? $lastQ   : -1,
                      $lastExc !== false ? $lastExc : -1);

        if ($cutPos > 100) {
            // +1, hogy a pont is bekerüljön
            $slice = mb_substr($slice, 0, $cutPos + 1, 'UTF-8');
        }

        $chunks[] = trim($slice);

        // Levágjuk az eddig feldolgozott részt az eredeti szövegből
        $text = mb_substr($text, mb_strlen($slice, 'UTF-8'), null, 'UTF-8');
        $text = trim($text);
    }

    if ($text !== '') {
        $chunks[] = $text;
    }

    return $chunks;
}
