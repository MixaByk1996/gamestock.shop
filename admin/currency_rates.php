<?php
// /www/gamestock.shop/admin/currency_rates.php

session_start();
require_once '../includes/config.php';

// Проверка админ-доступа
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    echo '<script>window.location.href = "index.php";</script>';
    exit();
}

// Устанавливаем переменные для заголовка
$page_title = "Управление курсами валют";
$page_icon = "fas fa-exchange-alt";
$page_subtitle = "Настройка конвертации валют поставщиков";
$active_menu = "currency";

// Обработка сохранения курса ДО подключения header.php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_rate'])) {
    require_once '../includes/currency_converter.php';
    $converter = new CurrencyConverter();
    $conn = getDBConnection();
    
    $supplier_id = intval($_POST['supplier_id']);
    $rate = floatval($_POST['rate']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $currency_code = $_POST['currency_code'];
    
    if ($converter->setSupplierRate($supplier_id, $rate, $is_active, $currency_code)) {
        $_SESSION['success_message'] = "Курс успешно сохранен! Цены товаров поставщика пересчитаны.";
        
        $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM supplier_products WHERE supplier_id = ?");
        $count_stmt->execute([$supplier_id]);
        $count = $count_stmt->fetch()['cnt'];
        $_SESSION['success_message'] .= " Товаров поставщика: " . $count;
    } else {
        $_SESSION['error_message'] = "Ошибка при сохранении курса";
    }
    
    echo '<script>window.location.href = "currency_rates.php";</script>';
    exit();
}

// Подключаем заголовок
require_once 'templates/header.php';

// Подключаем конвертер валют
require_once '../includes/currency_converter.php';
$converter = new CurrencyConverter();
$conn = getDBConnection();

// Получение списка поставщиков
$suppliers_stmt = $conn->prepare("SELECT id, name FROM suppliers ORDER BY name");
$suppliers_stmt->execute();
$suppliers = $suppliers_stmt->fetchAll();

// Получение текущих курсов
$rates = $converter->getAllRates();
$suppliers_without_rates = $converter->getSuppliersWithoutRates();

// Сообщения об успехе/ошибке
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="container mt-4">
    <h1><i class="fas fa-exchange-alt me-2"></i>Управление курсами валют</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Настройка курса для поставщика</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Поставщик:</label>
                            <select name="supplier_id" class="form-select" required id="supplierSelect">
                                <option value="">-- Выберите поставщика --</option>
                                <?php foreach($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>">
                                        <?php echo htmlspecialchars($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Валюта поставщика:</label>
                            <select name="currency_code" class="form-select" required id="currencySelect">
                                <option value="USD">USD - Доллар США</option>
                                <option value="EUR">EUR - Евро</option>
                                <option value="RUB">RUB - Российский рубль</option>
                            </select>
                            <div class="form-text">В этой валюте поставщик предоставляет цены</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Курс к рублю:</label>
                            <div class="input-group">
                                <span class="input-group-text">1 ед. =</span>
                                <input type="number" step="0.0001" name="rate" class="form-control" 
                                       value="80.45" required id="rateInput">
                                <span class="input-group-text">₽</span>
                            </div>
                            <div class="form-text">1 единица валюты поставщика = X рублей</div>
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <input type="checkbox" name="is_active" class="form-check-input" 
                                   id="is_active" checked value="1">
                            <label class="form-check-label" for="is_active">
                                <strong>Включить конвертацию</strong> (цены будут пересчитаны в рубли)
                            </label>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle"></i> 
                                При сохранении курса все товары этого поставщика будут автоматически 
                                пересчитаны по новому курсу. Цены на сайте отобразятся в рублях.
                            </small>
                        </div>
                        
                        <button type="submit" name="save_rate" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить курс и пересчитать товары
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Текущие курсы</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Поставщик</th>
                                    <th>Валюта</th>
                                    <th>Курс к рублю</th>
                                    <th>Конвертация</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($rates)): ?>
                                    <?php foreach($rates as $rate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rate['supplier_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $rate['currency_code']; ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo number_format($rate['rate_to_rub'], 4); ?></strong></td>
                                            <td>
                                                <?php if ($rate['is_active']): ?>
                                                    <span class="badge bg-success">Включена</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Отключена</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="fillForm(<?php echo $rate['supplier_id']; ?>, '<?php echo $rate['currency_code']; ?>', <?php echo $rate['rate_to_rub']; ?>, <?php echo $rate['is_active'] ? 'true' : 'false'; ?>)">
                                                    <i class="fas fa-edit"></i> Изменить
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle me-2"></i>Нет настроенных курсов
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($suppliers_without_rates)): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Поставщики без настроенного курса</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Поставщик</th>
                                    <th>Текущая валюта</th>
                                    <th>Товаров</th>
                                    <th>Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($suppliers_without_rates as $supplier): 
                                    $count_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM supplier_products WHERE supplier_id = ?");
                                    $count_stmt->execute([$supplier['id']]);
                                    $product_count = $count_stmt->fetch()['cnt'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $supplier['currency_code'] ?? 'RUB'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product_count; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="fillForm(<?php echo $supplier['id']; ?>, '<?php echo $supplier['currency_code'] ?? 'RUB'; ?>', 80.45, true)">
                                                <i class="fas fa-cog"></i> Настроить
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">
                        <i class="fas fa-info-circle"></i> Для этих поставщиков используется курс по умолчанию
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Как работает система конвертации</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-calculator me-2"></i> Пример расчета:</h6>
                            <div class="p-3 bg-light rounded">
                                <p><strong>Параметры:</strong></p>
                                <ul>
                                    <li>Поставщик: <code>buy-accs.net</code></li>
                                    <li>Валюта поставщика: <code>USD</code></li>
                                    <li>Установленный курс: <code>1 USD = 85.30 RUB</code></li>
                                    <li>Товар у поставщика: <code>$12.50</code></li>
                                    <li>Наценка магазина: <code>150%</code></li>
                                </ul>
                                <p><strong>Расчет:</strong></p>
                                <ol>
                                    <li>Конвертация: <code>12.50 × 85.30 = 1,066.25 ₽</code></li>
                                    <li>Наценка: <code>1,066.25 × 2.5 = 2,665.63 ₽</code></li>
                                </ol>
                                <p>Именно эта цена будет показана пользователю на сайте.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-cogs me-2"></i> Особенности системы:</h6>
                            <ul>
                                <li><strong>Для пользователей:</strong> Все цены всегда в рублях (₽)</li>
                                <li><strong>В админке:</strong> Видна оригинальная валюта и курс</li>
                                <li><strong>Автоматический пересчет:</strong> При изменении курса все товары поставщика обновляются</li>
                                <li><strong>Включать/выключать:</strong> Можно отключить конвертацию для любого поставщика</li>
                                <li><strong>Хранение данных:</strong> Сохраняются и оригинальная цена, и конвертированная</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Функция для заполнения формы данными из таблицы
function fillForm(supplierId, currencyCode, rate, isActive) {
    document.getElementById('supplierSelect').value = supplierId;
    document.getElementById('currencySelect').value = currencyCode;
    document.getElementById('rateInput').value = rate;
    document.getElementById('is_active').checked = isActive;
    
    document.getElementById('supplierSelect').scrollIntoView({ behavior: 'smooth' });
    document.getElementById('supplierSelect').focus();
    alert('Форма заполнена данными для выбранного поставщика. Проверьте параметры и нажмите "Сохранить".');
}

// Автоматическое заполнение формы при наличии supplier_id в URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('supplier_id')) {
    const supplierId = urlParams.get('supplier_id');
    const currency = urlParams.get('currency') || 'USD';
    const rate = urlParams.get('rate') || 80.45;
    
    setTimeout(() => {
        fillForm(supplierId, currency, rate, true);
    }, 500);
}
</script>

<?php require_once 'templates/footer.php'; ?>