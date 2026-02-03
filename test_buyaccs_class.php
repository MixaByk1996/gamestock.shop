<?php
// test_buyaccs_class.php
require_once 'includes/config.php';
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

echo "<h1>🧪 Тестирование класса BuyAccsNet</h1>";

try {
    // 1. Создаем экземпляр класса
    $api = new BuyAccsNet("ewhynyaswwj-bnhlwq_i7spuz83lrhju8uhagbiviw1uhqqsat");
    
    echo "<h2>1. Тест подключения</h2>";
    $test = $api->testConnection('rub');
    
    if ($test['success']) {
        echo "<div style='color:green; padding:10px; background:#e8f5e8;'>" . $test['message'] . "</div>";
    } else {
        echo "<div style='color:red; padding:10px; background:#ffebee;'>" . $test['message'] . "</div>";
    }
    
    // 2. Получаем товары
    echo "<h2>2. Получение товаров (RUB)</h2>";
    $goods_result = $api->getGoods('rub', ['limit' => 3]);
    
    if ($goods_result['success']) {
        echo "<div style='color:green;'>✅ Успешно! Товаров: " . count($goods_result['data']) . "</div>";
        
        // Показываем товары
        echo "<h3>Полученные товары:</h3>";
        
        if (is_array($goods_result['data']) && count($goods_result['data']) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width:100%;'>";
            
            // Заголовки из первого товара
            $first_good = $goods_result['data'][0];
            echo "<tr>";
            foreach (array_keys($first_good) as $key) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // Данные товаров
            foreach ($goods_result['data'] as $item) {
                echo "<tr>";
                foreach ($item as $value) {
                    echo "<td>" . htmlspecialchars(substr(strval($value), 0, 50)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div style='color:red;'>❌ Ошибка: " . ($goods_result['error'] ?? 'Unknown') . "</div>";
    }
    
    // 3. Получаем баланс
    echo "<h2>3. Получение баланса</h2>";
    $balance_result = $api->getBalance('rub');
    
    if ($balance_result['success']) {
        echo "<div style='color:green;'>✅ Баланс получен</div>";
        echo "<pre>" . htmlspecialchars(json_encode($balance_result['data'], JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<div style='color:orange;'>⚠ Баланс не получен: " . json_encode($balance_result['data']['errors'] ?? 'Unknown') . "</div>";
    }
    
    // 4. Тест с наценкой 150%
    echo "<h2>4. Тест расчета с наценкой 150%</h2>";
    
    if ($goods_result['success'] && count($goods_result['data']) > 0) {
        require_once 'includes/price_calculator.php';
        
        $first_item = $goods_result['data'][0];
        $price = $first_item['price'] ?? $first_item['cost'] ?? 0;
        
        echo "<p>Цена у поставщика: <strong>" . $price . " RUB</strong></p>";
        
        $calculated = PriceCalculator::calculatePrice($price, 'percent', 150);
        
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Параметр</th><th>Значение</th></tr>";
        echo "<tr><td>Базовая цена</td><td>" . $calculated['base_price'] . " RUB</td></tr>";
        echo "<tr><td>Наценка (150%)</td><td>" . $calculated['markup_amount'] . " RUB</td></tr>";
        echo "<tr><td><strong>Итоговая цена</strong></td><td><strong style='color:green;'>" . $calculated['final_price'] . " RUB</strong></td></tr>";
        echo "<tr><td>Множитель</td><td>" . $calculated['markup_percent'] . "% = ×" . (1 + $calculated['markup_percent']/100) . "</td></tr>";
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2 style='color:green;'>✅ КЛАСС API ГОТОВ К ИСПОЛЬЗОВАНИЮ!</h2>";
    echo "<p><a href='create_sync.php'>Перейти к созданию синхронизации →</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; background:#ffebee;'>❌ Ошибка: " . $e->getMessage() . "</div>";
}
?>