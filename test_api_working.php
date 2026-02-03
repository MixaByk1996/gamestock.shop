<?php
// test_api_working.php - Тестирование рабочего API
require_once 'includes/config.php';

$api_key = "ewhynyaswwj-bnhlwq_i7spuz83lrhju8uhagbiviw1uhqqsat";
$base_url = "https://buy-accs.net/api/";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Тест API buy-accs.net</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e8f5e8; padding: 10px; }
        .error { color: red; background: #ffebee; padding: 10px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
        .endpoint { background: #e3f2fd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
<h1>🎯 Тестирование рабочего API buy-accs.net</h1>
";

// 1. Тестируем categories эндпоинт
echo "<div class='endpoint'>";
echo "<h2>1. Тест: /api/categories</h2>";

$url_categories = $base_url . "categories?api_key=" . $api_key;
echo "<p><strong>URL:</strong> <code>" . htmlspecialchars($url_categories) . "</code></p>";

$response_categories = makeApiRequest($url_categories);
displayResponse($response_categories);

echo "</div>";

// 2. Пробуем другие возможные эндпоинты с api_key параметром
echo "<div class='endpoint'>";
echo "<h2>2. Поиск других эндпоинтов</h2>";

$possible_endpoints = [
    "products",
    "product/list", 
    "items",
    "games",
    "accounts",
    "stock",
    "balance",
    "user/info",
    "orders",
    "prices"
];

foreach ($possible_endpoints as $endpoint) {
    $url = $base_url . $endpoint . "?api_key=" . $api_key;
    echo "<h3>Тест: /api/" . $endpoint . "</h3>";
    echo "<p><small>URL: <code>" . htmlspecialchars($url) . "</code></small></p>";
    
    $response = makeApiRequest($url);
    displayResponse($response);
}

echo "</div>";

// 3. Проверяем разные форматы для products
echo "<div class='endpoint'>";
echo "<h2>3. Поиск эндпоинта для товаров</h2>";

$product_endpoints = [
    "products?api_key=" . $api_key,
    "product/list?api_key=" . $api_key,
    "items?api_key=" . $api_key,
    "games?api_key=" . $api_key,
    "accounts?api_key=" . $api_key,
    "stock?api_key=" . $api_key,
    "getProducts?api_key=" . $api_key,
    "products/all?api_key=" . $api_key,
    "products/list?api_key=" . $api_key,
];

foreach ($product_endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "<h4>" . $endpoint . "</h4>";
    
    $response = makeApiRequest($url);
    if ($response['http_code'] == 200) {
        echo "<div class='success'>✅ Найден рабочий эндпоинт для товаров!</div>";
        displayResponse($response);
        break;
    } else {
        echo "<p>Код: " . $response['http_code'] . " - " . $response['error'] . "</p>";
    }
}

echo "</div>";

// 4. Анализируем структуру ответа от categories
echo "<div class='endpoint'>";
echo "<h2>4. Анализ структуры API</h2>";

if ($response_categories['http_code'] == 200 && $response_categories['data']) {
    echo "<h3>Структура ответа categories:</h3>";
    
    // Выводим структуру JSON
    echo "<pre>";
    print_r($response_categories['data']);
    echo "</pre>";
    
    // Анализируем структуру
    echo "<h3>📊 Анализ структуры:</h3>";
    
    if (is_array($response_categories['data'])) {
        echo "<ul>";
        foreach ($response_categories['data'] as $key => $value) {
            echo "<li><strong>" . $key . ":</strong> ";
            if (is_array($value)) {
                echo "массив [" . count($value) . " элементов]";
            } elseif (is_string($value)) {
                echo "строка: " . htmlspecialchars(substr($value, 0, 50));
            } elseif (is_numeric($value)) {
                echo "число: " . $value;
            } elseif (is_bool($value)) {
                echo "булево: " . ($value ? 'true' : 'false');
            } else {
                echo gettype($value);
            }
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ Не удалось получить данные для анализа</p>";
}

echo "</div>";

// 5. Сохраняем данные в БД для теста
echo "<div class='endpoint'>";
echo "<h2>5. Сохранение данных в БД</h2>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($response_categories['http_code'] == 200 && $response_categories['data']) {
        // Создаем тестовую таблицу для категорий
        $sql = "CREATE TABLE IF NOT EXISTS api_test_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id VARCHAR(50),
            name VARCHAR(255),
            game VARCHAR(100),
            count INT DEFAULT 0,
            raw_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p class='success'>✅ Таблица для теста создана</p>";
            
            // Очищаем старые данные
            $conn->query("TRUNCATE TABLE api_test_categories");
            
            // Сохраняем данные
            $categories = $response_categories['data'];
            if (isset($categories['categories']) && is_array($categories['categories'])) {
                $stmt = $conn->prepare("INSERT INTO api_test_categories (category_id, name, game, count, raw_data) VALUES (?, ?, ?, ?, ?)");
                
                $saved = 0;
                foreach ($categories['categories'] as $cat) {
                    $category_id = $cat['id'] ?? uniqid();
                    $name = $cat['name'] ?? 'Unknown';
                    $game = $cat['game'] ?? 'Unknown';
                    $count = $cat['count'] ?? 0;
                    $raw_data = json_encode($cat);
                    
                    $stmt->bind_param("sssis", $category_id, $name, $game, $count, $raw_data);
                    $stmt->execute();
                    $saved++;
                }
                
                echo "<p class='success'>✅ Сохранено " . $saved . " категорий в БД</p>";
                
                // Показываем сохраненные данные
                $result = $conn->query("SELECT * FROM api_test_categories LIMIT 10");
                echo "<h4>Первые 10 категорий:</h4>";
                echo "<table border='1' cellpadding='10'>";
                echo "<tr><th>ID</th><th>Название</th><th>Игра</th><th>Количество</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['category_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['game']) . "</td>";
                    echo "<td>" . $row['count'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Ошибка БД: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Функции
function makeApiRequest($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'GameStock-API-Test/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $data = null;
    if ($response) {
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = $response; // Сохраняем сырой ответ если не JSON
        }
    }
    
    return [
        'http_code' => $http_code,
        'response' => $response,
        'data' => $data,
        'error' => $error
    ];
}

function displayResponse($response) {
    echo "<p><strong>HTTP код:</strong> " . $response['http_code'] . "</p>";
    
    if ($response['error']) {
        echo "<p class='error'><strong>Ошибка:</strong> " . $response['error'] . "</p>";
    }
    
    if ($response['http_code'] == 200) {
        echo "<div class='success'>✅ Успешный ответ!</div>";
        
        if (is_array($response['data'])) {
            echo "<h4>Данные (первые 500 символов):</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        } else {
            echo "<h4>Ответ:</h4>";
            echo "<pre>" . htmlspecialchars(substr($response['response'], 0, 500)) . "...</pre>";
        }
    } elseif ($response['http_code'] == 401) {
        echo "<p class='error'>❌ Ошибка авторизации (неверный ключ или метод)</p>";
    } elseif ($response['http_code'] == 404) {
        echo "<p class='error'>❌ Эндпоинт не найден</p>";
    } else {
        echo "<p>Ответ: " . htmlspecialchars(substr($response['response'] ?? '', 0, 200)) . "</p>";
    }
}

echo "<hr>";
echo "<h2 class='success'>✅ ЭТАП 3.2 ЗАВЕРШЕН!</h2>";
echo "<p>Найден рабочий эндпоинт: <code>/api/categories?api_key=ВАШ_КЛЮЧ</code></p>";
echo "<p><a href='create_api_class.php'>Перейти к созданию класса API →</a></p>";

echo "</body></html>";
?>