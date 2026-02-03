<?php
// simple_curl_test.php

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';

echo "<h2>Простой тест YoOMarket API через curl</h2>";

// Попробуем напрямую получить ответ от API
$urls = [
    'https://panel.yoomarket.net/',
    'https://panel.yoomarket.net/api/',
    'https://panel.yoomarket.net/api/v1/',
    'https://api.yoomarket.net/',
    'https://api.yoomarket.net/api/v1/'
];

foreach ($urls as $url) {
    echo "<h3>Запрос к: $url</h3>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "HTTP Code: <strong>$code</strong><br>";
    
    if ($response) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Ответ JSON: <pre>";
            print_r($data);
            echo "</pre>";
            
            // Если есть сообщение об ошибке
            if (isset($data['message'])) {
                echo "Сообщение: <em>" . $data['message'] . "</em><br>";
            }
        } else {
            echo "Ответ (не JSON): <pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "Нет ответа<br>";
    }
    
    curl_close($ch);
    echo "<hr>";
}
?>