<?php
// add_columns_now.php - Добавляем ВСЕ нужные колонки
require_once 'includes/config.php';

$pdo = getDBConnection();

echo "<h2>Добавление колонок в таблицу orders</h2>";

// Колонки для добавления
$columns = [
    [
        'name' => 'product_id',
        'sql' => "ALTER TABLE orders ADD COLUMN product_id INT NOT NULL DEFAULT 0 AFTER order_number",
        'test' => "SELECT product_id FROM orders LIMIT 1"
    ],
    [
        'name' => 'product_name', 
        'sql' => "ALTER TABLE orders ADD COLUMN product_name VARCHAR(255) NOT NULL DEFAULT '' AFTER product_id",
        'test' => "SELECT product_name FROM orders LIMIT 1"
    ],
    [
        'name' => 'customer_email',
        'sql' => "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(100) NOT NULL DEFAULT '' AFTER notes",
        'test' => "SELECT customer_email FROM orders LIMIT 1"
    ],
    [
        'name' => 'login_data',
        'sql' => "ALTER TABLE orders ADD COLUMN login_data TEXT AFTER customer_email",
        'test' => "SELECT login_data FROM orders LIMIT 1"
    ],
    [
        'name' => 'password_data',
        'sql' => "ALTER TABLE orders ADD COLUMN password_data TEXT AFTER login_data",
        'test' => "SELECT password_data FROM orders LIMIT 1"
    ],
    [
        'name' => 'payment_id',
        'sql' => "ALTER TABLE orders ADD COLUMN payment_id VARCHAR(100) AFTER password_data",
        'test' => "SELECT payment_id FROM orders LIMIT 1"
    ]
];

// Проверяем существующие колонки
$existing_columns = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN, 0);

echo "<h3>Существующие колонки (" . count($existing_columns) . "):</h3>";
echo "<p>" . implode(", ", $existing_columns) . "</p>";

echo "<hr><h3>Добавление новых колонок:</h3>";

$added = 0;
foreach ($columns as $col) {
    if (in_array($col['name'], $existing_columns)) {
        echo "<p>✅ <strong>{$col['name']}</strong> - уже существует</p>";
    } else {
        try {
            // Добавляем колонку
            $pdo->exec($col['sql']);
            echo "<p style='color: green;'>✅ Добавлена колонка: <strong>{$col['name']}</strong></p>";
            $added++;
            
            // Тестируем доступ
            sleep(1); // Даем время на добавление
            $pdo->query($col['test']);
            echo "<p>   ↳ Проверка доступа: OK</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Ошибка добавления <strong>{$col['name']}</strong>: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr><h3>Итог по таблице orders:</h3>";
if ($added > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Добавлено колонок: {$added}</p>";
} else {
    echo "<p>Все колонки уже существуют</p>";
}

// Показываем обновленную структуру таблицы orders
echo "<h3>Обновленная структура таблицы orders:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $new_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
    
    foreach ($new_columns as $col) {
        $style = in_array($col['Field'], array_column($columns, 'name')) ? 'style="background: #e8f5e8;"' : '';
        echo "<tr {$style}>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>Ошибка: " . $e->getMessage() . "</p>";
}

// === ДОБАВЛЯЕМ ПОЛЕ NAME_RU В SUPPLIER_PRODUCTS ===
echo "<hr><h2>Добавление поля name_ru в таблицу supplier_products</h2>";

try {
    // Проверяем, существует ли уже поле name_ru
    $stmt = $pdo->query("SHOW COLUMNS FROM supplier_products LIKE 'name_ru'");
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "<div style='color: green; padding: 10px; background: #d4edda;'>
                ✓ Поле name_ru уже существует в supplier_products
              </div>";
    } else {
        // Добавляем поле name_ru
        $sql = "ALTER TABLE supplier_products 
                ADD COLUMN name_ru VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci 
                AFTER name";
        
        $pdo->exec($sql);
        echo "<div style='color: green; padding: 10px; background: #d4edda;'>
                ✓ Поле name_ru успешно добавлено в supplier_products
              </div>";
        
        // Показываем информацию о добавленном поле
        $stmt = $pdo->query("SHOW FULL COLUMNS FROM supplier_products WHERE Field = 'name_ru'");
        $field_info = $stmt->fetch();
        
        if ($field_info) {
            echo "<p><strong>Информация о поле:</strong></p>";
            echo "<ul>";
            echo "<li>Тип: " . $field_info['Type'] . "</li>";
            echo "<li>Кодировка: " . $field_info['Collation'] . "</li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #f8d7da;'>
            ❌ Ошибка при добавлении поля name_ru: " . $e->getMessage() . "
          </div>";
}

echo "<hr>";
echo "<p><strong>Далее:</strong></p>";
echo "<ol>";
echo "<li><a href='catalog.php'>Протестировать создание заказа</a></li>";
echo "<li><a href='view_orders_structure.php'>Посмотреть структуру</a></li>";
echo "<li>Запустить перевод товаров на русский</li>";
echo "</ol>";
?>