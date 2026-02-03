<?php
// check_fixed.php - Проверка после исправлений
echo "<h2>✅ Проверка исправлений</h2>";

$files = [
    'templates/footer.php' => 'Подвал сайта',
    'index.php' => 'Главная страница',
    'templates/header.php' => 'Заголовок сайта'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_audio = stripos($content, 'audio') !== false;
        $has_autoplay = stripos($content, 'autoplay') !== false;
        
        echo "<p><strong>{$description} ({$file}):</strong> ";
        if (!$has_audio && !$has_autoplay) {
            echo "✅ Чистый (без аудио)";
        } else {
            echo "❌ Есть аудио элементы";
            if ($has_audio) echo " (найдено 'audio')";
            if ($has_autoplay) echo " (найдено 'autoplay')";
        }
        echo "</p>";
    } else {
        echo "<p><strong>{$description}:</strong> ❌ Файл не найден</p>";
    }
}

echo "<hr><p><a href='/' target='_blank'>Перейти на сайт</a> (откройте в новой вкладке и проверьте)</p>";
?>