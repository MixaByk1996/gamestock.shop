<?php
// test_new_api.php
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$buyaccs = new BuyAccsNet();

echo "<h2>Тестирование новой версии API класса</h2>";
echo "<p>Используется официальная документация buy-accs.net</p>";

// Тест 1: Проверка баланса
echo "<h3>1. Проверка баланса (RUB):</h3>";
$balance = $buyaccs->getBalance('rub');
echo "<pre>";
print_r($balance);
echo "</pre>";

if (isset($balance['balance'])) {
    echo "<p><strong>Баланс: " . $balance['balance'] . " RUB</strong></p>";
    
    if ($balance['balance'] <= 0) {
        echo "<p style='color: red;'>⚠️ Баланс равен 0. Пополните счет на buy-accs.net для тестирования покупок!</p>";
    }
}

// Тест 2: Получение категорий
echo "<h3>2. Получение категорий:</h3>";
$categories = $buyaccs->getCategories();
if (isset($categories['categories']) && is_array($categories['categories'])) {
    echo "Найдено категорий: " . count($categories['categories']) . "<br>";
    
    // Показываем первые 3 категории
    $count = 0;
    foreach ($categories['categories'] as $category) {
        if ($count++ >= 3) break;
        echo "- " . ($category['name'] ?? 'ID: ' . ($category['id'] ?? 'N/A')) . "<br>";
    }
} else {
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
}

// Тест 3: Получение товаров
echo "<h3>3. Получение товаров (первые 3):</h3>";
$products = $buyaccs->getProducts(['limit' => 3]);

if (isset($products['goods']) && is_array($products['goods'])) {
    echo "Найдено товаров: " . count($products['goods']) . "<br><br>";
    
    foreach ($products['goods'] as $product) {
        echo "<div style='border:1px solid #ddd; padding:10px; margin:10px 0;'>";
        echo "<strong>ID:</strong> " . ($product['id'] ?? 'N/A') . "<br>";
        echo "<strong>Название:</strong> " . ($product['title'] ?? 'N/A') . "<br>";
        echo "<strong>Цена:</strong> " . ($product['price'] ?? 'N/A') . " RUB<br>";
        echo "<strong>В наличии:</strong> " . ($product['count'] ?? 0) . " шт.<br>";
        echo "<strong>Категория:</strong> " . ($product['category_name'] ?? 'N/A') . "<br>";
        echo "</div>";
    }
} else {
    echo "<pre>";
    print_r($products);
    echo "</pre>";
}

// Тест 4: Если есть баланс > 0, пробуем найти дешевый товар для теста
if (isset($balance['balance']) && $balance['balance'] > 0) {
    echo "<h3>4. Поиск дешевого товара для теста покупки:</h3>";
    
    // Ищем товары до 500 RUB
    $cheap_products = $buyaccs->getProducts([
        'limit' => 10,
        'sort' => 'price',
        'sort-direction' => 'ASC'
    ]);
    
    if (isset($cheap_products['goods']) && count($cheap_products['goods']) > 0) {
        $cheapest = $cheap_products['goods'][0];
        
        echo "Самый дешевый товар:<br>";
        echo "ID: " . ($cheapest['id'] ?? 'N/A') . "<br>";
        echo "Название: " . ($cheapest['title'] ?? 'N/A') . "<br>";
        echo "Цена: " . ($cheapest['price'] ?? 'N/A') . " RUB<br>";
        echo "В наличии: " . ($cheapest['count'] ?? 0) . " шт.<br><br>";
        
        if (($cheapest['price'] ?? 0) <= $balance['balance']) {
            echo "<button onclick=\"testPurchase(" . ($cheapest['id'] ?? 0) . ")\" style='background:#4CAF50;color:white;padding:10px;border:none;cursor:pointer;'>
                🛒 Тест покупки (ID: " . ($cheapest['id'] ?? 0) . ")
            </button>";
        } else {
            echo "<p style='color:orange;'>Цена товара превышает баланс</p>";
        }
    }
}

// Тест 5: Автоматическое сопоставление товара
echo "<h3>5. Тест автоматического сопоставления товаров:</h3>";
$test_names = [
    "Instagram аккаунт",
    "Google аккаунт", 
    "Прокси Россия",
    "Facebook реклама"
];

foreach ($test_names as $name) {
    $product_id = $buyaccs->findProductIdByName($name, 3000);
    echo "Поиск: <strong>'$name'</strong> → Найден ID: " . ($product_id ? $product_id : 'не найден') . "<br>";
}
?>

<script>
function testPurchase(product_id) {
    if (confirm('Вы уверены? Это РЕАЛЬНАЯ покупка если есть баланс!')) {
        window.location.href = 'test_purchase.php?id=' + product_id;
    }
}
</script>