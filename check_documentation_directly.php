<?php
// check_documentation_directly.php

echo "<h2>Прямая проверка документации YoOMarket</h2>";

$doc_url = 'https://panel.yoomarket.net/docs/integration/v1/ru';

// Пробуем получить документацию разными способами
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
    ]
]);

$html = @file_get_contents($doc_url, false, $context);

if ($html) {
    echo "<h3>HTML документации получен (" . strlen($html) . " байт)</h3>";
    
    // Сохраним для анализа
    file_put_contents('yoomarket_docs.html', $html);
    
    // Поиск всех URL в документации
    preg_match_all('/["\'](https?:\/\/[^"\']*\/api[^"\']*)["\']/i', $html, $api_urls);
    preg_match_all('/["\'](\/api[^"\']*)["\']/i', $html, $relative_urls);
    
    echo "<h4>Найденные API URLs:</h4>";
    if (!empty($api_urls[1])) {
        foreach (array_unique($api_urls[1]) as $url) {
            echo "- $url<br>";
        }
    } else {
        echo "Не найдено<br>";
    }
    
    echo "<h4>Относительные пути API:</h4>";
    if (!empty($relative_urls[1])) {
        foreach (array_unique($relative_urls[1]) as $url) {
            echo "- $url<br>";
            // Пробуем с базовым URL
            $full_url = 'https://panel.yoomarket.net' . $url;
            echo "&nbsp;&nbsp;&nbsp;&nbsp;→ $full_url<br>";
        }
    } else {
        echo "Не найдено<br>";
    }
    
    // Поиск примеров кода
    if (preg_match_all('/<code[^>]*>([^<]*)<\/code>/i', $html, $code_blocks)) {
        echo "<h4>Примеры кода в документации:</h4>";
        foreach ($code_blocks[1] as $code) {
            if (strpos($code, 'api') !== false || strpos($code, 'http') !== false) {
                echo "<pre>" . htmlspecialchars($code) . "</pre><hr>";
            }
        }
    }
    
    // Поиск по ключевым словам
    $keywords = ['endpoint', 'baseUrl', 'baseURL', 'API_URL', 'url', 'route'];
    echo "<h4>Поиск ключевых слов:</h4>";
    foreach ($keywords as $keyword) {
        if (stripos($html, $keyword) !== false) {
            $pos = stripos($html, $keyword);
            $snippet = substr($html, max(0, $pos - 50), 200);
            echo "<strong>$keyword</strong>: ..." . htmlspecialchars($snippet) . "...<br><br>";
        }
    }
    
} else {
    echo "❌ Не удалось получить документацию<br>";
    
    // Попробуем через curl
    $ch = curl_init($doc_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_TIMEOUT => 10
    ]);
    
    $html = curl_exec($ch);
    if ($html) {
        echo "✅ Получили через CURL (" . strlen($html) . " байт)<br>";
        file_put_contents('yoomarket_docs_curl.html', $html);
    } else {
        echo "❌ Не работает даже через CURL<br>";
    }
    curl_close($ch);
}
?>