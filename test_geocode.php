<?php
$query = 'Москва';
$apiKey = 'ec80939d-87bd-4bbf-b4fa-51eff7363ca8';
$url = "https://geocode-maps.yandex.ru/1.x/?apikey={$apiKey}&geocode=" . urlencode($query) . "&format=json&results=1&lang=ru_RU";

echo "Запрос: " . $url . "<br><br>";

$response = @file_get_contents($url);

if ($response === false) {
    echo "❌ ОШИБКА: file_get_contents не работает. Возможно, хостинг блокирует внешние запросы.<br>";
    echo "Попробуем через curl...<br><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ CURL ошибка: " . $error . "<br>";
        echo "Хостинг блокирует исходящие запросы. Нужно использовать curl с настройками.";
    } else {
        echo "✅ CURL работает!<br>";
        echo "<textarea style='width:100%;height:300px;'>" . htmlspecialchars($response) . "</textarea>";
    }
} else {
    echo "✅ file_get_contents работает!<br>";
    echo "<textarea style='width:100%;height:300px;'>" . htmlspecialchars($response) . "</textarea>";
}