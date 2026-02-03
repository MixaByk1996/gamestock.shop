<?php
// update_all_products.php - Обновление ВСЕХ товаров на русские названия
require_once 'includes/config.php';
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

echo "<h2>🔄 Обновление ВСЕХ товаров на русские названия</h2>";

try {
    $pdo = getDBConnection();
    
    // Получаем поставщика
    $supplier_id = 1;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("❌ Поставщик не найден");
    }
    
    $api = new BuyAccsNet($supplier['api_key']);
    
    // 1. Сначала получаем все ID товаров из нашей базы
    $stmt = $pdo->query("SELECT external_id FROM supplier_products WHERE supplier_id = $supplier_id");
    $our_product_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $total_in_db = count($our_product_ids);
    echo "<p>📊 Товаров в базе: $total_in_db</p>";
    
    if ($total_in_db == 0) {
        die("<p>❌ В базе нет товаров для обновления</p>");
    }
    
    // 2. Разбиваем на группы по 50 ID (лимит API)
    $chunks = array_chunk($our_product_ids, 50);
    $total_chunks = count($chunks);
    
    echo "<p>🔢 Будет обработано групп: $total_chunks</p>";
    
    $total_updated = 0;
    $total_errors = 0;
    
    // 3. Обрабатываем каждую группу
    foreach ($chunks as $chunk_index => $ids_chunk) {
        $ids_string = implode(',', $ids_chunk);
        echo "<p>📦 Группа " . ($chunk_index + 1) . "/$total_chunks (ID: " . count($ids_chunk) . " товаров)...</p>";
        
        // Получаем актуальные данные от API
        $result = $api->getProductById($ids_string);
        
        if (isset($result['goods']) && is_array($result['goods'])) {
            foreach ($result['goods'] as $item) {
                try {
                    // Обновляем товар в базе
                    $sql = "UPDATE supplier_products SET 
                            name = ?, 
                            price = ?, 
                            stock = ?,
                            last_updated = NOW()
                            WHERE external_id = ? AND supplier_id = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $item['title'], // Русское название
                        $item['price'],
                        $item['count'] ?? 0,
                        $item['id'],
                        $supplier_id
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $total_updated++;
                        echo "<span style='color: green;'>✅ Обновлен товар #{$item['id']}: " . 
                             htmlspecialchars(substr($item['title'], 0, 50)) . "...</span><br>";
                    }
                    
                } catch (Exception $e) {
                    $total_errors++;
                    echo "<span style='color: red;'>❌ Ошибка товара #{$item['id']}: " . 
                         $e->getMessage() . "</span><br>";
                }
            }
        } else {
            $total_errors += count($ids_chunk);
            echo "<span style='color: orange;'>⚠️ API не вернул данные для группы</span><br>";
        }
        
        // Пауза между запросами чтобы не превысить лимит API
        sleep(2);
    }
    
    // 4. Итоги
    echo "<h3>📊 Итоги обновления:</h3>";
    echo "<p>✅ Обновлено товаров: <strong>$total_updated</strong></p>";
    echo "<p>❌ Ошибок: <strong>$total_errors</strong></p>";
    echo "<p>🎯 Всего в базе: <strong>$total_in_db</strong></p>";
    
    if ($total_updated > 0) {
        echo "<p><a href='/catalog.php' target='_blank'>📁 Перейти в каталог</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Критическая ошибка: " . $e->getMessage() . "</p>";
}
?>