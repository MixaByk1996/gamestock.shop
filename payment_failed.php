<?php
// payment_failed.php - Страница неудачной оплаты
session_start();
require_once 'includes/config.php';

$pdo = getDBConnection();

$order_id = $_GET['order_id'] ?? 0;

// Получаем данные заказа
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Заказ не найден");
    }
} catch (Exception $e) {
    die("Ошибка получения данных заказа");
}

// ========== УБИРАЕМ АВТОМАТИЧЕСКИЕ EMAIL ==========
// Если email автоматический - скрываем его
if (!empty($order['customer_email']) && 
    strpos($order['customer_email'], 'customer_') === 0 && 
    strpos($order['customer_email'], '@gamestock.shop') !== false) {
    $order['customer_email'] = '';
}
// ========== КОНЕЦ ИСПРАВЛЕНИЯ ==========

$page_title = 'Ошибка оплаты - ' . SITE_NAME;
require_once 'templates/header.php';

// Определяем правильное название поля суммы
$amount_field = isset($order['total_amount']) ? 'total_amount' : 'amount';
$amount = $order[$amount_field] ?? 0;
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <div class="error-icon" style="font-size: 5rem; color: #dc3545;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    
                    <h2 class="mb-3">❌ Ошибка оплаты</h2>
                    
                    <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
                        <div class="alert alert-danger mb-4">
                            <h5><?= htmlspecialchars(urldecode($_GET['error'])) ?></h5>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-4">
                            <h5>При обработке платежа возникла ошибка</h5>
                            <p>Возможные причины:</p>
                            <ul class="text-start">
                                <li>Недостаточно средств на карте</li>
                                <li>Неверные данные карты</li>
                                <li>Превышен лимит операции</li>
                                <li>Технические проблемы банка</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-4">
                        <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                        <p><strong>Товар:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                        <p><strong>Сумма:</strong> <span class="fw-bold"><?= number_format($amount, 2) ?> ₽</span></p>
                        <p><strong>Статус:</strong> <span class="badge bg-danger">Оплата не прошла</span></p>
                        
                        <?php 
                        // ========== ИСПРАВЛЕНО: Показываем только НЕавтоматические email ==========
                        if (!empty($order['customer_email']) && 
                            strpos($order['customer_email'], 'customer_') !== 0): ?>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id']) && empty($order['customer_email'])): ?>
                            <p><strong>Email:</strong> <i>Данные в личном кабинете</i></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <!-- Проверяем статус заказа перед показом кнопки -->
                        <?php if ($order['payment_status'] === 'failed' || $order['payment_status'] === 'pending'): ?>
                            <a href="payment.php?order_id=<?= $order_id ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>Попробовать оплатить снова
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p>Этот заказ уже был обработан. Статус: <?= $order['payment_status'] ?></p>
                                <a href="catalog.php" class="btn btn-secondary">Вернуться в каталог</a>
                            </div>
                        <?php endif; ?>
                        <a href="catalog.php" class="btn btn-outline-secondary">
                            Вернуться в каталог
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/cabinet/" class="btn btn-outline-primary">
                                Перейти в личный кабинет
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>