<?php
require_once 'includes/config.php';
$pdo = getDBConnection();

echo "<h2>Проверка структуры БД:</h2>";

// 1. Проверяем таблицу test_accounts
$tables = ['test_accounts', 'supplier_products', 'orders'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW CREATE TABLE $table");
    $result = $stmt->fetch();
    echo "<h3>Таблица: $table</h3>";
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
}

// 2. Проверяем данные в test_accounts
echo "<h3>Данные в test_accounts:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM test_accounts");
$total = $stmt->fetchColumn();
echo "Всего записей: $total<br>";

$stmt = $pdo->query("SELECT * FROM test_accounts LIMIT 5");
$accounts = $stmt->fetchAll();

if ($accounts) {
    echo "<table border='1'><tr><th>ID</th><th>Логин</th><th>Пароль</th><th>Статус</th><th>Тип</th></tr>";
    foreach ($accounts as $account) {
        echo "<tr>";
        echo "<td>{$account['id']}</td>";
        echo "<td>{$account['login']}</td>";
        echo "<td>{$account['password']}</td>";
        echo "<td>{$account['status']}</td>";
        echo "<td>{$account['product_type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Таблица test_accounts пуста или не существует!";
}

// 3. Проверяем последние заказы
echo "<h3>Последние 5 заказов:</h3>";
$stmt = $pdo->query("SELECT id, order_number, product_name, login_data, password_data, payment_status FROM orders ORDER BY id DESC LIMIT 5");
$orders = $stmt->fetchAll();

if ($orders) {
    echo "<table border='1'><tr><th>ID</th><th>Номер</th><th>Товар</th><th>Логин</th><th>Пароль</th><th>Статус</th></tr>";
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td>{$order['product_name']}</td>";
        echo "<td>" . (empty($order['login_data']) ? '<span style="color:red">НЕТ</span>' : $order['login_data']) . "</td>";
        echo "<td>" . (empty($order['password_data']) ? '<span style="color:red">НЕТ</span>' : $order['password_data']) . "</td>";
        echo "<td>{$order['payment_status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>