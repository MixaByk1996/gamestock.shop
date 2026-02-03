<?php
// analyze_documentation.php

$doc_url = 'https://panel.yoomarket.net/docs/integration/v1/ru';

echo "<h2>Анализ документации YoOMarket API</h2>";

// Получаем HTML документации
$ch = curl_init($doc_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10
]);

$html = curl_exec($ch);
curl_close($ch);

// Сохраняем для анализа
file_put_contents('yoomarket_docs_full.html', $html);

echo "Размер документации: " . strlen($html) . " байт<br><br>";

// Ищем примеры запросов
echo "<h3>Ищем примеры API запросов:</h3>";

// Ищем все <pre>, <code> блоки
if (preg_match_all('/<pre[^>]*>(.*?)<\/pre>/is', $html, $pre_matches) ||
    preg_match_all('/<code[^>]*>(.*?)<\/code>/is', $html, $code_matches)) {
    
    $all_matches = array_merge($pre_matches[1] ?? [], $code_matches[1] ?? []);
    
    foreach ($all_matches as $code) {
        $code = html_entity_decode(strip_tags($code));
        
        // Ищем упоминания API
        if (strpos($code, 'api') !== false || 
            strpos($code, 'curl') !== false ||
            strpos($code, 'http') !== false) {
            
            echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; border: 1px solid #ddd;'>";
            echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($code) . "</pre>";
            echo "</div>";
        }
    }
}

// Ищем JavaScript переменные (могут содержать URL API)
echo "<h3>Ищем JavaScript переменные:</h3>";
if (preg_match_all('/var\s+(\w+)\s*=\s*["\']([^"\']+)["\']/i', $html, $js_vars)) {
    for ($i = 0; $i < count($js_vars[0]); $i++) {
        $var_name = $js_vars[1][$i];
        $var_value = $js_vars[2][$i];
        
        if (strpos($var_value, 'api') !== false || 
            strpos($var_value, 'yoomarket') !== false) {
            echo "$var_name = '$var_value'<br>";
        }
    }
}

// Ищем ссылки на OpenAPI/Swagger
echo "<h3>Ищем спецификации OpenAPI:</h3>";
if (preg_match_all('/["\']([^"\']*openapi[^"\']*\.(json|yaml|yml))["\']/i', $html, $openapi_matches)) {
    foreach ($openapi_matches[1] as $spec_url) {
        echo "Найдена спецификация: $spec_url<br>";
        
        // Проверяем доступность
        if (strpos($spec_url, 'http') === 0) {
            $full_url = $spec_url;
        } else {
            $full_url = 'https://panel.yoomarket.net' . (strpos($spec_url, '/') === 0 ? '' : '/') . $spec_url;
        }
        
        echo "Проверяем: $full_url<br>";
        
        $ch = curl_init($full_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5
        ]);
        
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Статус: HTTP $code<br><br>";
    }
}

// Ищем информацию об аутентификации
echo "<h3>Ищем информацию об аутентификации:</h3>";

$auth_keywords = ['Authorization', 'Bearer', 'X-API', 'api_key', 'token', 'authenticat'];
foreach ($auth_keywords as $keyword) {
    if (stripos($html, $keyword) !== false) {
        $pos = stripos($html, $keyword);
        $context = substr($html, max(0, $pos - 100), 300);
        echo "<div style='border:1px solid #ccc; padding:10px; margin:5px;'>";
        echo "Найдено '$keyword':<br>";
        echo htmlspecialchars($context);
        echo "</div>";
    }
}

// Пробуем найти реальные примеры из JavaScript
echo "<h3>Анализ JavaScript кода:</h3>";
if (preg_match_all('/fetch\(["\']([^"\']+)["\']/i', $html, $fetch_matches)) {
    foreach ($fetch_matches[1] as $fetch_url) {
        echo "Fetch запрос к: $fetch_url<br>";
    }
}

// Выводим часть HTML для ручного просмотра
echo "<h3>Часть HTML документации (для анализа):</h3>";
echo "<textarea style='width:100%; height:300px; font-size:12px;'>";
echo htmlspecialchars(substr($html, 0, 5000));
echo "</textarea>";
?>