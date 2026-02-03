<?php
// final_yoomarket_api_test.php

$token = 'ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e';
$openapi_url = 'https://panel.yoomarket.net/docs/openapi.yaml';

echo "<h2>Финальный тест YoOMarket API на основе OpenAPI спецификации</h2>";

// 1. Получаем спецификацию
echo "<h3>1. Получение OpenAPI спецификации...</h3>";
$ch = curl_init($openapi_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$yaml = curl_exec($ch);
$yaml_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($yaml_http_code != 200 || empty($yaml)) {
    die("❌ Не удалось получить OpenAPI спецификацию");
}

echo "✅ Спецификация получена (" . strlen($yaml) . " байт)<br>";

// 2. Извлекаем базовый URL
echo "<h3>2. Поиск базового URL API...</h3>";
$base_url = 'https://panel.yoomarket.net/api/v1/'; // значение по умолчанию

if (preg_match('/servers:\s*\n\s*-\s*url:\s*[\'"]([^\'"]+)[\'"]/i', $yaml, $server_match)) {
    $base_url = rtrim($server_match[1], '/') . '/';
    echo "✅ Найден в спецификации: $base_url<br>";
} else {
    echo "⚠ Используем URL по умолчанию: $base_url<br>";
}

// 3. Извлекаем endpoints
echo "<h3>3. Поиск endpoints...</h3>";
$endpoints = [];

if (preg_match_all('/^\s*(\/[^:]+):\s*$/m', $yaml, $matches)) {
    foreach ($matches[1] as $endpoint) {
        $endpoint = trim($endpoint);
        if (!in_array($endpoint, ['/', '//'])) {
            $endpoints[] = $endpoint;
        }
    }
}

echo "Найдено endpoints: " . count($endpoints) . "<br>";
if (count($endpoints) > 0) {
    echo "Примеры:<br>";
    foreach (array_slice($endpoints, 0, 5) as $endpoint) {
        echo "- $endpoint<br>";
    }
}

// 4. Извлекаем информацию об аутентификации
echo "<h3>4. Анализ аутентификации...</h3>";
$auth_type = 'Bearer';
$auth_header_name = 'Authorization';

if (preg_match('/type:\s*(apiKey|http|oauth2)/i', $yaml, $auth_match)) {
    $auth_type = strtolower(trim($auth_match[1]));
    echo "Тип аутентификации: $auth_type<br>";
}

if (preg_match('/name:\s*[\'"]([^\'"]+)[\'"]/i', $yaml, $name_match)) {
    $auth_header_name = trim($name_match[1]);
    echo "Имя параметра/заголовка: $auth_header_name<br>";
}

// 5. Тестируем найденные endpoints
echo "<h3>5. Тестирование API с найденными параметрами...</h3>";

$test_endpoints = [
    '/products',
    '/goods', 
    '/items',
    '/accounts',
    '/user',
    '/me',
    '/balance',
    '/orders',
    '/store/products',
    '/api/products'
];

// Фильтруем только существующие endpoints
$existing_endpoints = [];
foreach ($test_endpoints as $test_ep) {
    foreach ($endpoints as $ep) {
        if (strpos($ep, $test_ep) !== false || similar_text($ep, $test_ep) > 5) {
            $existing_endpoints[] = $ep;
        }
    }
}

// Если не нашли совпадений, берем первые 5 endpoints
if (empty($existing_endpoints)) {
    $existing_endpoints = array_slice($endpoints, 0, 5);
}

echo "<strong>Тестируемые endpoints:</strong><br>";
foreach ($existing_endpoints as $ep) {
    echo "- $ep<br>";
}

// Тестируем каждый endpoint
foreach ($existing_endpoints as $endpoint) {
    $url = $base_url . ltrim($endpoint, '/');
    
    echo "<h4>Тест: $endpoint</h4>";
    echo "URL: $url<br>";
    
    // Пробуем разные методы аутентификации
    $auth_methods = [
        ["$auth_header_name: Bearer $token"],
        ["$auth_header_name: Token $token"],
        ["X-API-Key: $token"],
        ["$auth_header_name: $token"],
    ];
    
    $success = false;
    
    foreach ($auth_methods as $headers) {
        $ch = curl_init($url);
        
        $all_headers = array_merge($headers, [
            "Accept: application/json",
            "User-Agent: YoOMarket-Integration/1.0"
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => $all_headers,
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Метод: " . implode(", ", $headers) . " → HTTP: $http_code ";
        
        if ($http_code == 200) {
            echo "<span style='color: green; font-weight: bold;'>✅ УСПЕХ!</span><br>";
            $data = json_decode($response, true);
            echo "<pre style='background: #f5f5f5; padding: 10px;'>";
            print_r($data);
            echo "</pre>";
            $success = true;
            break;
        } elseif ($http_code == 401) {
            echo "<span style='color: orange;'>⚠ 401 Unauthorized</span><br>";
        } elseif ($http_code == 404) {
            echo "<span style='color: red;'>❌ 404 Not Found</span><br>";
        } else {
            echo "<br>";
        }
    }
    
    if ($success) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        echo "<strong>🎉 API РАБОТАЕТ!</strong><br>";
        echo "Используйте:<br>";
        echo "- Base URL: $base_url<br>";
        echo "- Endpoint: $endpoint<br>";
        echo "- Заголовок: " . $all_headers[0] . "<br>";
        echo "</div>";
        break;
    }
    
    echo "<hr>";
}

if (!$success) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;'>";
    echo "<h3>❌ Не удалось подключиться к API</h3>";
    echo "<strong>Рекомендации:</strong><br>";
    echo "1. Проверьте, активирован ли токен в панели YoOMarket<br>";
    echo "2. Убедитесь, что у токена есть необходимые права<br>";
    echo "3. Проверьте, не заблокирован ли доступ с вашего IP<br>";
    echo "4. Свяжитесь с поддержкой YoOMarket<br>";
    echo "</div>";
    
    // Показываем часть спецификации для отладки
    echo "<h3>Для отладки (первые 1000 символов спецификации):</h3>";
    echo "<textarea style='width:100%; height:300px; font-family: monospace; font-size: 12px;'>";
    echo htmlspecialchars(substr($yaml, 0, 1000));
    echo "</textarea>";
}

// Создаем рабочий класс на основе найденной информации
echo "<h3>6. Создание рабочего класса YoOMarket:</h3>";

$working_class = <<<EOD
<?php
// includes/ApiSuppliers/YoOMarket_Working.php

class YoOMarket_Working {
    private \$api_token;
    private \$api_url = "{$base_url}";
    
    public function __construct(\$api_token) {
        \$this->api_token = \$api_token;
    }
    
    /**
     * Основной метод запроса
     */
    private function makeRequest(\$endpoint, \$params = [], \$method = "GET") {
        \$url = \$this->api_url . ltrim(\$endpoint, '/');
        
        \$ch = curl_init();
        
        // Настройки аутентификации (на основе тестов)
        \$headers = [
            "Authorization: Bearer {\$this->api_token}", // Или другой метод
            "Accept: application/json",
            "User-Agent: GameStock-Shop/1.0"
        ];
        
        curl_setopt_array(\$ch, [
            CURLOPT_URL => \$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => \$headers
        ]);
        
        // Для POST запросов
        if (\$method === "POST") {
            curl_setopt(\$ch, CURLOPT_POST, true);
            \$headers[] = "Content-Type: application/json";
            curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$params));
        }
        // Для GET с параметрами
        elseif (\$method === "GET" && !empty(\$params)) {
            \$url .= "?" . http_build_query(\$params);
            curl_setopt(\$ch, CURLOPT_URL, \$url);
        }
        
        \$response = curl_exec(\$ch);
        \$http_code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
        curl_close(\$ch);
        
        \$data = json_decode(\$response, true);
        
        return [
            "success" => (\$http_code == 200),
            "http_code" => \$http_code,
            "data" => \$data
        ];
    }
    
    /**
     * Тест подключения
     */
    public function testConnection() {
        // Пробуем разные endpoints
        \$test_endpoints = ['/user', '/me', '/balance', '/products'];
        
        foreach (\$test_endpoints as \$endpoint) {
            \$result = \$this->makeRequest(\$endpoint);
            if (\$result['success']) {
                return [
                    'success' => true,
                    'message' => "✅ API работает (endpoint: \$endpoint)",
                    'endpoint' => \$endpoint
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => "❌ Не удалось подключиться к API"
        ];
    }
    
    /**
     * Получение товаров
     */
    public function getGoods(\$params = []) {
        return \$this->makeRequest('/products', \$params);
    }
}
?>
EOD;

echo "<pre style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd;'>";
echo htmlspecialchars($working_class);
echo "</pre>";
?>