<?php
// add_currency_tables.php (финальная версия)
require_once 'includes/config.php';

$conn = getDBConnection();

try {
    echo "<h2>Настройка системы валют</h2>";
    
    // Проверяем, что основные таблицы созданы
    $check_rates = $conn->query("SHOW TABLES LIKE 'supplier_currency_rates'");
    if ($check_rates->rowCount() == 0) {
        $sql1 = "CREATE TABLE supplier_currency_rates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT NOT NULL,
            currency_code VARCHAR(10) DEFAULT 'USD',
            rate_to_rub DECIMAL(10,4) DEFAULT 80.45,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_supplier (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql1);
        echo "✅ Таблица supplier_currency_rates создана!<br>";
    } else {
        echo "✅ Таблица supplier_currency_rates уже существует<br>";
    }
    
    $check_system = $conn->query("SHOW TABLES LIKE 'system_currencies'");
    if ($check_system->rowCount() == 0) {
        $sql2 = "CREATE TABLE system_currencies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            currency_code VARCHAR(10) UNIQUE NOT NULL,
            currency_name VARCHAR(50),
            default_rate DECIMAL(10,4),
            is_base BOOLEAN DEFAULT FALSE,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $conn->exec($sql2);
        echo "✅ Таблица system_currencies создана!<br>";
        
        // Добавляем базовые валюты
        $currencies = [
            ['RUB', 'Российский рубль', 1.00, 1],
            ['USD', 'Американский доллар', 80.45, 0],
            ['EUR', 'Евро', 90.12, 0]
        ];
        
        $stmt = $conn->prepare("INSERT INTO system_currencies (currency_code, currency_name, default_rate, is_base) 
                               VALUES (?, ?, ?, ?)");
        
        foreach ($currencies as $currency) {
            $stmt->execute($currency);
        }
        echo "✅ Базовые валюты добавлены!<br>";
    } else {
        echo "✅ Таблица system_currencies уже существует<br>";
    }
    
    // Добавляем поля в supplier_products если их нет
    $check_currency_col = $conn->query("SHOW COLUMNS FROM supplier_products LIKE 'currency_code'");
    if ($check_currency_col->rowCount() == 0) {
        $conn->exec("ALTER TABLE supplier_products ADD COLUMN currency_code VARCHAR(10) DEFAULT 'RUB'");
        echo "✅ Добавлен столбец currency_code в supplier_products<br>";
    } else {
        echo "✅ Столбец currency_code уже есть в supplier_products<br>";
    }
    
    $check_original_price = $conn->query("SHOW COLUMNS FROM supplier_products LIKE 'original_price'");
    if ($check_original_price->rowCount() == 0) {
        $conn->exec("ALTER TABLE supplier_products ADD COLUMN original_price DECIMAL(10,2) DEFAULT 0");
        echo "✅ Добавлен столбец original_price в supplier_products<br>";
    } else {
        echo "✅ Столбец original_price уже есть в supplier_products<br>";
    }
    
    $check_converted_price = $conn->query("SHOW COLUMNS FROM supplier_products LIKE 'converted_price'");
    if ($check_converted_price->rowCount() == 0) {
        $conn->exec("ALTER TABLE supplier_products ADD COLUMN converted_price DECIMAL(10,2) DEFAULT 0");
        echo "✅ Добавлен столбец converted_price в supplier_products<br>";
    } else {
        echo "✅ Столбец converted_price уже есть в supplier_products<br>";
    }
    
    // Обновляем данные: копируем price в original_price для существующих записей
    $conn->exec("UPDATE supplier_products SET original_price = price WHERE original_price = 0 OR original_price IS NULL");
    echo "✅ Цены скопированы в original_price<br>";
    
    // Обновляем currency_code в suppliers если нужно
    $check_suppliers_currency = $conn->query("SHOW COLUMNS FROM suppliers LIKE 'currency_code'");
    if ($check_suppliers_currency->rowCount() == 0) {
        $conn->exec("ALTER TABLE suppliers ADD COLUMN currency_code VARCHAR(10) DEFAULT 'RUB'");
        echo "✅ Добавлен столбец currency_code в suppliers<br>";
    } else {
        echo "✅ Столбец currency_code уже есть в suppliers<br>";
    }
    
    echo "<div style='padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>🎉 Система валют готова к работе!</h3>";
    echo "<p>Все необходимые таблицы и поля созданы.</p>";
    echo "<p><strong>Основная таблица с товарами:</strong> supplier_products</p>";
    echo "<p><strong>Поля для работы с валютами добавлены:</strong></p>";
    echo "<ul>";
    echo "<li>currency_code - валюта товара (RUB/USD/EUR)</li>";
    echo "<li>original_price - оригинальная цена в валюте поставщика</li>";
    echo "<li>converted_price - цена после конвертации в рубли</li>";
    echo "</ul>";
    echo "<p><a href='/admin/currency_rates.php' style='color: #155724; text-decoration: underline;'>Перейти к настройке курсов</a> | ";
    echo "<a href='/admin/' style='color: #155724; text-decoration: underline;'>В админку</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>❌ Ошибка:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>