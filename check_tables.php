<?php
// check_tables.php - Проверка таблиц в БД
require_once 'includes/config.php';

$pdo = getDBConnection();

echo "<h2>Проверка таблиц в базе данных</h2>";
echo "<p>База данных: " . DB_NAME . "</p>";

// 1. Показать все таблицы
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ В базе данных нет таблиц!</p>";
    } else {
        echo "<h3>Найдены таблицы (" . count($tables) . "):</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>{$table}</strong></li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Ошибка при получении таблиц: " . $e->getMessage() . "</p>";
}

// 2. Поиск таблиц для заказов
echo "<h3>Поиск таблиц для заказов:</h3>";
$found_order_tables = [];

foreach ($tables as $table) {
    // Проверяем разные варианты названий
    if (preg_match('/(order|purchase|buy|shop_cart)/i', $table)) {
        $found_order_tables[] = $table;
    }
}

if (empty($found_order_tables)) {
    echo "<p style='color: orange;'>⚠️ Не найдено таблиц для заказов</p>";
    echo "<p>Нужно создать таблицу orders</p>";
    
    // SQL для создания таблицы
    $create_sql = "
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(100) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        login_data TEXT,
        password_data TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        payment_status VARCHAR(20) DEFAULT 'awaiting',
        payment_id VARCHAR(100),
        payment_system VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    echo "<h4>SQL для создания таблицы:</h4>";
    echo "<pre>" . htmlspecialchars($create_sql) . "</pre>";
    
    echo "<p><a href='create_orders_table.php'>Создать таблицу orders</a></p>";
    
} else {
    echo "<p>Найдены таблицы для заказов:</p>";
    echo "<ul>";
    foreach ($found_order_tables as $table) {
        echo "<li><strong>{$table}</strong> ";
        echo "<a href='#{$table}' onclick='showTableStructure(\"{$table}\")'>[структура]</a>";
        echo "</li>";
    }
    echo "</ul>";
    
    // Показать структуру первой найденной таблицы
    if (!empty($found_order_tables)) {
        $first_table = $found_order_tables[0];
        echo "<h4>Структура таблицы: {$first_table}</h4>";
        
        try {
            $stmt = $pdo->query("DESCRIBE {$first_table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
            
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
        }
    }
}

// 3. Проверить таблицу supplier_products
echo "<h3>Проверка таблицы товаров:</h3>";
try {
    if (in_array('supplier_products', $tables)) {
        echo "<p style='color: green;'>✅ Таблица supplier_products существует</p>";
        
        // Проверить структуру
        $stmt = $pdo->query("DESCRIBE supplier_products");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        echo "<p>Колонки: " . implode(", ", $columns) . "</p>";
        
        // Проверить есть ли товары
        $count = $pdo->query("SELECT COUNT(*) FROM supplier_products")->fetchColumn();
        echo "<p>Товаров в каталоге: " . $count . "</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Таблица supplier_products не найдена!</p>";
    }
} catch (Exception $e) {
    echo "<p>Ошибка: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Действия:</h3>";
echo "<ul>";
echo "<li><a href='catalog.php'>Перейти в каталог</a></li>";
echo "<li><a href='create_orders_table.php'>Создать таблицу orders</a></li>";
echo "<li><a href='test_db.php'>Тест подключения к БД</a></li>";
echo "</ul>";

// JavaScript для показа структуры таблиц
echo "
<script>
function showTableStructure(tableName) {
    alert('Структура таблицы ' + tableName + ' будет показана здесь');
    // Можно реализовать AJAX запрос для подробной информации
}
</script>
";
?>