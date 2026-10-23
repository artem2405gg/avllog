<?php
header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
if (empty($query)) {
    echo json_encode(['error' => 'Empty query']);
    exit;
}

$apiKey = YANDEX_MAPS_API_KEY;
$url = "https://geocode-maps.yandex.ru/1.x/?apikey={$apiKey}&geocode=" . urlencode($query) . "&format=json&results=5&lang=ru_RU";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['error' => 'CURL failed']);
    exit;
}

echo $response;