<?php
// final_check.php - Финальная проверка
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Финальная проверка</title>
</head>
<body>
    <h2>🔍 Финальная проверка файлов</h2>
    
    <?php
    function checkFile($filename, $description) {
        if (!file_exists($filename)) {
            echo "<p><strong>{$description}:</strong> ❌ Файл не найден</p>";
            return;
        }
        
        $content = file_get_contents($filename);
        $content_lower = strtolower($content);
        
        $keywords = [
            'audio' => 'найдено "audio"',
            'autoplay' => 'найдено "autoplay"',
            'beep' => 'найдено "beep"',
            'sound' => 'найдено "sound"',
            'play()' => 'найдено "play()"',
            'howl' => 'найдено "howl"',
            'tone' => 'найдено "tone"'
        ];
        
        $found = [];
        foreach ($keywords as $word => $message) {
            if (strpos($content_lower, $word) !== false) {
                $found[] = $message;
            }
        }
        
        if (empty($found)) {
            echo "<p><strong>{$description} ({$filename}):</strong> ✅ ЧИСТЫЙ</p>";
        } else {
            echo "<p><strong>{$description} ({$filename}):</strong> ❌ Проблемы: " . implode(', ', $found) . "</p>";
        }
    }
    
    checkFile('templates/footer.php', 'Подвал сайта');
    checkFile('index.php', 'Главная страница');
    checkFile('templates/header.php', 'Заголовок сайта');
    
    // Проверка старых файлов
    if (file_exists('find_audio.php')) {
        echo "<p><strong>find_audio.php:</strong> ⚠️ Удалите этот файл!</p>";
    }
    if (file_exists('check_fixed.php')) {
        echo "<p><strong>check_fixed.php:</strong> ⚠️ Удалите этот файл!</p>";
    }
    ?>
    
    <hr>
    <h3>Действия:</h3>
    <ol>
        <li>Замените файлы footer.php и index.php на код выше</li>
        <li>Удалите файлы: find_audio.php, check_fixed.php, final_check.php (после проверки)</li>
        <li>Очистите кэш браузера (Ctrl+Shift+R)</li>
        <li>Перейдите на <a href="/" target="_blank">главную страницу</a></li>
    </ol>
    
    <p><a href="/" target="_blank">Перейти на сайт для проверки</a></p>
</body>
</html>