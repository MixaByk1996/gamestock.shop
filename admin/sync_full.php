<?php
// /admin/sync_full.php - Полная синхронизация товаров с конвертером валют
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/currency_converter.php'; // ДОБАВЛЕНО

echo "<!DOCTYPE html>
<html>
<head>
    <title>Полная синхронизация</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <style>
        body { padding: 20px; }
        .log-box { max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #f8f9fa; }
        .log-item { padding: 3px 0; font-family: monospace; font-size: 0.9em; }
        .log-item.info { color: #0c5460; }
        .log-item.success { color: #155724; font-weight: bold; }
        .log-item.warning { color: #856404; }
        .log-item.error { color: #721c24; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>
    <nav class='navbar navbar-dark bg-success mb-4'>
        <div class='container-fluid'>
            <span class='navbar-brand'>🚀 Полная синхронизация buy-accs.net</span>
            <a href='suppliers_info.php' class='btn btn-light'>← Назад</a>
        </div>
    </nav>";
    
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Инициализация конвертера валют
    $converter = new CurrencyConverter(); // ДОБАВЛЕНО
    
    $supplier_id = 1;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("<div class='alert alert-danger'>Поставщик не найден</div>");
    }
    
    // Получаем данные о курсе валют
    $rate_data = $converter->getSupplierRate($supplier_id);
    $is_conversion_active = $rate_data['is_active'] && $rate_data['currency_code'] != 'RUB';
    $currency_code = $rate_data['currency_code'];
    $rate_to_rub = $rate_data['rate_to_rub'];
    
    echo "<div class='card mb-4'>
            <div class='card-header'>
                <h4>" . htmlspecialchars($supplier['name']) . " - Полная синхронизация</h4>
            </div>
            <div class='card-body'>
                <div class='row'>
                    <div class='col-md-6'>
                        <p><strong>Наценка:</strong> " . $supplier['markup_value'] . "%</p>
                        <p><strong>Текущих товаров в базе:</strong> " . 
                           $pdo->query("SELECT COUNT(*) as cnt FROM supplier_products WHERE supplier_id = $supplier_id")->fetch()['cnt'] . "</p>
                    </div>
                    <div class='col-md-6'>
                        <p><strong>Валюта:</strong> <span class='badge bg-info'>" . $currency_code . "</span></p>";
    
    if ($is_conversion_active) {
        echo "<p><strong>Курс конвертации:</strong> 1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</p>";
        echo "<p><span class='badge bg-success'>Конвертация активна</span></p>";
    } else {
        echo "<p><span class='badge bg-secondary'>Цены в рублях</span></p>";
    }
    
    echo "          </div>
                </div>
                <div class='alert alert-warning mt-3'>
                    <h6>⚠️ Внимание!</h6>
                    <p>Полная синхронизация загрузит до 500 товаров за 5-10 минут.</p>
                    " . ($is_conversion_active ? 
                        "<p><strong>Валюта:</strong> Все цены будут автоматически конвертированы из " . $currency_code . " в рубли.</p>" : 
                        "") . "
                </div>
            </div>
        </div>";
    
    // Проверяем наличие необходимых файлов
    $required_files = [
        '../includes/ApiSuppliers/BuyAccsNet.php',
        '../includes/price_calculator.php'
    ];
    
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            die("<div class='alert alert-danger'>❌ Отсутствует файл: $file</div>");
        }
    }
    
    require_once '../includes/ApiSuppliers/BuyAccsNet.php';
    require_once '../includes/price_calculator.php';
    
    // Обработка запуска синхронизации
    if (isset($_GET['action']) && $_GET['action'] == 'full_sync') {
        echo "<div class='card'>
                <div class='card-header bg-primary text-white'>
                    <h5>🚀 Запуск синхронизации...</h5>
                </div>
                <div class='card-body'>
                    <div class='progress mb-3' style='height: 25px;'>
                        <div id='progressBar' class='progress-bar progress-bar-striped progress-bar-animated' 
                             role='progressbar' style='width: 0%'>0%</div>
                    </div>
                    <div class='log-box' id='logBox'>";
        
        // Включаем буферизацию для реального времени
        ob_implicit_flush(true);
        ob_end_flush();
        
        $api = new BuyAccsNet($supplier['api_key']);
        
        // 1. Тест подключения
        logMessage("1. Тестирование подключения к API...", "info");
        $test = $api->testConnection('rub');
        
        if ($test['success']) {
            logMessage("✅ " . $test['message'], "success");
            $total_in_api = $test['total_in_api'] ?? 11007;
            
            // Показываем информацию о конвертации
            if ($is_conversion_active) {
                logMessage("💱 Конвертация валюты: 1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽", "info");
            }
            
            // 2. Загрузка товаров
            logMessage("2. Загрузка товаров из API...", "info");
            logMessage("Всего товаров в API: " . $total_in_api, "info");
            logMessage("Будет загружено: до 500 товаров", "info");
            
            // Загружаем 5 страниц по 100 товаров
            $all_goods = [];
            
            for ($page = 1; $page <= 5; $page++) {
                $offset = ($page - 1) * 100;
                logMessage("📥 Страница $page (offset: $offset)...", "info");
                
                $result = $api->getGoods('rub', ['offset' => $offset, 'limit' => 100]);
                
                if ($result['success'] && isset($result['data']['goods'])) {
                    $page_goods = $result['data']['goods'];
                    $all_goods = array_merge($all_goods, $page_goods);
                    logMessage("✅ Загружено: " . count($page_goods) . " товаров (всего: " . count($all_goods) . ")", "success");
                    
                    // Показываем пример конвертации для первого товара на странице
                    if (count($page_goods) > 0 && $is_conversion_active) {
                        $sample = $page_goods[0];
                        $converted_price = $converter->convertToRub($sample['price'], $supplier_id);
                        logMessage("Пример: " . number_format($sample['price'], 2) . " " . $currency_code . 
                                  " → " . number_format($converted_price, 2) . " ₽", "info");
                    }
                } else {
                    logMessage("❌ Ошибка загрузки страницы $page", "error");
                    break;
                }
                
                // Прогресс
                $percent = round(($page / 5) * 50); // 50% за загрузку
                echo "<script>updateProgress($percent);</script>";
                
                sleep(1); // Пауза между запросами
            }
            
            $loaded_count = count($all_goods);
            logMessage("✅ Всего загружено: $loaded_count товаров", "success");
            
            // 3. Обработка товаров
            logMessage("3. Обработка и сохранение товаров в БД...", "info");
            
            $processed = 0;
            $added = 0;
            $updated = 0;
            $errors = 0;
            $converted_total = 0;
            
            foreach ($all_goods as $item) {
                $processed++;
                
                try {
                    // Проверяем наличие товара
                    $check = $pdo->prepare("SELECT id FROM supplier_products WHERE supplier_id = ? AND external_id = ?");
                    $check->execute([$supplier_id, $item['id']]);
                    $existing = $check->fetch();
                    
                    // Оригинальная цена от поставщика
                    $original_price = $item['price'];
                    
                    // КОНВЕРТАЦИЯ ВАЛЮТЫ
                    if ($is_conversion_active && $currency_code != 'RUB') {
                        // Конвертируем цену в рубли
                        $converted_price = $converter->convertToRub($original_price, $supplier_id);
                        $converted_total += $original_price;
                    } else {
                        // Без конвертации
                        $converted_price = $original_price;
                    }
                    
                    // Рассчитываем нашу цену (с наценкой)
                    $calculated = PriceCalculator::calculatePrice(
                        $converted_price, // Используем конвертированную цену
                        $supplier['markup_type'],
                        $supplier['markup_value']
                    );
                    
                    // Определяем статус товара
                    $stock = $item['count'] ?? 0;
                    $is_available = $stock > 0;
                    
                    if ($existing) {
                        // Обновляем существующий товар
                        $sql = "UPDATE supplier_products SET 
                                name = ?, 
                                category = ?, 
                                price = ?, 
                                our_price = ?, 
                                original_price = ?, 
                                currency_code = ?, 
                                converted_price = ?, 
                                stock = ?, 
                                last_updated = NOW()
                                WHERE id = ?";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $item['title'],
                            $item['category_id'],
                            $converted_price,           // price (для показа)
                            $calculated['final_price'], // our_price (с наценкой)
                            $original_price,            // original_price (оригинал в валюте поставщика)
                            $currency_code,             // currency_code
                            $converted_price,           // converted_price (конвертированная цена)
                            $stock,
                            $existing['id']
                        ]);
                        
                        $updated++;
                    } else {
                        // Добавляем новый товар
                        $sql = "INSERT INTO supplier_products 
                                (supplier_id, external_id, name, category, price, our_price, 
                                 original_price, currency_code, converted_price, stock, last_updated) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            $supplier_id,
                            $item['id'],
                            $item['title'],
                            $item['category_id'],
                            $converted_price,           // price (для показа)
                            $calculated['final_price'], // our_price (с наценкой)
                            $original_price,            // original_price
                            $currency_code,             // currency_code
                            $converted_price,           // converted_price
                            $stock
                        ]);
                        
                        $added++;
                    }
                    
                    // Логируем прогресс каждые 50 товаров
                    if ($processed % 50 == 0) {
                        $percent = 50 + round(($processed / $loaded_count) * 50);
                        $log_msg = "Обработано: $processed из $loaded_count (добавлено: $added, обновлено: $updated)";
                        if ($is_conversion_active) {
                            $log_msg .= " | Конвертировано: " . number_format($converted_total, 2) . " " . $currency_code;
                        }
                        logMessage($log_msg, "info");
                        echo "<script>updateProgress($percent);</script>";
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    logMessage("❌ Ошибка товара #" . $item['id'] . ": " . $e->getMessage(), "error");
                }
            }
            
            // Обновляем время синхронизации
            $pdo->prepare("UPDATE suppliers SET last_sync = NOW() WHERE id = ?")
                ->execute([$supplier_id]);
            
            echo "</div>"; // Закрываем log-box
            
            // Итоги
            echo "<div class='alert alert-success mt-4'>
                    <h5>✅ Синхронизация завершена!</h5>
                    <table class='table'>
                        <tr><td>Загружено товаров:</td><td><strong>$loaded_count</strong></td></tr>
                        <tr><td>Обработано:</td><td><strong>$processed</strong></td></tr>
                        <tr><td>Добавлено новых:</td><td><strong class='text-success'>$added</strong></td></tr>
                        <tr><td>Обновлено существующих:</td><td><strong>$updated</strong></td></tr>
                        <tr><td>Ошибок:</td><td><strong class='text-danger'>$errors</strong></td></tr>";
            
            if ($is_conversion_active && $converted_total > 0) {
                echo "<tr><td>Конвертировано валюты:</td><td><strong>" . number_format($converted_total, 2) . " " . $currency_code . "</strong></td></tr>";
                echo "<tr><td>Использованный курс:</td><td><strong>1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</strong></td></tr>";
            }
            
            echo "</table>
                </div>";
            
            // Статистика
            $stats = $pdo->prepare("SELECT COUNT(*) as total FROM supplier_products WHERE supplier_id = ?");
            $stats->execute([$supplier_id]);
            $total_products = $stats->fetch()['total'];
            
            // Статистика по валютам
            $currency_stats = $pdo->prepare("
                SELECT currency_code, COUNT(*) as count, 
                       AVG(original_price) as avg_original,
                       AVG(converted_price) as avg_converted
                FROM supplier_products 
                WHERE supplier_id = ? 
                GROUP BY currency_code
            ");
            $currency_stats->execute([$supplier_id]);
            $currency_data = $currency_stats->fetchAll();
            
            echo "<div class='card'>
                    <div class='card-header'>📊 Статистика после синхронизации</div>
                    <div class='card-body'>
                        <p>Всего товаров в базе: <strong>$total_products</strong></p>
                        <p>Новых товаров добавлено: <strong>$added</strong></p>
                        <p>Старых товаров обновлено: <strong>$updated</strong></p>";
            
            if (count($currency_data) > 0) {
                echo "<div class='mt-3 p-3 bg-light rounded'>";
                echo "<h6>Статистика по валютам:</h6>";
                foreach ($currency_data as $stat) {
                    echo "<p>";
                    echo "<span class='badge bg-info'>" . $stat['currency_code'] . "</span> ";
                    echo $stat['count'] . " товаров";
                    if ($stat['currency_code'] != 'RUB') {
                        echo " (средняя цена: " . number_format($stat['avg_original'], 2) . " " . 
                             $stat['currency_code'] . " → " . number_format($stat['avg_converted'], 2) . " ₽)";
                    }
                    echo "</p>";
                }
                echo "</div>";
            }
            
            echo "      <div class='mt-3'>
                            <a href='/catalog.php' class='btn btn-primary'>Перейти в каталог</a>
                            <a href='suppliers_info.php' class='btn btn-secondary'>Назад к поставщикам</a>
                            <a href='currency_rates.php?supplier_id=" . $supplier_id . "' class='btn btn-info'>
                                <i class='fas fa-exchange-alt'></i> Управление курсом
                            </a>
                        </div>
                    </div>
                </div>";
            
        } else {
            logMessage("❌ " . $test['message'], "error");
            echo "</div></div></div>";
        }
        
    } else {
        // Кнопка запуска
        echo "<div class='card'>
                <div class='card-header'>
                    <h5>Запуск полной синхронизации</h5>
                </div>
                <div class='card-body'>
                    <p>Нажмите кнопку для загрузки до 500 товаров от поставщика.</p>
                    <p>Это займет примерно 5-10 минут.</p>";
        
        if ($is_conversion_active) {
            echo "<div class='alert alert-info mb-3'>
                    <i class='fas fa-exchange-alt'></i> 
                    <strong>Конвертация активна!</strong> 
                    Цены будут конвертированы из " . $currency_code . " в рубли по курсу: 
                    <strong>1 " . $currency_code . " = " . number_format($rate_to_rub, 4) . " ₽</strong>
                </div>";
        }
        
        echo "      <div class='alert alert-warning'>
                        <h6>Что будет сделано:</h6>
                        <ul>
                            <li>Тест подключения к API buy-accs.net</li>
                            <li>Загрузка 500 товаров (5 страниц по 100)</li>";
        
        if ($is_conversion_active) {
            echo "<li>Конвертация цен из " . $currency_code . " в рубли</li>";
        }
        
        echo "          <li>Расчет цен с наценкой " . $supplier['markup_value'] . "%</li>
                            <li>Сохранение в базу данных</li>
                        </ul>
                    </div>
                    
                    <div class='d-grid gap-2'>
                        <a href='?action=full_sync' class='btn btn-success btn-lg' 
                           onclick='return confirm(\"Запустить полную синхронизацию 500 товаров? Это займет 5-10 минут.\")'>
                           🚀 Запустить полную синхронизацию
                        </a>
                        <a href='sync_buyaccs.php' class='btn btn-primary'>🔄 Быстрая синхронизация (20 товаров)</a>
                        <a href='currency_rates.php?supplier_id=" . $supplier_id . "' class='btn btn-info'>
                            <i class='fas fa-exchange-alt'></i> Настроить курс валюты
                        </a>
                        <a href='suppliers_info.php' class='btn btn-secondary'>← Назад</a>
                    </div>
                </div>
            </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "</div>
<script>
    // Функция для обновления прогресса
    function updateProgress(percent) {
        var progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
        }
    }
    
    // Функция для авто-скролла логов
    function scrollLogs() {
        var logBox = document.getElementById('logBox');
        if (logBox) {
            logBox.scrollTop = logBox.scrollHeight;
        }
    }
    
    // Скроллим каждую секунду
    setInterval(scrollLogs, 1000);
</script>
</body>
</html>";

// Функция для логирования с типами сообщений
function logMessage($message, $type = "info") {
    $timestamp = date('H:i:s');
    $class = "log-item " . $type;
    echo "<div class='$class'>[$timestamp] $message</div>";
    ob_flush();
    flush();
}
?>