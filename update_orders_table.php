<?php
// update_orders_table.php - Обновление таблицы заказов для работы с балансом
require_once 'includes/config.php';

$pdo = getDBConnection();

echo "<h2>🔄 Обновление системы заказов</h2>";
echo "<style>pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }</style>";

try {
    // Проверяем существующие колонки
    $stmt = $pdo->query("DESCRIBE orders");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<h3>Существующие колонки в orders:</h3>";
    echo "<pre>" . implode(", ", $existing_columns) . "</pre>";
    
    // Определяем какие колонки нужно добавить
    $columns_to_add = [
        'user_id' => "INT DEFAULT 0 AFTER id",
        'payment_method' => "VARCHAR(20) DEFAULT 'card'",
        'transaction_id' => "INT DEFAULT NULL",
        'customer_phone' => "VARCHAR(20) DEFAULT NULL",
        'customer_telegram' => "VARCHAR(50) DEFAULT NULL"
    ];
    
    echo "<h3>Добавление недостающих колонок:</h3>";
    
    foreach ($columns_to_add as $column_name => $column_def) {
        if (!in_array($column_name, $existing_columns)) {
            $sql = "ALTER TABLE orders ADD COLUMN $column_name $column_def";
            
            try {
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Добавлена колонка: <strong>$column_name</strong></p>";
                echo "<pre>$sql</pre>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Ошибка при добавлении $column_name: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Колонка <strong>$column_name</strong> уже существует</p>";
        }
    }
    
    // Проверим связь между таблицами orders и users
    echo "<h3>Проверка таблицы users:</h3>";
    
    $users_exists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    
    if ($users_exists) {
        echo "<p style='color: green;'>✅ Таблица users существует</p>";
        
        // Покажем структуру users
        $users_columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN, 0);
        echo "<pre>Колонки users: " . implode(", ", $users_columns) . "</pre>";
        
        // Проверим есть ли тестовые пользователи
        $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>Количество пользователей: $users_count</p>";
        
        if ($users_count == 0) {
            echo "<p style='color: orange;'>⚠️ Нет пользователей. Создайте тестового:</p>";
            echo "<pre>
INSERT INTO users (username, email, password, balance, created_at) 
VALUES ('testuser', 'test@example.com', '', 1000.00, NOW())
            </pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Таблица users не найдена!</p>";
        echo "<p>Создайте таблицу через install.php</p>";
    }
    
    // Проверим таблицу transactions
    $transactions_exists = $pdo->query("SHOW TABLES LIKE 'transactions'")->rowCount() > 0;
    
    if ($transactions_exists) {
        echo "<p style='color: green;'>✅ Таблица transactions существует</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Таблица transactions не найдена</p>";
        echo "<pre>
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('deposit', 'purchase', 'refund', 'bonus', 'withdrawal') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    payment_system VARCHAR(50),
    transaction_id VARCHAR(100),
    related_order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        </pre>";
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Обновление завершено!</h3>";
    
    // Финальная проверка структуры
    $final_columns = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Текущая структура orders:</h4>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
    
    foreach ($final_columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Следующие шаги:</strong></p>";
    echo "<ol>";
    echo "<li><a href='/install.php'>Создать таблицу users (если нет)</a></li>";
    echo "<li><a href='/cabinet/'>Перейти в личный кабинет</a></li>";
    echo "<li><a href='/catalog.php'>Протестировать покупки</a></li>";
    echo "</ol>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px;'>";
    echo "<h3>❌ Критическая ошибка:</h3>";
    echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p>Проверьте подключение к базе данных</p>";
    echo "</div>";
}
?>