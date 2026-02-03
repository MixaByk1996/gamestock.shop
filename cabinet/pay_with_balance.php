<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/balance_system.php';

$order_id = $_GET['order_id'] ?? 0;

if (!isset($_SESSION['user_id'])) {
    header('Location: /cabinet/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pdo = getDBConnection();
$balanceSystem = new BalanceSystem();

// Получаем данные заказа
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND payment_status IN ('pending', 'failed')");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Заказ не найден или уже оплачен");
    }
} catch (Exception $e) {
    die("Ошибка получения данных заказа");
}

// Определяем сумму
$amount_field = isset($order['total_amount']) ? 'total_amount' : 'amount';
$amount = $order[$amount_field] ?? 0;

// Проверяем баланс
$user_id = $_SESSION['user_id'];
$balance = $balanceSystem->getUserBalance($user_id);

if ($balance < $amount) {
    header('Location: /cabinet/deposit.php?order_id=' . $order_id . '&needed=' . ($amount - $balance));
    exit;
}

// Обработка оплаты с баланса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $balanceSystem->makePurchase(
        $user_id,
        $amount,
        $order['product_id'],
        $order['product_name'],
        $order['customer_email'],
        $order['customer_telegram'] ?? ''
    );
    
    if ($result['success']) {
        header('Location: /payment_success.php?order_id=' . $order_id);
        exit;
    } else {
        header('Location: /payment_failed.php?order_id=' . $order_id . '&error=' . urlencode($result['message']));
        exit;
    }
}

$page_title = 'Оплата с баланса - ' . SITE_NAME;
require_once '../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">💰 Оплата с баланса</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <h5>Подтверждение оплаты</h5>
                        <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                        <p><strong>Товар:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                        <p><strong>Сумма:</strong> <span class="text-success fw-bold"><?= number_format($amount, 2) ?> ₽</span></p>
                        <p><strong>Ваш баланс:</strong> <span class="text-primary fw-bold"><?= number_format($balance, 2) ?> ₽</span></p>
                        <p><strong>Останется после оплаты:</strong> <span class="fw-bold"><?= number_format($balance - $amount, 2) ?> ₽</span></p>
                    </div>
                    
                    <form method="POST">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Подтвердить оплату
                            </button>
                            <a href="/payment.php?order_id=<?= $order_id ?>" class="btn btn-outline-primary">
                                <i class="fas fa-credit-card me-2"></i>Оплатить картой
                            </a>
                            <a href="/cabinet/" class="btn btn-secondary">
                                Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>