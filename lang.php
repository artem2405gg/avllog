<?php
function loadLang() {
    $lang = $_SESSION['lang'] ?? 'ru';
    $file = BASE_PATH . "/lang/{$lang}.json";
    
    if (!file_exists($file)) {
        $file = BASE_PATH . "/lang/ru.json";
    }
    
    $json = file_get_contents($file);
    return json_decode($json, true);
}

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}