<?php
// quick_order.php - Простая страница быстрого заказа
session_start();
require_once '../includes/config.php';

$pdo = getDBConnection();

$product_id = $_GET['product_id'] ?? 0;
$error = '';
$success = '';

// Получаем информацию о товаре
if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM supplier_products WHERE id = ? AND stock > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $error = 'Товар не найден или нет в наличии';
    }
} else {
    $error = 'Товар не выбран';
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    $email = trim($_POST['email']);
    
    // Валидация
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Введите корректный Email адрес';
    } elseif (!$product) {
        $error = 'Товар не найден';
    } else {
        try {
            // Создаем заказ без регистрации пользователя
            $order_number = 'GS' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // ДАННЫЕ БУДУТ ПОЛУЧЕНЫ ПОСЛЕ ОПЛАТЫ
            // login_data и password_data будут NULL до оплаты
            
            // Создаем заказ
            $sql = "
                INSERT INTO orders (
                    user_id, order_number, product_id, product_name,
                    customer_email, total_amount, login_data, password_data,
                    status, payment_status, notes, created_at
                ) VALUES (0, ?, ?, ?, ?, ?, NULL, NULL, 'new', 'pending', ?, NOW())
            ";
            
            $notes = "Быстрый заказ. Email: " . $email;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $order_number,
                $product['id'],
                $product['name'],
                $email,
                $product['our_price'],
                $notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Уменьшаем количество товара
            $stmt = $pdo->prepare("UPDATE supplier_products SET stock = stock - 1 WHERE id = ?");
            $stmt->execute([$product['id']]);
            
            // Перенаправляем на оплату с параметром для показа модального окна
            header('Location: /payment.php?order_id=' . $order_id . '&fast_order=1');
            exit;
            
        } catch (Exception $e) {
            $error = 'Ошибка создания заказа: ' . $e->getMessage();
        }
    }
}

$page_title = 'Быстрый заказ - ' . SITE_NAME;
require_once '../templates/header.php';
?>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card">
<div class="card-header bg-primary text-white">
<h4 class="mb-0">🚀 Быстрый заказ</h4>
</div>
<div class="card-body">

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Информация о товаре -->
<?php if ($product): ?>
<div class="alert alert-info mb-4">
<h5>Выбранный товар:</h5>
<p><strong><?= htmlspecialchars($product['name']) ?></strong></p>
<p><strong>Цена:</strong> <span class="text-success fw-bold">
<?= number_format($product['our_price'], 2) ?> ₽
</span></p>
<p class="mb-0"><small><i class="fas fa-info-circle"></i> После оплаты вы получите реальные данные от поставщика</small></p>
</div>
<?php else: ?>
<div class="alert alert-warning">
<p>Товар не выбран. Выберите товар в <a href="/catalog.php">каталоге</a>.</p>
</div>
<?php endif; ?>

<!-- Простая форма заказа -->
<form method="POST" id="orderForm">
<input type="hidden" name="submit_order" value="1">
<input type="hidden" name="product_id" value="<?= $product_id ?>">
<div class="mb-4">
<label class="form-label">Ваш Email *</label>
<input type="email" class="form-control" name="email"
value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
placeholder="example@mail.ru" required>
<small class="text-muted">На этот email придут данные доступа</small>
</div>
<div class="alert alert-light">
<small>
<i class="fas fa-info-circle"></i>
<strong>Процесс быстрого заказа:</strong><br>
1. Введите email<br>
2. Перейдите на страницу оплаты<br>
3. Оплатите заказ<br>
4. <strong>Получите реальные данные от поставщика</strong> в личном кабинете<br>
5. Сохраните данные в безопасном месте
</small>
</div>
<div class="d-grid gap-2">
<button type="submit" class="btn btn-success btn-lg"
id="submitBtn" <?= !$product ? 'disabled' : '' ?>>
<i class="fas fa-shopping-cart me-2"></i>
Перейти к оплате <?= $product ? number_format($product['our_price'], 2) . ' ₽' : '' ?>
</button>
<a href="/catalog.php" class="btn btn-secondary">
Вернуться в каталог
</a>
</div>
</form>
</div>
</div>
<!-- Информация о процессе -->
<div class="card mt-4">
<div class="card-header">
<h6 class="mb-0">ℹ️ Как это работает:</h6>
</div>
<div class="card-body">
<ol class="mb-0">
<li><strong>Быстрая регистрация:</strong> Вам не нужно создавать аккаунт на сайте</li>
<li><strong>Оплата:</strong> Выберите удобный способ оплаты (карта, баланс)</li>
<li><strong>Получение данных:</strong> Сразу после оплаты система покупает аккаунт у поставщика</li>
<li><strong>Доступ к данным:</strong> Логин и пароль появятся в личном кабинете</li>
<li><strong>Копирование:</strong> Вы сможете скопировать данные одной кнопкой</li>
<li><strong>Гарантия:</strong> Все данные проверены и рабочие</li>
</ol>
</div>
</div>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderForm = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Валидация формы
    orderForm.addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]');
        
        // Простая валидация email
        if (!email.value || !email.value.includes('@')) {
            e.preventDefault();
            alert('Введите корректный email адрес');
            email.focus();
            return false;
        }
        
        // Показываем лоадер
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Создание заказа...';
            submitBtn.disabled = true;
            
            // Восстановить через 5 секунд на случай ошибки
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        }
    });
    
    // Автоматический фокус на поле email
    document.querySelector('input[name="email"]')?.focus();
});
</script>

<style>
.card {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    border-radius: 10px;
}

.card-header {
    border-bottom: none;
    border-radius: 10px 10px 0 0 !important;
}

.btn-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border: none;
    padding: 12px;
    font-weight: 600;
}

.btn-success:hover {
    background: linear-gradient(135deg, #20c997 0%, #198754 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
}

.btn-success:disabled {
    background: #6c757d;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: #6c757d;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
}

.alert-info {
    background-color: #e8f4fd;
    border-color: #b6e0fe;
    color: #05547f;
}

.form-control:focus {
    border-color: #20c997;
    box-shadow: 0 0 0 0.25rem rgba(32, 201, 151, 0.25);
}

ol {
    padding-left: 20px;
}

ol li {
    margin-bottom: 8px;
    padding-left: 5px;
}

ol li:last-child {
    margin-bottom: 0;
}
</style>

<?php require_once '../templates/footer.php'; ?>