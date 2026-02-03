<?php
require_once 'includes/config.php';
$pdo = getDBConnection();

// Получаем 5 товаров из базы
$stmt = $pdo->query("SELECT id, name, category FROM supplier_products LIMIT 5");
$products = $stmt->fetchAll();

echo "<h2>Проверка названий товаров в базе:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Название</th><th>Категория</th></tr>";

foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>" . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>{$product['category']}</td>";
    echo "</tr>";
}
echo "</table>";

// Проверим что приходит от API
echo "<h2>Проверка что приходит от API поставщика:</h2>";
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$api = new BuyAccsNet('m02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a');
$result = $api->getProducts(['limit' => 3]);

if (isset($result['goods']) && is_array($result['goods'])) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID API</th><th>Название от API</th></tr>";
    
    foreach ($result['goods'] as $item) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>" . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Ошибка API: " . ($result['message'] ?? 'Неизвестная ошибка') . "</p>";
}
?>