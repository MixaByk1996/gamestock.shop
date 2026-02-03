<?php
// deposit.php - Страница пополнения баланса
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();

$amount = $_GET['amount'] ?? 0;
$needed = $_GET['needed'] ?? 0;

$page_title = 'Пополнение баланса - ' . SITE_NAME;
require_once '../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">💰 Пополнение баланса</h4>
                </div>
                <div class="card-body">
                    <?php if ($needed > 0): ?>
                        <div class="alert alert-info mb-4">
                            <h5>Для оплаты заказа не хватает:</h5>
                            <p class="h3 text-center text-primary"><?= number_format($needed, 2) ?> ₽</p>
                            <p class="text-center">Пополните баланс на эту сумму или больше</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/admin/add_balance.php">
                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Сумма пополнения *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="amount" 
                                       value="<?= max($needed, 100) ?>" min="10" step="10" required>
                                <span class="input-group-text">₽</span>
                            </div>
                            <small class="text-muted">Минимум 10 ₽</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Способ оплаты *</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="card">💳 Банковская карта</option>
                                <option value="yoomoney">💎 ЮMoney</option>
                                <option value="qiwi">🥝 QIWI</option>
                                <option value="cryptocurrency">₿ Криптовалюта</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-light">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                После пополнения баланса вы сможете моментально оплачивать товары без ввода карты.
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-wallet me-2"></i>Пополнить баланс
                            </button>
                            <a href="/payment.php?order_id=<?= $_GET['order_id'] ?? '' ?>" class="btn btn-outline-secondary">
                                Вернуться к оплате
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>