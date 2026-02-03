<?php
// get_openapi_spec.php

$openapi_url = 'https://panel.yoomarket.net/docs/openapi.yaml';

echo "<h2>Получение OpenAPI спецификации YoOMarket</h2>";

// Получаем YAML спецификацию
$ch = curl_init($openapi_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_TIMEOUT => 10
]);

$yaml_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && !empty($yaml_content)) {
    echo "✅ OpenAPI спецификация получена (" . strlen($yaml_content) . " байт)<br>";
    
    // Сохраняем файл
    file_put_contents('yoomarket_openapi.yaml', $yaml_content);
    
    // Парсим YAML (если установлено расширение yaml)
    if (function_exists('yaml_parse')) {
        $spec = yaml_parse($yaml_content);
        echo "<h3>Анализ спецификации:</h3>";
        echo "<pre>";
        print_r($spec);
        echo "</pre>";
    } else {
        // Или выводим часть для анализа
        echo "<h3>Первые 2000 символов YAML:</h3>";
        echo "<textarea style='width:100%; height:400px; font-family: monospace;'>";
        echo htmlspecialchars(substr($yaml_content, 0, 2000));
        echo "</textarea>";
        
        // Ищем ключевые части
        echo "<h3>Поиск ключевой информации:</h3>";
        
        // Серверы
        if (preg_match('/servers:\s*\n\s*-\s*url:\s*[\'"]([^\'"]+)[\'"]/i', $yaml_content, $server_match)) {
            echo "<strong>Базовый URL сервера:</strong> " . $server_match[1] . "<br>";
        }
        
        // Безопасность/аутентификация
        if (preg_match('/securitySchemes:\s*\n(.*?)\n\s*\w+:/is', $yaml_content, $security_match)) {
            echo "<strong>Схемы безопасности:</strong><br>";
            echo "<pre>" . htmlspecialchars($security_match[1]) . "</pre>";
        }
        
        // Пути (endpoints)
        if (preg_match('/paths:\s*\n(.*?)(?=\n\w+:|\Z)/is', $yaml_content, $paths_match)) {
            echo "<strong>Найденные endpoints:</strong><br>";
            $lines = explode("\n", $paths_match[1]);
            foreach ($lines as $line) {
                if (preg_match('/^\s*(\/[^:]+):/', $line, $path_match)) {
                    echo "- " . trim($path_match[1]) . "<br>";
                }
            }
        }
    }
    
    // Конвертируем YAML в JSON для удобства
    echo "<h3>Конвертация YAML в JSON:</h3>";
    $json_content = json_encode(yaml_parse($yaml_content), JSON_PRETTY_PRINT);
    file_put_contents('yoomarket_openapi.json', $json_content);
    echo "Спецификация сохранена как JSON: yoomarket_openapi.json<br>";
    
} else {
    echo "❌ Не удалось получить OpenAPI спецификацию<br>";
    echo "HTTP код: $http_code<br>";
}

// Прямой анализ через запрос
echo "<hr><h2>Прямой анализ через cURL:</h2>";

// Получаем спецификацию и сразу ищем endpoints
$ch = curl_init($openapi_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);

$yaml = curl_exec($ch);
curl_close($ch);

// Простой парсинг для нахождения endpoints
if ($yaml) {
    echo "<h3>Извлечение endpoints из спецификации:</h3>";
    
    // Ищем все пути
    if (preg_match_all('/^\s*(\/[^:]+):\s*$/m', $yaml, $endpoint_matches)) {
        echo "<strong>Все endpoints API:</strong><br>";
        foreach ($endpoint_matches[1] as $endpoint) {
            echo "- " . trim($endpoint) . "<br>";
        }
    }
    
    // Ищем информацию о безопасности
    if (preg_match('/securitySchemes:\s*\n\s*([\w-]+):\s*\n\s*type:\s*([^\n]+)/i', $yaml, $security_match)) {
        echo "<strong>Тип аутентификации:</strong> " . $security_match[2] . "<br>";
        echo "<strong>Название схемы:</strong> " . $security_match[1] . "<br>";
    }
    
    // Ищем базовый URL
    if (preg_match('/url:\s*[\'"]([^\'"]+)[\'"]/i', $yaml, $url_match)) {
        echo "<strong>Базовый URL API:</strong> " . $url_match[1] . "<br>";
    }
}
?>