<?php
// api_deep_test.php - Глубокий тест API с параметрами
$api_key = "ewhynyaswwj-bnhlwq_i7spuz83lrhju8uhagbiviw1uhqqsat";
$base_url = "https://buy-accs.net/api/";

echo "<h1>🔍 Глубокое тестирование API buy-accs.net</h1>";

// 1. Тест с разными параметрами для /api/goods
echo "<h2>1. Тестируем /api/goods с параметрами</h2>";

// Параметры, которые могут влиять на вывод
$test_params = [
    'show_unavailable' => [0, 1],
    'category_id' => [1, 2, 3, 'all'],
    'game' => ['instagram', 'facebook', 'vk'],
    'limit' => [10, 50, 100],
    'offset' => [0],
    'currency' => ['RUB', 'USD'],
    'in_stock' => [1],
    'format' => ['json', 'array']
];

// Основные тесты
$tests = [
    // Тест 1: Базовый запрос с разными параметрами
    [
        'name' => 'Базовый запрос с in_stock',
        'params' => ['api_key' => $api_key, 'in_stock' => 1]
    ],
    
    // Тест 2: С определенной категорией
    [
        'name' => 'Запрос с категорией',
        'params' => ['api_key' => $api_key, 'category_id' => 'all']
    ],
    
    // Тест 3: Показ недоступных товаров
    [
        'name' => 'Показ всех товаров',
        'params' => ['api_key' => $api_key, 'show_unavailable' => 1]
    ],
    
    // Тест 4: С лимитом
    [
        'name' => 'С лимитом 10',
        'params' => ['api_key' => $api_key, 'limit' => 10]
    ],
    
    // Тест 5: POST запрос (иногда API требуют POST)
    [
        'name' => 'POST запрос',
        'params' => ['api_key' => $api_key, 'action' => 'get_goods'],
        'method' => 'POST'
    ],
    
    // Тест 6: С фильтром по игре
    [
        'name' => 'Фильтр по Instagram',
        'params' => ['api_key' => $api_key, 'game' => 'instagram']
    ],
    
    // Тест 7: Комплексный запрос
    [
        'name' => 'Комплексный фильтр',
        'params' => [
            'api_key' => $api_key,
            'in_stock' => 1,
            'limit' => 20,
            'category_id' => 'all',
            'format' => 'json'
        ]
    ]
];

foreach ($tests as $test) {
    echo "<h3>📋 Тест: " . $test['name'] . "</h3>";
    
    $url = $base_url . "goods";
    $method = $test['method'] ?? 'GET';
    
    // Формируем URL для GET или данные для POST
    if ($method === 'GET') {
        $url .= "?" . http_build_query($test['params']);
        echo "<p><small>URL: <code>" . htmlspecialchars($url) . "</code></small></p>";
        $response = makeRequest($url, 'GET');
    } else {
        echo "<p><small>POST to: <code>" . htmlspecialchars($url) . "</code></small></p>";
        echo "<p><small>Data: <code>" . htmlspecialchars(json_encode($test['params'])) . "</code></small></p>";
        $response = makeRequest($url, 'POST', $test['params']);
    }
    
    displayResponse($response);
    echo "<hr>";
}

// 2. Проверяем другие возможные эндпоинты
echo "<h2>2. Проверка дополнительных эндпоинтов</h2>";

$other_endpoints = [
    "products/list",
    "items/list", 
    "account/list",
    "goods/list",
    "stock/list",
    "getGoods",
    "getProducts",
    "all/goods",
    "all/products"
];

foreach ($other_endpoints as $endpoint) {
    $url = $base_url . $endpoint . "?api_key=" . $api_key . "&in_stock=1";
    echo "<h4>Тест: /api/" . $endpoint . "</h4>";
    
    $response = makeRequest($url);
    if ($response['code'] == 200 && !isset($response['data']['errors'])) {
        echo "<div style='color:green; padding:10px; background:#e8f5e8;'>✅ УСПЕХ! Получены данные</div>";
        analyzeDataStructure($response['data']);
    } else {
        echo "<p>Код: " . $response['code'] . " | Ответ: " . htmlspecialchars(substr(json_encode($response['data']), 0, 200)) . "</p>";
    }
}

// 3. Тестируем панель поставщика
echo "<h2>3. Проверка панели поставщика (panel.buy-accs.net)</h2>";

$panel_urls = [
    "https://panel.buy-accs.net/api/goods?api_key=" . $api_key,
    "https://panel.buy-accs.net/api/products?api_key=" . $api_key,
    "https://panel.buy-accs.net/api/stock?api_key=" . $api_key
];

foreach ($panel_urls as $url) {
    echo "<h4>URL: <code>" . htmlspecialchars($url) . "</code></h4>";
    $response = makeRequest($url);
    echo "<p>Код: " . $response['code'] . "</p>";
    
    if ($response['code'] == 200) {
        echo "<div style='color:green;'>✅ Ответ получен</div>";
        echo "<pre>" . htmlspecialchars(json_encode($response['data'], JSON_PRETTY_PRINT)) . "</pre>";
    }
}

// 4. Анализируем структуру ошибки
echo "<h2>4. Анализ структуры ошибки</h2>";

$error_url = $base_url . "goods?api_key=" . $api_key;
$error_response = makeRequest($error_url);

if (isset($error_response['data']['errors']) && is_array($error_response['data']['errors'])) {
    echo "<p>Структура ошибки:</p>";
    echo "<ul>";
    foreach ($error_response['data']['errors'] as $error_key => $error_value) {
        echo "<li><strong>" . $error_key . ":</strong> ";
        if (is_array($error_value)) {
            echo implode(", ", $error_value);
        } else {
            echo htmlspecialchars($error_value);
        }
        echo "</li>";
    }
    echo "</ul>";
}

// 5. Рекомендации
echo "<h2>🎯 Рекомендации по дальнейшим действиям</h2>";

echo "<ol>
    <li><strong>Связаться с поставщиком:</strong> Спросить точную документацию API и примеры запросов</li>
    <li><strong>Проверить доступ к panel.buy-accs.net:</strong> Возможно, нужны отдельные доступы для API поставщика</li>
    <li><strong>Протестировать с реальными параметрами:</strong> Узнать ID категорий и другие фильтры</li>
</ol>";

// Функции
function makeRequest($url, $method = 'GET', $postData = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $response;
    }
    
    return [
        'code' => $http_code,
        'data' => $data,
        'raw' => $response
    ];
}

function displayResponse($response) {
    echo "<p><strong>HTTP код:</strong> " . $response['code'] . "</p>";
    
    if ($response['code'] == 200) {
        if (is_array($response['data']) && !empty($response['data'])) {
            echo "<div style='color:green;'>✅ Получен массив данных</div>";
            echo "<p>Размер массива: " . count($response['data']) . " элементов</p>";
            
            // Показываем структуру
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Ключ</th><th>Тип</th><th>Пример значения</th></tr>";
            
            $counter = 0;
            foreach ($response['data'] as $key => $value) {
                if ($counter++ > 5) {
                    echo "<tr><td colspan='3'>... и еще " . (count($response['data']) - 5) . " элементов</td></tr>";
                    break;
                }
                
                echo "<tr>";
                echo "<td>" . $key . "</td>";
                echo "<td>" . gettype($value) . "</td>";
                echo "<td>" . htmlspecialchars(substr(json_encode($value), 0, 100)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<pre>" . htmlspecialchars(json_encode($response['data'], JSON_PRETTY_PRINT)) . "</pre>";
        }
    } else {
        echo "<p>Ответ: " . htmlspecialchars(substr($response['raw'], 0, 300)) . "</p>";
    }
}

function analyzeDataStructure($data) {
    if (is_array($data) && isset($data[0])) {
        echo "<h5>Структура первого элемента товара:</h5>";
        echo "<ul>";
        foreach ($data[0] as $key => $value) {
            echo "<li><strong>" . $key . ":</strong> " . gettype($value) . " = " . htmlspecialchars(substr(strval($value), 0, 50)) . "</li>";
        }
        echo "</ul>";
    }
}
?>