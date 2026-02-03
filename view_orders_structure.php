<?php
// view_orders_structure.php - Просмотр структуры таблицы orders
require_once 'includes/config.php';

$pdo = getDBConnection();

echo "<h2>Структура таблицы orders</h2>";

// 1. Показать структуру
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка: " . $e->getMessage() . "</p>";
}

// 2. Показать несколько записей
echo "<h3>Несколько записей из orders:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM orders LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p>Таблица пуста</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        foreach (array_keys($orders[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            foreach ($order as $value) {
                echo "<td>" . htmlspecialchars(substr((string)$value, 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Ошибка при получении записей: " . $e->getMessage() . "</p>";
}

// 3. SQL для добавления недостающих колонок
echo "<h3>Добавить недостающие колонки:</h3>";

$needed_columns = [
    'customer_email' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_email VARCHAR(100) AFTER notes",
    'customer_name' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) AFTER customer_email",
    'login_data' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS login_data TEXT AFTER customer_name",
    'password_data' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS password_data TEXT AFTER login_data",
    'payment_id' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_id VARCHAR(100) AFTER password_data",
    'payment_system' => "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_system VARCHAR(50) AFTER payment_id"
];

foreach ($needed_columns as $col => $sql) {
    echo "<p><strong>{$col}:</strong> <code>{$sql}</code></p>";
}

echo "<p><a href='add_missing_columns.php'>Добавить недостающие колонки</a></p>";

echo "<hr>";
echo "<p><a href='catalog.php'>Вернуться в каталог</a></p>";
?>