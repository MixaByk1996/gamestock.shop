<?php
// test_buyaccs_get_method.php
$api_key = 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a';

// Тест 1: Баланс через GET с параметрами в URL
echo "<h3>1. Баланс через GET:</h3>";
$url1 = 'https://buy-accs.net/api/balance?api_key=' . urlencode($api_key);
echo "URL: " . $url1 . "<br>";

$ch = curl_init($url1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response1 = curl_exec($ch);
curl_close($ch);

echo "Ответ: <pre>" . htmlspecialchars($response1) . "</pre><br>";

// Тест 2: Баланс через POST
echo "<h3>2. Баланс через POST:</h3>";
$url2 = 'https://buy-accs.net/api/balance';
$data2 = ['api_key' => $api_key];

$ch = curl_init($url2);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data2));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response2 = curl_exec($ch);
curl_close($ch);

echo "Ответ: <pre>" . htmlspecialchars($response2) . "</pre><br>";

// Тест 3: Попробуем другие endpoint'ы через POST
$endpoints = [
    'goods' => 'https://buy-accs.net/api/goods',
    'products' => 'https://buy-accs.net/api/products',
    'order' => 'https://buy-accs.net/api/order',
    'orders' => 'https://buy-accs.net/api/orders',
    'create' => 'https://buy-accs.net/api/create',
    'buy' => 'https://buy-accs.net/api/buy',
    'purchase' => 'https://buy-accs.net/api/purchase'
];

foreach ($endpoints as $name => $url) {
    echo "<h3>3. Тест endpoint '$name': $url</h3>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['api_key' => $api_key]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP код: $http_code<br>";
    echo "Ответ: <pre>" . htmlspecialchars($response) . "</pre><hr>";
}

// Тест 4: Посмотрим что вернет сайт при запросе документации
echo "<h3>4. Поиск документации API:</h3>";
$doc_urls = [
    'https://buy-accs.net/api/docs',
    'https://buy-accs.net/api/documentation',
    'https://buy-accs.net/apidocs',
    'https://buy-accs.net/api/v1/docs',
    'https://buy-accs.net/help/api'
];

foreach ($doc_urls as $url) {
    echo "Проверка: $url<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        echo "Найдена документация!<br>";
        // Ищем упоминания API в HTML
        if (strpos($response, 'api') !== false || strpos($response, 'API') !== false) {
            echo "Содержит информацию об API<br>";
        }
        break;
    }
}
?>