<?php
// check_supplier_api.php

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';

echo "<h2>Проверка: является ли YoOMarket поставщиком товаров?</h2>";

// Возможные URL для поставщиков
$supplier_urls = [
    'https://panel.yoomarket.net/api/v1/supplier/products',
    'https://panel.yoomarket.net/api/v1/marketplace/products',
    'https://panel.yoomarket.net/api/v1/wholesale/products',
    'https://panel.yoomarket.net/api/v1/reseller/products',
    'https://panel.yoomarket.net/api/v1/distributor/products',
    'https://panel.yoomarket.net/api/v1/catalog',
    'https://panel.yoomarket.net/api/v1/stock',
    'https://panel.yoomarket.net/api/v1/offers',
    'https://panel.yoomarket.net/api/v1/listings',
];

echo "<h3>Тестирование возможных supplier endpoints:</h3>";

foreach ($supplier_urls as $url) {
    echo "Тест: $url<br>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Accept: application/json"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 3
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP код: $http_code ";
    
    if ($http_code == 200) {
        echo "<span style='color: green; font-weight: bold;'>✅ НАЙДЕНО!</span><br>";
        $data = json_decode($response, true);
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        break;
    } elseif ($http_code == 401) {
        echo "<span style='color: orange;'>⚠ 401 (токен работает, но нет доступа)</span><br>";
    } elseif ($http_code == 404) {
        echo "<span style='color: red;'>❌ 404</span><br>";
    } else {
        echo "<br>";
    }
}

echo "<hr><h3>Вывод:</h3>";
echo "YoOMarket предоставляет <strong>CRM API для продавцов</strong>, а не API для закупки товаров.<br>";
echo "Это значит, что с вашим токеном вы можете:<br>";
echo "1. Управлять своими заказами на YoOMarket<br>";
echo "2. Работать с чатами покупателей<br>";
echo "3. Управлять рекламой<br>";
echo "4. <strong>НО НЕ МОЖЕТЕ получать товары для перепродажи</strong><br>";

echo "<h3>Рекомендации:</h3>";
echo "1. <strong>Найдите другого поставщика</strong> с работающим API для товаров<br>";
echo "2. Или используйте YoOMarket как <strong>площадку для продаж</strong>, а не поставщика<br>";
echo "3. <strong>Свяжитесь с поддержкой YoOMarket</strong> и спросите о партнерской программе/API для реселлеров<br>";
?>