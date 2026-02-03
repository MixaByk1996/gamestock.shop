<?php
// test_api_find.php
$api_key = "ewhynyaswwj-bnhlwq_i7spuz83lrhju8uhagbiviw1uhqqsat";
$base_url = "https://buy-accs.net/";

// Список возможных эндпоинтов (обычно такие у поставщиков)
$possible_endpoints = [
    "/api/products",
    "/api/v1/products", 
    "/api/items",
    "/api/games",
    "/api/accounts",
    "/api/stock",
    "/api/getProducts",
    "/api/product/list",
    "/api/categories",
    "/api/balance",
    "/api/test",
    "/api/version"
];

echo "<h2>🔍 Поиск рабочих эндпоинтов API</h2>";
echo "<p>API Key: <code>" . substr($api_key, 0, 15) . "...</code></p>";

foreach ($possible_endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
    echo "<strong>Тестируем:</strong> <code>" . $url . "</code><br>";
    
    // Вариант 1: Без ключа
    $response1 = testEndpoint($url);
    echo "Без ключа: " . formatResponse($response1) . "<br>";
    
    // Вариант 2: С ключом как GET параметр
    $url_with_key = $url . "?key=" . $api_key;
    $response2 = testEndpoint($url_with_key);
    echo "С ключом (GET): " . formatResponse($response2) . "<br>";
    
    // Вариант 3: С ключом в заголовке
    $response3 = testEndpoint($url, $api_key);
    echo "С ключом (Header): " . formatResponse($response3);
    
    echo "</div>";
}

function testEndpoint($url, $api_key = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $headers = ['Accept: application/json'];
    if ($api_key) {
        $headers[] = 'Authorization: Bearer ' . $api_key;
        $headers[] = 'X-API-Key: ' . $api_key;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $http_code,
        'response' => $response
    ];
}

function formatResponse($response) {
    if ($response['code'] == 0) {
        return "❌ Нет соединения";
    } elseif ($response['code'] == 200) {
        $data = json_decode($response['response'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return "✅ 200 OK (JSON, " . strlen($response['response']) . " байт)";
        } else {
            return "✅ 200 OK (не JSON, " . strlen($response['response']) . " байт)";
        }
    } elseif ($response['code'] == 403) {
        return "🔒 403 Forbidden (нужен ключ)";
    } elseif ($response['code'] == 404) {
        return "❓ 404 Not Found";
    } elseif ($response['code'] == 401) {
        return "🔑 401 Unauthorized (неверный ключ)";
    } else {
        return "📡 " . $response['code'] . " код";
    }
}
?>