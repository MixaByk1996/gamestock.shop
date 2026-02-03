<?php
// test_api_direct.php - прямой тест API

$api_key = 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a';

// Тест 1: Баланс
echo "<h3>1. Тест баланса:</h3>";
$balance_url = 'https://buy-accs.net/api/v1/balance/get';
$balance_data = [
    'api_key' => $api_key,
    'currency' => 'rub'
];

$ch = curl_init($balance_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($balance_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response1 = curl_exec($ch);
curl_close($ch);

echo "URL: " . $balance_url . "<br>";
echo "Raw response: <pre>" . htmlspecialchars($response1) . "</pre><br>";

// Тест 2: Список товаров
echo "<h3>2. Тест списка товаров:</h3>";
$products_url = 'https://buy-accs.net/api/v1/products';
$products_data = [
    'api_key' => $api_key,
    'currency' => 'rub'
];

$ch = curl_init($products_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($products_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response2 = curl_exec($ch);
curl_close($ch);

echo "URL: " . $products_url . "<br>";
echo "Raw response: <pre>" . htmlspecialchars($response2) . "</pre>";

// Тест 3: Простой GET запрос
echo "<h3>3. Простой GET запрос к API:</h3>";
$test_url = 'https://buy-accs.net/api/v1/test';
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response3 = curl_exec($ch);
curl_close($ch);

echo "URL: " . $test_url . "<br>";
echo "Raw response: <pre>" . htmlspecialchars($response3) . "</pre>";

// Тест 4: Проверка доступности API
echo "<h3>4. Проверка доступности API:</h3>";
$ch = curl_init('https://buy-accs.net/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response4 = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP код: " . $http_code . "<br>";
echo "Сайт доступен: " . ($http_code == 200 ? 'ДА' : 'НЕТ');
?>