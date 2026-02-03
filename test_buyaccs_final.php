<?php
// test_buyaccs_final.php
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$buyaccs = new BuyAccsNet();

echo "<h2>Тестирование API buy-accs.net (FINAL)</h2>";

// 1. Баланс
echo "<h3>1. Проверка баланса (RUB):</h3>";
$balance = $buyaccs->getBalance('rub');
echo "<pre>";
print_r($balance);
echo "</pre>";

// 2. Список товаров
echo "<h3>2. Список товаров (RUB):</h3>";
$products = $buyaccs->getProducts('rub');

if (isset($products['error'])) {
    echo "Ошибка: " . $products['message'];
} elseif (isset($products['goods']) && is_array($products['goods'])) {
    echo "Найдено товаров: " . count($products['goods']) . "<br><br>";
    
    // Показываем первые 5 товаров
    $count = 0;
    foreach ($products['goods'] as $product) {
        if ($count++ >= 5) break;
        
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<strong>ID:</strong> " . ($product['id'] ?? 'N/A') . "<br>";
        echo "<strong>Название:</strong> " . ($product['name'] ?? 'N/A') . "<br>";
        echo "<strong>Цена:</strong> " . ($product['price'] ?? 'N/A') . " RUB<br>";
        echo "<strong>Категория:</strong> " . ($product['category_name'] ?? 'N/A') . "<br>";
        echo "<strong>Описание:</strong> " . substr($product['description'] ?? '', 0, 100) . "...<br>";
        echo "</div>";
    }
} elseif (isset($products['products']) && is_array($products['products'])) {
    echo "Найдено товаров (products): " . count($products['products']) . "<br><br>";
    
    $count = 0;
    foreach ($products['products'] as $product) {
        if ($count++ >= 5) break;
        
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<strong>ID:</strong> " . ($product['id'] ?? 'N/A') . "<br>";
        echo "<strong>Название:</strong> " . ($product['name'] ?? 'N/A') . "<br>";
        echo "<strong>Цена:</strong> " . ($product['price'] ?? 'N/A') . " RUB<br>";
        echo "</div>";
    }
} else {
    echo "<pre>";
    print_r($products);
    echo "</pre>";
}

// 3. Если есть товары, покажем их ID для теста покупки
if (isset($products['goods'][0]['id'])) {
    $test_product_id = $products['goods'][0]['id'];
    
    echo "<h3>3. Тест покупки (ID: $test_product_id):</h3>";
    echo "<p style='color:red;'><strong>Внимание: Это РЕАЛЬНАЯ покупка, если есть баланс!</strong></p>";
    
    $purchase = $buyaccs->purchaseProduct($test_product_id, 1);
    
    echo "<pre>";
    print_r($purchase);
    echo "</pre>";
}
?>