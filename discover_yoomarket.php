<?php
// discover_yoomarket.php

// Подключаем класс
require_once 'includes/ApiSuppliers/YoOMarket.php';

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';

echo "<h2>Обнаружение endpoints YoOMarket API</h2>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
</style>";

// Тестируем разные base URLs
$base_urls = [
    'https://panel.yoomarket.net/api/',
    'https://panel.yoomarket.net/api/v1/',
    'https://api.yoomarket.net/api/v1/',
    'https://yoomarket.net/api/v1/',
    'https://panel.yoomarket.net/',
    'https://api.yoomarket.net/'
];

foreach ($base_urls as $base_url) {
    echo "<h3>Тестируем: $base_url</h3>";
    
    // Создаем объект с текущим base_url
    $yoomarket = new YoOMarket($token, $base_url);
    
    // Тест подключения
    $test = $yoomarket->testConnection();
    
    if ($test['success']) {
        echo "<div class='success'>✅ " . $test['message'] . "</div>";
        
        // Если подключение успешно, ищем endpoints
        echo "<h4>Обнаружение endpoints:</h4>";
        $discovery = $yoomarket->discoverEndpoints();
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Endpoint</th><th>Статус</th><th>Код</th><th>URL</th></tr>";
        
        foreach ($discovery as $name => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $color = $result['success'] ? 'green' : 'red';
            echo "<tr>
                <td><strong>$name</strong> ({$result['endpoint']})</td>
                <td style='color: $color'>$status</td>
                <td>{$result['http_code']}</td>
                <td style='font-size: 12px;'>{$result['url']}</td>
            </tr>";
            
            // Если endpoint найден, покажем структуру ответа
            if ($result['success'] && $name == 'goods') {
                echo "<tr><td colspan='4'>";
                echo "Структура ответа goods: <pre>";
                print_r($result['response']);
                echo "</pre>";
                echo "</td></tr>";
            }
        }
        
        echo "</table>";
        
        // Пробуем получить товары
        if (isset($discovery['goods']['success']) && $discovery['goods']['success']) {
            echo "<h4>Тест получения товаров:</h4>";
            $goods = $yoomarket->getGoods(['limit' => 2]);
            echo "<pre>";
            print_r($goods);
            echo "</pre>";
        }
        
    } else {
        echo "<div class='error'>❌ " . $test['message'] . "</div>";
    }
    
    echo "<hr>";
}
?>