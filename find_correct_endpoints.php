<?php
// find_correct_endpoints.php
$api_key = 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a';

// Проверим разные варианты endpoint'ов
$test_cases = [
    // Баланс
    ['name' => 'Balance 1', 'url' => 'https://buy-accs.net/api/balance', 'data' => ['api_key' => $api_key]],
    ['name' => 'Balance 2', 'url' => 'https://buy-accs.net/api/getBalance', 'data' => ['api_key' => $api_key]],
    ['name' => 'Balance 3', 'url' => 'https://buy-accs.net/api/v1/user/balance', 'data' => ['api_key' => $api_key, 'currency' => 'RUB']],
    ['name' => 'Balance 4', 'url' => 'https://buy-accs.net/api/v1/balance', 'data' => ['api_key' => $api_key]],
    ['name' => 'Balance 5', 'url' => 'https://buy-accs.net/balance', 'data' => ['api_key' => $api_key]],
    
    // Товары
    ['name' => 'Products 1', 'url' => 'https://buy-accs.net/api/products', 'data' => ['api_key' => $api_key]],
    ['name' => 'Products 2', 'url' => 'https://buy-accs.net/api/getProducts', 'data' => ['api_key' => $api_key]],
    ['name' => 'Products 3', 'url' => 'https://buy-accs.net/api/v1/products', 'data' => ['api_key' => $api_key]],
    ['name' => 'Products 4', 'url' => 'https://buy-accs.net/api/v1/goods', 'data' => ['api_key' => $api_key]],
    ['name' => 'Products 5', 'url' => 'https://buy-accs.net/products', 'data' => ['api_key' => $api_key]],
    
    // Покупка
    ['name' => 'Order 1', 'url' => 'https://buy-accs.net/api/order', 'data' => ['api_key' => $api_key, 'product_id' => 1, 'quantity' => 1]],
    ['name' => 'Order 2', 'url' => 'https://buy-accs.net/api/createOrder', 'data' => ['api_key' => $api_key, 'product_id' => 1, 'quantity' => 1]],
    ['name' => 'Order 3', 'url' => 'https://buy-accs.net/api/v1/order', 'data' => ['api_key' => $api_key, 'product_id' => 1]],
    
    // Документация/инфо
    ['name' => 'API info', 'url' => 'https://buy-accs.net/api/', 'data' => []],
    ['name' => 'API v1 info', 'url' => 'https://buy-accs.net/api/v1/', 'data' => []],
];

foreach ($test_cases as $test) {
    echo "<h3>" . $test['name'] . " (" . $test['url'] . ")</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if (!empty($test['data'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test['data']));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP код: " . $http_code . "<br>";
    
    if ($error) {
        echo "Ошибка CURL: " . $error . "<br>";
    }
    
    echo "Ответ (первые 500 символов):<br>";
    echo "<pre style='background:#f0f0f0;padding:10px;border:1px solid #ccc;max-height:200px;overflow:auto;'>";
    echo htmlspecialchars(substr($response, 0, 500));
    echo "</pre>";
    
    // Проверим JSON
    if ($response) {
        $json = json_decode($response, true);
        if ($json) {
            echo "JSON распарсен успешно<br>";
            echo "Структура ответа:<br>";
            echo "<pre>";
            print_r($json);
            echo "</pre>";
        } else {
            echo "Не JSON или невалидный JSON<br>";
        }
    }
    
    echo "<hr>";
}
?>