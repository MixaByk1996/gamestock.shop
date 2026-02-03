<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/balance_system.php';

$product_id = $_GET['product_id'] ?? 0;

if (!isset($_SESSION['user_id'])) {
    header('Location: /cabinet/login.php');
    exit;
}

$pdo = getDBConnection();
$balanceSystem = new BalanceSystem();

// Получаем товар
$stmt = $pdo->prepare("SELECT * FROM supplier_products WHERE id = ? AND stock > 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Товар не найден или нет в наличии");
}

// Получаем пользователя
$user_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

if (!$user) {
    die("Пользователь не найден");
}

$amount = $product['our_price'];
$balance = $balanceSystem->getUserBalance($_SESSION['user_id']);

// Обработка покупки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($balance < $amount) {
        header('Location: /cabinet/deposit.php?needed=' . ($amount - $balance));
        exit;
    }
    
    $result = $balanceSystem->makePurchase(
        $_SESSION['user_id'],
        $amount,
        $product_id,
        $product['name'],
        $user['email'],
        ''
    );
    
    if ($result['success']) {
        echo "<script>
            alert('Покупка успешна!\\\\nЗаказ: {$result['order_number']}\\\\nЛогин: {$result['login']}\\\\nПароль: {$result['password']}');
            window.location.href = '/catalog.php';
        </script>";
        exit;
    } else {
        die("Ошибка: " . $result['message']);
    }
}

$page_title = 'Покупка с баланса - ' . SITE_NAME;
require_once '../templates/header.php';
?>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card">
<div class="card-header bg-success text-white">
<h4 class="mb-0">💰 Покупка с баланса</h4>
</div>
<div class="card-body">
<div class="alert alert-info mb-4">
<p><strong>Товар:</strong> <?= htmlspecialchars($product['name']) ?></p>
<p><strong>Цена:</strong> <span class="text-success fw-bold"><?= number_format($amount, 2) ?> ₽</span></p>
<p><strong>Ваш баланс:</strong> <span class="text-primary fw-bold"><?= number_format($balance, 2) ?> ₽</span></p>
<p><strong>Останется:</strong> <span class="fw-bold"><?= number_format($balance - $amount, 2) ?> ₽</span></p>
</div>
<form method="POST">
<div class="d-grid gap-2">
<button type="submit" class="btn btn-success btn-lg" <?= $balance < $amount ? 'disabled' : '' ?>>
<?php if ($balance >= $amount): ?>
<i class="fas fa-bolt me-2"></i>Купить моментально
<?php else: ?>
<i class="fas fa-exclamation-triangle me-2"></i>Недостаточно средств
<?php endif; ?>
</button>

<?php if ($balance < $amount): ?>
<a href="/cabinet/deposit.php?needed=<?= $amount - $balance ?>" class="btn btn-warning">
<i class="fas fa-wallet me-2"></i>Пополнить баланс
</a>
<?php endif; ?>

<a href="/catalog.php" class="btn btn-secondary">Отмена</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>


<?php require_once '../templates/footer.php'; ?>