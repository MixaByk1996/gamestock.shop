<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    die('Доступ запрещен');
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->query("DESCRIBE supplier_products");
    $columns = $stmt->fetchAll();
    
    echo "<h2>Структура таблицы supplier_products</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>