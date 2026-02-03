<?php
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$buyaccs = new BuyAccsNet();

echo "<h2>Тестирование API buy-accs.net</h2>";

// 1. Проверить баланс
echo "<h3>1. Проверка баланса (RUB):</h3>";
$balance = $buyaccs->getBalance();
echo "<pre>";
print_r($balance);
echo "</pre>";

// 2. Получить список товаров
echo "<h3>2. Список товаров:</h3>";
$products = $buyaccs->getProducts();

if (isset($products['error'])) {
    echo "Ошибка: " . $products['message'];
} elseif (isset($products['products']) && is_array($products['products'])) {
    echo "Найдено товаров: " . count($products['products']) . "<br><br>";
    
    // Показываем первые 5 товаров
    $count = 0;
    foreach ($products['products'] as $product) {
        if ($count++ >= 5) break;
        
        echo "ID: " . ($product['id'] ?? 'N/A') . "<br>";
        echo "Название: " . ($product['name'] ?? 'N/A') . "<br>";
        echo "Цена: " . ($product['price'] ?? 'N/A') . " RUB<br>";
        echo "Категория: " . ($product['category_name'] ?? 'N/A') . "<br>";
        echo "Описание: " . substr($product['description'] ?? '', 0, 100) . "...<br>";
        echo "<hr>";
    }
} else {
    echo "<pre>";
    print_r($products);
    echo "</pre>";
}

// 3. Если есть товары, показать тестовую покупку
if (isset($products['products'][0]['id'])) {
    $test_product_id = $products['products'][0]['id'];
    
    echo "<h3>3. Тест покупки (ID: $test_product_id):</h3>";
    echo "<p><em>Внимание: Это реальная покупка, если баланс положительный!</em></p>";
    
    $purchase = $buyaccs->purchaseProduct($test_product_id);
    
    echo "<pre>";
    print_r($purchase);
    echo "</pre>";
}
?>