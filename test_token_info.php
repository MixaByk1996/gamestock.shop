<?php
// test_token_info.php

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';

echo "<h2>Проверка информации о токене</h2>";

// Попробуем получить информацию о пользователе
$urls = [
    'https://panel.yoomarket.net/api/v1/user',
    'https://panel.yoomarket.net/api/v1/me',
    'https://panel.yoomarket.net/api/user',
    'https://panel.yoomarket.net/api/me',
    'https://panel.yoomarket.net/user',
    'https://panel.yoomarket.net/me'
];

foreach ($urls as $url) {
    echo "<h3>Запрос: $url</h3>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 3
    ]);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "HTTP Code: <strong>$code</strong><br>";
    
    if ($response) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Ответ: <pre>";
            print_r($data);
            echo "</pre>";
            
            if ($code == 200) {
                echo "<div style='color: green; font-weight: bold;'>✅ Токен работает!</div>";
                break;
            }
        }
    }
    
    curl_close($ch);
    echo "<hr>";
}

// Проверим валидность токена через JWT декодирование (если это JWT)
echo "<h2>Анализ токена</h2>";
$token_parts = explode('.', $token);
if (count($token_parts) === 3) {
    echo "Токен похож на JWT (3 части разделенные точками)<br>";
    
    // Пробуем декодировать payload (вторая часть)
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1]));
    if ($payload) {
        $payload_data = json_decode($payload, true);
        echo "Payload JWT: <pre>";
        print_r($payload_data);
        echo "</pre>";
    }
} else {
    echo "Токен не в формате JWT<br>";
}

echo "Длина токена: " . strlen($token) . " символов<br>";
echo "Первые 20 символов: " . substr($token, 0, 20) . "...<br>";
?>