<?php
// find_api_methods.php - Поиск методов API buy-accs.net
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$api_key = 'ВАШ_API_КЛЮЧ'; // Замените на ваш реальный API ключ
$api = new BuyAccsNet($api_key);

echo "<h2>Поиск методов API buy-accs.net</h2>";

// Попробуем разные URL
$test_urls = [
    'https://buy-accs.net/api/order/create',
    'https://buy-accs.net/api/purchase',
    'https://buy-accs.net/api/account/get',
    'https://buy-accs.net/api/orders',
    'https://buy-accs.net/api/buy'
];

foreach ($test_urls as $url) {
    echo "<h3>Тестируем: $url</h3>";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . '?api_key=' . $api_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        echo "<div style='background:#d4edda; padding:10px; margin:5px;'>";
        echo "✅ Успешно (код: $http_code)<br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da; padding:10px; margin:5px;'>";
        echo "❌ Ошибка (код: $http_code)<br>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    }
}
?>