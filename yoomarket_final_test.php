<?php
// yoomarket_final_test.php

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';

echo "<h2>Полное тестирование YoOMarket API</h2>";
echo "<style>
    .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; }
    .error { color: #721c24; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; }
    .info { color: #856404; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; }
</style>";

// Варианты base URL
$base_urls = [
    'https://api.yoomarket.net/',
    'https://api.yoomarket.ru/',
    'https://yoomarket.net/api/',
    'https://yoomarket.ru/api/',
    'https://partner.yoomarket.net/',
    'https://partner.yoomarket.ru/',
    'https://merchant.yoomarket.net/',
    'https://merchant.yoomarket.ru/',
    'https://store.yoomarket.net/',
    'https://store.yoomarket.ru/',
];

// Варианты endpoints
$endpoints = [
    '' => 'Корень API',
    'api' => 'API',
    'api/v1' => 'API v1',
    'v1' => 'v1',
    'api/v2' => 'API v2',
    'api/v3' => 'API v3',
    'api/v1/products' => 'Товары',
    'api/v1/goods' => 'Товары',
    'api/v1/items' => 'Предметы',
    'api/v1/accounts' => 'Аккаунты',
    'api/v1/stock' => 'Сток',
    'api/v1/balance' => 'Баланс',
    'api/v1/user' => 'Пользователь',
    'api/v1/me' => 'Мой профиль',
    'api/v1/shop' => 'Магазин',
    'api/v1/store' => 'Стор',
    'api/v1/merchant' => 'Мерчант',
    'api/v1/partner' => 'Партнер',
];

// Варианты аутентификации
$auth_methods = [
    'bearer' => ["Authorization: Bearer $token"],
    'x_api_key' => ["X-API-Key: $token"],
    'x_api_token' => ["X-API-Token: $token"],
    'api_key_param' => "?api_key=$token",
    'token_param' => "?token=$token",
    'access_token_param' => "?access_token=$token",
];

// Функция для тестирования
function testUrl($url, $headers = null, $method = 'GET') {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
    ]);
    
    if ($headers && is_array($headers)) {
        $headers[] = "Accept: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'error' => $error,
        'response' => $response,
        'effective_url' => $effective_url,
    ];
}

// Основной цикл тестирования
$success_found = false;

foreach ($base_urls as $base_url) {
    echo "<div class='info'><strong>Тестируем базовый URL: $base_url</strong></div>";
    
    foreach ($endpoints as $endpoint => $desc) {
        foreach ($auth_methods as $auth_type => $auth) {
            $full_url = rtrim($base_url, '/') . '/' . $endpoint;
            
            // Для параметров добавляем к URL
            if (strpos($auth_type, '_param') !== false) {
                $full_url .= $auth;
                $headers = null;
            } else {
                $headers = $auth;
            }
            
            echo "<div>Тест: $desc ($auth_type) => $full_url</div>";
            
            $result = testUrl($full_url, $headers);
            
            if ($result['http_code'] == 200) {
                echo "<div class='success'>✅ УСПЕХ! HTTP 200 на: {$result['effective_url']}</div>";
                $data = json_decode($result['response'], true);
                echo "<pre>";
                print_r($data);
                echo "</pre>";
                $success_found = true;
                break 3; // Выходим из всех циклов
            } elseif ($result['http_code'] > 0 && $result['http_code'] != 404) {
                echo "<div>Код: {$result['http_code']} - ";
                if ($result['response']) {
                    $data = json_decode($result['response'], true);
                    if ($data && isset($data['message'])) {
                        echo "Сообщение: {$data['message']}";
                    }
                }
                echo "</div>";
            }
            
            // Небольшая пауза между запросами
            usleep(100000); // 0.1 секунда
        }
    }
    
    echo "<hr>";
}

if (!$success_found) {
    echo "<div class='error'><strong>❌ Не удалось найти работающий API endpoint</strong></div>";
    
    // Предлагаем альтернативы
    echo "<h3>Рекомендуемые действия:</h3>";
    echo "<ol>";
    echo "<li><strong>Свяжитесь с поддержкой YoOMarket</strong> через панель управления</li>";
    echo "<li><strong>Проверьте документацию</strong> в личном кабинете YoOMarket</li>";
    echo "<li><strong>Используйте альтернативные поставщики</strong> пока решается вопрос с API</li>";
    echo "<li><strong>Проверьте статус токена</strong> - возможно, он не активирован или просрочен</li>";
    echo "</ol>";
}

// Дополнительный тест - проверка доступности сервиса
echo "<h3>Проверка доступности сервисов YoOMarket:</h3>";

$services = [
    'Основной сайт' => 'https://yoomarket.net',
    'Панель управления' => 'https://panel.yoomarket.net',
    'API документация' => 'https://panel.yoomarket.net/docs/integration/v1/ru',
    'Главная (RU)' => 'https://yoomarket.ru',
];

foreach ($services as $name => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $status = ($http_code == 200 || $http_code == 301 || $http_code == 302) ? '✅' : '❌';
    echo "$status $name ($url): HTTP $http_code<br>";
}
?>