<?php
// install.php - Установка таблиц для личного кабинета
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>🛠 Установка личного кабинета</h2>";
    echo "<div style='max-width: 800px; margin: 0 auto; font-family: Arial;'>";
    
    // Проверяем существующие таблицы
    $existing_tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Существующие таблицы:</h3>";
    echo "<ul>";
    foreach ($existing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // SQL запросы
    $queries = [
        // Таблица пользователей
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            balance DECIMAL(10,2) DEFAULT 0.00,
            telegram VARCHAR(50) NULL,
            phone VARCHAR(20) NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Таблица заказов
        "CREATE TABLE IF NOT EXISTS orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            order_number VARCHAR(20) NOT NULL UNIQUE,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('new', 'processing', 'completed', 'cancelled', 'refunded') DEFAULT 'new',
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // Таблица транзакций
        "CREATE TABLE IF NOT EXISTS transactions (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    // Выполняем запросы
    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Таблица создана успешно</p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ " . $e->getMessage() . "</p>";
        }
    }
    
    // Создаем тестового пользователя (генерируем правильный хеш)
    $password = '123456';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $test_users = [
        [
            'username' => 'testuser',
            'email' => 'test@gamestock.shop',
            'password' => $hashed_password,
            'balance' => 1000.00,
            'is_admin' => false
        ],
        [
            'username' => 'admin',
            'email' => 'admin@gamestock.shop',
            'password' => $hashed_password,
            'balance' => 5000.00,
            'is_admin' => true
        ]
    ];
    
    echo "<h3>Создание тестовых пользователей:</h3>";
    
    foreach ($test_users as $user_data) {
        try {
            $sql = "INSERT INTO users (username, email, password, balance, is_admin) 
                    VALUES (:username, :email, :password, :balance, :is_admin)
                    ON DUPLICATE KEY UPDATE 
                    password = VALUES(password),
                    balance = VALUES(balance),
                    is_admin = VALUES(is_admin)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($user_data);
            
            echo "<p style='color: green;'>✅ Пользователь '{$user_data['username']}' создан/обновлен</p>";
            echo "<p><small>Логин: {$user_data['username']} | Пароль: {$password}</small></p>";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Ошибка при создании пользователя '{$user_data['username']}': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🎉 Установка завершена!</h3>";
    echo "<p><a href='/cabinet/' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Перейти в личный кабинет</a></p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; border: 2px solid red;'>";
    echo "<h3>❌ Критическая ошибка подключения к БД</h3>";
    echo "<p><strong>" . $e->getMessage() . "</strong></p>";
    echo "<p>Проверьте настройки в includes/config.php</p>";
    echo "</div>";
}
?>