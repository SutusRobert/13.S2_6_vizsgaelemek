<?php

function translateToHungarian($text) {
    if (!$text || strlen(trim($text)) === 0) {
        return $text;
    }

    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=en|hu";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $raw = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($raw, true);

    return $data['responseData']['translatedText'] ?? $text;
}
