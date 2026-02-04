<?php
// payment_success.php - Страница успешной оплаты
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

$page_title = 'Оплата успешна - ' . SITE_NAME;
require_once 'templates/header.php';

// Определяем правильное название поля суммы
$amount_field = isset($order['total_amount']) ? 'total_amount' : 'amount';
$amount = $order[$amount_field] ?? 0;
require_once 'templates/header-main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <div class="success-icon" style="font-size: 5rem; color: #28a745;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    
                    <h2 class="mb-3">✅ Оплата успешно завершена!</h2>
                    
                    <div class="alert alert-success mb-4">
                        <h5>Детали заказа:</h5>
                        <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                        <p><strong>Товар:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                        <p><strong>Сумма:</strong> <span class="fw-bold"><?= number_format($amount, 2) ?> ₽</span></p>
                        <p><strong>Статус:</strong> <span class="badge bg-success">Оплачено</span></p>
                        
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
                    
                    <div class="mb-4">
                        <?php if (!empty($order['login_data']) && !empty($order['password_data'])): ?>
                            <div class="alert alert-info">
                                <h6>Данные для доступа:</h6>
                                <p><strong>Логин:</strong> <code><?= htmlspecialchars($order['login_data']) ?></code></p>
                                <p><strong>Пароль:</strong> <code><?= htmlspecialchars($order['password_data']) ?></code></p>
                                <small class="text-muted">Сохраните эти данные!</small>
                            </div>
                        <?php else: ?>
                            <p>Данные для доступа к товару будут доступны в вашем личном кабинете.</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['customer_email']) && 
                                 strpos($order['customer_email'], '@') !== false && 
                                 strpos($order['customer_email'], 'gamestock.shop') === false): ?>
                            <p>На email <strong><?= htmlspecialchars($order['customer_email']) ?></strong> отправлено письмо с подтверждением.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/cabinet/" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-circle me-2"></i>Перейти в личный кабинет
                            </a>
                        <?php endif; ?>
                        <a href="catalog.php" class="btn btn-outline-primary">
                            Вернуться в каталог
                        </a>
                        <?php if (!empty($order['login_data']) && !empty($order['password_data'])): ?>
                            <button class="btn btn-outline-success" onclick="copyAccessData()">
                                <i class="fas fa-copy me-2"></i>Копировать данные
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'templates/footer.php'; ?>
<?php if (!empty($order['login_data']) && !empty($order['password_data'])): ?>
<script>
function copyAccessData() {
    const text = `Логин: ${'<?= $order['login_data'] ?>'}\nПароль: ${'<?= $order['password_data'] ?>'}`;
    
    navigator.clipboard.writeText(text).then(function() {
        alert('Данные скопированы в буфер обмена!');
    }, function(err) {
        alert('Ошибка копирования: ' + err);
    });
}
</script>
<?php endif; ?>

