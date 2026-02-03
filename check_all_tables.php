<?php
// check_all_tables.php
require_once 'includes/config.php';

$conn = getDBConnection();

echo "<h2>Таблицы в базе данных:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Название таблицы</th><th>Колонки</th></tr>";

$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
    echo "<td>";
    
    $columns = $conn->query("DESCRIBE `$table`")->fetchAll();
    foreach ($columns as $col) {
        echo htmlspecialchars($col['Field']) . " (" . htmlspecialchars($col['Type']) . ")<br>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Проверяем таблицу поставщиков
echo "<h3>Проверка таблицы suppliers:</h3>";
if (in_array('suppliers', $tables)) {
    echo "✅ Таблица suppliers существует<br>";
    
    $suppliers_data = $conn->query("SELECT id, name FROM suppliers LIMIT 5")->fetchAll();
    echo "Первые 5 поставщиков:<br>";
    foreach ($suppliers_data as $row) {
        echo "ID: " . $row['id'] . " - " . htmlspecialchars($row['name']) . "<br>";
    }
} else {
    echo "❌ Таблица suppliers не найдена<br>";
}

// Проверяем таблицу с товарами (поищем по разным возможным названиям)
$possible_goods_tables = ['goods', 'products', 'items', 'services', 'stock', 'catalog'];
echo "<h3>Поиск таблицы с товарами:</h3>";

foreach ($possible_goods_tables as $table_name) {
    if (in_array($table_name, $tables)) {
        echo "✅ Найдена таблица: <strong>$table_name</strong><br>";
        
        // Покажем структуру
        echo "Структура таблицы $table_name:<br>";
        $columns = $conn->query("DESCRIBE `$table_name`")->fetchAll();
        foreach ($columns as $col) {
            echo "  - " . htmlspecialchars($col['Field']) . " (" . htmlspecialchars($col['Type']) . ")<br>";
        }
        
        // Покажем несколько записей
        echo "Первые 3 записи:<br>";
        $sample_data = $conn->query("SELECT * FROM `$table_name` LIMIT 3")->fetchAll();
        foreach ($sample_data as $row) {
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
        
        break;
    }
}
?>