<?php
// /www/gamestock.shop/check_product_structure.php

require_once 'includes/config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>Структура таблицы supplier_products:</h2>";
    
    // Проверяем структуру таблицы
    $stmt = $pdo->query("DESCRIBE supplier_products");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Проверяем, есть ли уже поле для русского названия
    $has_russian_field = false;
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['name_ru', 'description_ru', 'rus_name', 'russian_name'])) {
            $has_russian_field = true;
            break;
        }
    }
    
    echo "<br><h3>Текущий статус:</h3>";
    if ($has_russian_field) {
        echo "<div style='color: green;'>✓ Поле для русского описания уже существует</div>";
    } else {
        echo "<div style='color: orange;'>✗ Поле для русского описания отсутствует</div>";
    }
    
    // Посмотрим несколько примеров товаров
    echo "<h2>Примеры товаров (первые 10):</h2>";
    $stmt = $pdo->query("SELECT id, name, category, stock FROM supplier_products LIMIT 10");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<strong>ID:</strong> {$product['id']}<br>";
        echo "<strong>Name (EN):</strong> " . htmlspecialchars($product['name']) . "<br>";
        echo "<strong>Category:</strong> {$product['category']}<br>";
        echo "<strong>Stock:</strong> {$product['stock']}<br>";
        echo "</div>";
    }
    
    // Проверим, из каких категорий товары
    echo "<h2>Категории товаров:</h2>";
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM supplier_products GROUP BY category ORDER BY count DESC");
    $categories = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li>Категория {$cat['category']}: {$cat['count']} товаров</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Ошибка: " . $e->getMessage() . "</div>";
}
?>