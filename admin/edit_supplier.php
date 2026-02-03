<?php
// /admin/edit_supplier.php
session_start();
require_once '../includes/config.php';
require_once '../includes/currency_converter.php'; // ДОБАВЛЕНО

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$supplier_id = $_GET['id'] ?? 1;

// Инициализация конвертера валют
$converter = new CurrencyConverter(); // ДОБАВЛЕНО

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Получаем данные поставщика
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    
    if (!$supplier) {
        die("<div class='alert alert-danger'>Поставщик не найден</div>");
    }
    
    // Получаем данные о курсе валют для этого поставщика
    $rate_data = $converter->getSupplierRate($supplier_id); // ДОБАВЛЕНО
    
    // Обновление наценки
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $markup_type = $_POST['markup_type'];
        $markup_value = (float) $_POST['markup_value'];
        
        $update = $pdo->prepare("UPDATE suppliers SET markup_type = ?, markup_value = ? WHERE id = ?");
        if ($update->execute([$markup_type, $markup_value, $supplier_id])) {
            $success = "✅ Наценка обновлена!";
            $supplier['markup_type'] = $markup_type;
            $supplier['markup_value'] = $markup_value;
            
            // После изменения наценки можно обновить цены товаров
            if (isset($_POST['update_existing']) && $_POST['update_existing'] == '1') {
                // Обновляем цены существующих товаров
                require_once '../includes/price_calculator.php';
                
                $stmt = $pdo->prepare("SELECT id, price FROM supplier_products WHERE supplier_id = ?");
                $stmt->execute([$supplier_id]);
                $products = $stmt->fetchAll();
                
                $updated_count = 0;
                foreach ($products as $product) {
                    $calculated = PriceCalculator::calculatePrice(
                        $product['price'],
                        $markup_type,
                        $markup_value
                    );
                    
                    $update_product = $pdo->prepare("UPDATE supplier_products SET our_price = ? WHERE id = ?");
                    $update_product->execute([$calculated['final_price'], $product['id']]);
                    $updated_count++;
                }
                
                $success .= " Обновлено цен: $updated_count";
            }
        } else {
            $error = "❌ Ошибка обновления";
        }
    }
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>Ошибка: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Настройка наценки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .example-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .price-example { border-left: 4px solid #28a745; padding-left: 15px; }
        .currency-info { border-left: 4px solid #17a2b8; padding-left: 15px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">⚡ Настройка наценки</span>
        <a href="suppliers_info.php" class="btn btn-light">← Назад к поставщикам</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><?= htmlspecialchars($supplier['name']) ?></h4>
                    <p class="mb-0 text-muted">ID: <?= $supplier['id'] ?></p>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><strong>Тип наценки:</strong></label>
                            <select name="markup_type" class="form-select" id="markupType">
                                <option value="percent" <?= $supplier['markup_type'] == 'percent' ? 'selected' : '' ?>>Процентная (%)</option>
                                <option value="fixed" <?= $supplier['markup_type'] == 'fixed' ? 'selected' : '' ?>>Фиксированная (₽)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Значение наценки:</strong></label>
                            <div class="input-group">
                                <input type="number" 
                                       name="markup_value" 
                                       class="form-control" 
                                       value="<?= $supplier['markup_value'] ?>" 
                                       step="0.01"
                                       min="0"
                                       max="1000"
                                       required
                                       id="markupValue">
                                <span class="input-group-text" id="markupSuffix">
                                    <?= $supplier['markup_type'] == 'percent' ? '%' : '₽' ?>
                                </span>
                            </div>
                            <div class="form-text">
                                <?php if ($supplier['markup_type'] == 'percent'): ?>
                                    Можно устанавливать больше 100%. Например: 150% = цена × 2.5
                                <?php else: ?>
                                    Фиксированная сумма добавляется к цене поставщика
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="update_existing" value="1" id="updateExisting">
                                <label class="form-check-label" for="updateExisting">
                                    <strong>Обновить цены существующих товаров</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                Если отмечено, то цены всех товаров этого поставщика будут пересчитаны
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">💾 Сохранить наценку</button>
                            <a href="sync_buyaccs.php?action=sync" class="btn btn-success btn-lg">🔄 Синхронизировать товары</a>
                            <a href="suppliers_info.php" class="btn btn-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Блок статистики поставщика -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>📊 Статистика поставщика</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stats = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total_products,
                            SUM(stock) as total_stock,
                            AVG(price) as avg_price,
                            AVG(our_price) as avg_our_price
                        FROM supplier_products 
                        WHERE supplier_id = ?
                    ");
                    $stats->execute([$supplier_id]);
                    $stat = $stats->fetch();
                    ?>
                    
                    <p><strong>Товаров в базе:</strong> <?= $stat['total_products'] ?? 0 ?></p>
                    <p><strong>Общее количество:</strong> <?= $stat['total_stock'] ?? 0 ?> шт.</p>
                    <p><strong>Средняя цена поставщика:</strong> <?= round($stat['avg_price'] ?? 0, 2) ?>₽</p>
                    <p><strong>Средняя наша цена:</strong> <?= round($stat['avg_our_price'] ?? 0, 2) ?>₽</p>
                    
                    <?php if ($supplier['last_sync']): ?>
                        <p><strong>Последняя синхронизация:</strong> <?= $supplier['last_sync'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Блок информации о валюте (ДОБАВЛЕНО) -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>💰 Настройки валюты</h5>
                </div>
                <div class="card-body currency-info">
                    <p><strong>Текущая валюта:</strong> 
                        <span class="badge bg-info"><?php echo $rate_data['currency_code']; ?></span>
                    </p>
                    
                    <?php if ($rate_data['currency_code'] != 'RUB'): ?>
                        <p><strong>Курс к рублю:</strong> 
                            <span class="text-success"><?php echo number_format($rate_data['rate_to_rub'], 4); ?></span>
                        </p>
                        <p><strong>Конвертация:</strong> 
                            <?php if ($rate_data['is_active']): ?>
                                <span class="badge bg-success">Включена</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Отключена</span>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <p><strong>Статус:</strong> 
                            <span class="badge bg-secondary">Цены в рублях</span>
                        </p>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="currency_rates.php?supplier_id=<?php echo $supplier_id; ?>" class="btn btn-info">
                            <i class="fas fa-exchange-alt"></i> Управление курсом
                        </a>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> 
                            При изменении курса все цены товаров будут автоматически пересчитаны
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>🧮 Примеры расчета</h5>
                </div>
                <div class="card-body">
                    <div class="example-box">
                        <h6>Текущие настройки:</h6>
                        <p>Тип: <strong><?= $supplier['markup_type'] == 'percent' ? 'Процентная' : 'Фиксированная' ?></strong></p>
                        <p>Значение: <strong><?= $supplier['markup_value'] ?><?= $supplier['markup_type'] == 'percent' ? '%' : '₽' ?></strong></p>
                        
                        <?php if ($supplier['markup_type'] == 'percent'): ?>
                            <p><strong>Множитель:</strong> ×<?= 1 + ($supplier['markup_value']/100) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="example-box price-example">
                        <h6>Пример для 100₽:</h6>
                        <?php
                        require_once '../includes/price_calculator.php';
                        $example = PriceCalculator::calculatePrice(100, $supplier['markup_type'], $supplier['markup_value']);
                        ?>
                        <p>Базовая цена: <strong>100₽</strong></p>
                        <p>Наценка: <strong><?= $example['markup_amount'] ?>₽</strong></p>
                        <p class="h4 text-success">Итоговая цена: <strong><?= $example['final_price'] ?>₽</strong></p>
                        
                        <?php if ($supplier['markup_type'] == 'percent'): ?>
                            <div class="alert alert-info mt-2">
                                <small>
                                    <strong>Формула:</strong> 100 × (1 + <?= $supplier['markup_value'] ?>/100) = <?= $example['final_price'] ?>₽
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Пример расчета с учетом конвертации валюты (ДОБАВЛЕНО) -->
                    <?php if ($rate_data['currency_code'] != 'RUB' && $rate_data['is_active']): ?>
                    <div class="example-box currency-info">
                        <h6><i class="fas fa-exchange-alt"></i> Пример с конвертацией валюты:</h6>
                        <p>Валюта поставщика: <strong><?= $rate_data['currency_code'] ?></strong></p>
                        <p>Курс: <strong>1 <?= $rate_data['currency_code'] ?> = <?= number_format($rate_data['rate_to_rub'], 2) ?> ₽</strong></p>
                        <p>Товар у поставщика: <strong>15.50 <?= $rate_data['currency_code'] ?></strong></p>
                        <p>Конвертация: <strong>15.50 × <?= number_format($rate_data['rate_to_rub'], 2) ?> = <?= number_format(15.50 * $rate_data['rate_to_rub'], 2) ?> ₽</strong></p>
                        <p>Наценка (<?= $supplier['markup_value'] ?>%): <strong>× <?= (1 + $supplier['markup_value']/100) ?></strong></p>
                        <p class="h4 text-success">Итоговая цена: <strong><?= number_format(15.50 * $rate_data['rate_to_rub'] * (1 + $supplier['markup_value']/100), 2) ?> ₽</strong></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="example-box">
                        <h6>Популярные значения наценки:</h6>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=50" class="btn btn-sm btn-outline-primary w-100">50% (×1.5)</a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=100" class="btn btn-sm btn-outline-primary w-100">100% (×2.0)</a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=150" class="btn btn-sm btn-primary w-100">150% (×2.5)</a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=200" class="btn btn-sm btn-outline-warning w-100">200% (×3.0)</a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=300" class="btn btn-sm btn-outline-warning w-100">300% (×4.0)</a>
                            </div>
                            <div class="col-6 mb-2">
                                <a href="?id=<?= $supplier_id ?>&quick=500" class="btn btn-sm btn-outline-danger w-100">500% (×6.0)</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="example-box">
                        <h6>Реальные товары из вашей базы:</h6>
                        <?php
                        $products = $pdo->prepare("
                            SELECT name, price, our_price, currency_code, original_price 
                            FROM supplier_products 
                            WHERE supplier_id = ? 
                            ORDER BY price DESC 
                            LIMIT 3
                        ");
                        $products->execute([$supplier_id]);
                        $sample_products = $products->fetchAll();
                        
                        if (count($sample_products) > 0):
                        ?>
                        <table class="table table-sm">
                            <tr>
                                <th>Товар</th>
                                <th>Цена поставщика</th>
                                <th>Наша цена</th>
                                <th>Валюта</th>
                            </tr>
                            <?php foreach ($sample_products as $prod): 
                                $currency = $prod['currency_code'] ?? 'RUB';
                                $original_price = $prod['original_price'] ?? $prod['price'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($prod['name'], 0, 20)) ?>...</td>
                                <td>
                                    <?php if ($currency != 'RUB'): ?>
                                        <span class="text-primary"><?= number_format($original_price, 2) ?> <?= $currency ?></span><br>
                                        <small class="text-muted"><?= number_format($prod['price'], 2) ?> ₽</small>
                                    <?php else: ?>
                                        <?= $prod['price'] ?>₽
                                    <?php endif; ?>
                                </td>
                                <td><strong class="text-success"><?= $prod['our_price'] ?>₽</strong></td>
                                <td>
                                    <?php if ($currency != 'RUB'): ?>
                                        <span class="badge bg-info"><?= $currency ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">RUB</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">Нет товаров в базе. <a href="sync_buyaccs.php">Запустите синхронизацию</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>⚠️ Важная информация</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6>После изменения наценки:</h6>
                        <ol>
                            <li>Новые товары будут добавляться с новой наценкой</li>
                            <li>Старые товары останутся с прежней ценой</li>
                            <li>Чтобы обновить все цены, отметьте "Обновить цены существующих товаров"</li>
                            <li>Или запустите полную синхронизацию</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6>Работа с валютами:</h6>
                        <ol>
                            <li>Настройте курс в разделе <a href="currency_rates.php">Курсы валют</a></li>
                            <li>При изменении курса все цены автоматически пересчитываются</li>
                            <li>Для пользователей все цены всегда отображаются в рублях</li>
                            <li>В админке видна оригинальная валюта и курс</li>
                        </ol>
                    </div>
                    
                    <p><strong>Рекомендация:</strong> При изменении наценки всегда запускайте синхронизацию, чтобы все товары имели одинаковую наценку.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Динамическое изменение суффикса
document.getElementById('markupType').addEventListener('change', function() {
    const suffix = document.getElementById('markupSuffix');
    const input = document.getElementById('markupValue');
    
    if (this.value === 'percent') {
        suffix.textContent = '%';
        input.max = 1000;
        input.placeholder = 'Например: 150';
    } else {
        suffix.textContent = '₽';
        input.removeAttribute('max');
        input.placeholder = 'Например: 500';
    }
});

// Быстрая установка наценки из URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('quick')) {
    const quickValue = urlParams.get('quick');
    document.getElementById('markupValue').value = quickValue;
    document.getElementById('markupType').value = 'percent';
    document.getElementById('markupSuffix').textContent = '%';
    
    // Показываем сообщение
    alert('Наценка установлена на ' + quickValue + '%. Нажмите "Сохранить наценку" для применения.');
}

// Автоматический скролл к валюте при наличии параметра
if (urlParams.has('scroll') && urlParams.get('scroll') === 'currency') {
    setTimeout(() => {
        const currencySection = document.querySelector('.currency-info');
        if (currencySection) {
            currencySection.scrollIntoView({ behavior: 'smooth' });
        }
    }, 500);
}
</script>

</body>
</html>