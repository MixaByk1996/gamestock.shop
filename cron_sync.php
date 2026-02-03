<?php
// cron_sync.php - автоматическая синхронизация по расписанию
require_once 'includes/config.php';
require_once 'includes/ApiSuppliers/BuyAccsNet.php';
require_once 'includes/price_calculator.php';

echo "🔄 Запуск автоматической синхронизации...\n";

try {
    $pdo = getDBConnection();
    
    // Получаем поставщика
    $supplier_id = 1;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("❌ Поставщик не найден\n");
    }
    
    $api = new BuyAccsNet($supplier['api_key']);
    
    // Получаем 20 товаров для быстрого обновления
    $result = $api->getProducts(['limit' => 20]);
    
    if (isset($result['goods']) && is_array($result['goods'])) {
        $processed = 0;
        $added = 0;
        $updated = 0;
        
        foreach ($result['goods'] as $item) {
            $processed++;
            
            // Проверяем наличие товара
            $check = $pdo->prepare("SELECT id FROM supplier_products WHERE supplier_id = ? AND external_id = ?");
            $check->execute([$supplier_id, $item['id']]);
            $existing = $check->fetch();
            
            // Расчет цены
            $calculated = PriceCalculator::calculatePrice(
                $item['price'],
                $supplier['markup_type'],
                $supplier['markup_value']
            );
            
            if ($existing) {
                // Обновляем
                $sql = "UPDATE supplier_products SET 
                        name = ?, 
                        price = ?, 
                        our_price = ?, 
                        stock = ?, 
                        last_updated = NOW()
                        WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $item['title'],
                    $item['price'],
                    $calculated['final_price'],
                    $item['count'] ?? 0,
                    $existing['id']
                ]);
                
                $updated++;
            } else {
                // Добавляем новый
                $sql = "INSERT INTO supplier_products 
                        (supplier_id, external_id, name, category, price, our_price, stock, last_updated) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $supplier_id,
                    $item['id'],
                    $item['title'],
                    $item['category_id'],
                    $item['price'],
                    $calculated['final_price'],
                    $item['count'] ?? 0
                ]);
                
                $added++;
            }
        }
        
        // Обновляем время синхронизации
        $pdo->prepare("UPDATE suppliers SET last_sync = NOW() WHERE id = ?")
            ->execute([$supplier_id]);
        
        echo "✅ Синхронизация завершена!\n";
        echo "📊 Обработано: $processed\n";
        echo "📥 Добавлено: $added\n";
        echo "🔄 Обновлено: $updated\n";
        
    } else {
        echo "❌ Ошибка получения товаров\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>