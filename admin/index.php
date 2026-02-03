<?php
// admin/index.php - Панель администратора
session_start();
require_once '../includes/config.php';

// === ВЫХОД ИЗ СИСТЕМЫ ===
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Простой пароль админа (оставлю ваш оригинальный для совместимости)
$correct_login = 'admin@gamestock.shop';
$correct_password = 'MOIuog7&^G*@^@*D*1ddDl5';

// Проверяем авторизацию (сохраняем ваш старый метод + новую систему)
if (isset($_POST['login'])) {
    if ($_POST['login'] == $correct_login && $_POST['password'] == $correct_password) {
        $_SESSION['admin'] = true;
        $_SESSION['username'] = 'admin';
        header('Location: index.php');
        exit;
    }
}

// Если не авторизован - показываем форму входа
if (!isset($_SESSION['admin'])) {
    // Проверяем также новую систему авторизации через users
    try {
        $pdo = getDBConnection();
        if (isset($_POST['login']) && isset($_POST['password'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_admin = 1");
            $stmt->execute([$_POST['login']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($_POST['password'], $user['password'])) {
                $_SESSION['admin'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit;
            }
        }
    } catch (Exception $e) {
        // Если БД не настроена, используем старый метод
    }
    
    // Показываем форму входа
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход в админ-панель - <?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<meta name="lava-verify" content="S3a0fe43f5k4a1dr" />
<style>
body {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
min-height: 100vh;
display: flex;
align-items: center;
justify-content: center;
}
.login-card {
background: white;
border-radius: 15px;
box-shadow: 0 10px 40px rgba(0,0,0,0.2);
width: 100%;
max-width: 400px;
}
.login-header {
background: #2c3e50;
color: white;
border-radius: 15px 15px 0 0;
padding: 30px;
text-align: center;
}
.login-body {
padding: 30px;
}
.form-password-toggle {
position: relative;
}
.password-toggle {
position: absolute;
right: 10px;
top: 50%;
transform: translateY(-50%);
cursor: pointer;
color: #6c757d;
}
.test-login {
background: #f8f9fa;
border-radius: 10px;
padding: 15px;
margin-top: 20px;
font-size: 0.9rem;
}
</style>
</head>
<body>
<div class="login-card">
<div class="login-header">
<h3><i class="fas fa-user-shield me-2"></i>Админ-панель</h3>
<p class="mb-0"><?= SITE_NAME ?></p>
</div>
<div class="login-body">
<?php if (isset($_POST['login']) && !isset($_SESSION['admin'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
<i class="fas fa-exclamation-circle me-2"></i>Неверный логин или пароль
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
<div class="mb-3">
<label class="form-label">Логин:</label>
<div class="input-group">
<span class="input-group-text"><i class="fas fa-user"></i></span>
<input type="text" name="login" class="form-control"
value="<?= htmlspecialchars($_POST['login'] ?? 'admin') ?>"
required autocomplete="username">
</div>
</div>
<div class="mb-3 form-password-toggle">
<label class="form-label">Пароль:</label>
<div class="input-group">
<span class="input-group-text"><i class="fas fa-lock"></i></span>
<input type="password" name="password" class="form-control"
id="adminPassword" required autocomplete="current-password">
<span class="input-group-text password-toggle" onclick="togglePassword('adminPassword')">
<i class="fas fa-eye"></i>
</span>
</div>
</div>
<button type="submit" class="btn btn-primary w-100 py-2">
<i class="fas fa-sign-in-alt me-2"></i>Войти
</button>
</form>
<div class="test-login mt-4">
<h6><i class="fas fa-info-circle me-2"></i>Тестовые данные:</h6>
<p class="mb-1"><strong>Логин:</strong> <code>admin</code></p>
<p class="mb-0"><strong>Пароль:</strong> <code>admin123</code></p>
</div>
<div class="text-center mt-4">
<a href="/" class="text-decoration-none">
<i class="fas fa-arrow-left me-1"></i>На главную сайта
</a>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentElement.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Автофокус на поле пароля если логин уже введен
        document.addEventListener('DOMContentLoaded', function() {
            const loginInput = document.querySelector('input[name="login"]');
            const passwordInput = document.getElementById('adminPassword');
            
            if (loginInput.value && loginInput.value !== 'admin') {
                passwordInput.focus();
            } else {
                loginInput.focus();
            }
        });
    </script>
</body>
</html>
<?php
    exit;
}
// Конец формы входа

// ============= ОСНОВНАЯ АДМИН-ПАНЕЛЬ =============
try {
    $pdo = getDBConnection();
    
    // Статистика
    $stats = [];
    
    // Количество товаров
    $products_stmt = $pdo->query("SELECT COUNT(*) as count FROM supplier_products");
    $stats['products'] = $products_stmt->fetch()['count'];
    
    // Количество заказов
    $orders_stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $stats['orders'] = $orders_stmt->fetch()['count'];
    
    // Количество пользователей
    $users_stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $users_stmt->fetch()['count'];
    
    // Общая выручка
    $revenue_stmt = $pdo->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
    $stats['revenue'] = $revenue_stmt->fetch()['revenue'] ?? 0;
    
    // Наценка из настроек
    $markup_stmt = $pdo->query("SELECT markup_value FROM suppliers WHERE is_active = 1 LIMIT 1");
    $markup = $markup_stmt->fetch()['markup_value'] ?? 150;
    
    // Последние заказы
    $recent_orders = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Последние пользователи
    $recent_users = $pdo->query("
        SELECT * FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    $stats = ['products' => 0, 'orders' => 0, 'users' => 0, 'revenue' => 0];
    $markup = 150;
    $recent_orders = [];
    $recent_users = [];
}

// Настройки для шаблона админки
$page_title = 'Дашборд администратора';
$page_icon = 'fas fa-tachometer-alt';
$page_subtitle = 'Обзор статистики и управление магазином';
$active_menu = 'dashboard';

// Подключаем шапку админ-панели
require_once __DIR__ . '/templates/header.php';
?>

<!-- Статистика -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card-admin" style="border-top-color: #3498db;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="text-primary"><?= number_format($stats['products']) ?></h3>
                    <p class="text-muted mb-0">Товаров</p>
                </div>
                <div style="font-size: 2.5rem; opacity: 0.8; color: #3498db;">
                    <i class="fas fa-box"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-admin" style="border-top-color: #2ecc71;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="text-success"><?= number_format($stats['orders']) ?></h3>
                    <p class="text-muted mb-0">Заказов</p>
                </div>
                <div style="font-size: 2.5rem; opacity: 0.8; color: #2ecc71;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-admin" style="border-top-color: #e74c3c;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="text-danger"><?= number_format($stats['users']) ?></h3>
                    <p class="text-muted mb-0">Пользователей</p>
                </div>
                <div style="font-size: 2.5rem; opacity: 0.8; color: #e74c3c;">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-admin" style="border-top-color: #f39c12;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h3 class="text-warning"><?= number_format($stats['revenue'], 0) ?> ₽</h3>
                    <p class="text-muted mb-0">Выручка</p>
                </div>
                <div style="font-size: 2.5rem; opacity: 0.8; color: #f39c12;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Наценка и быстрые действия -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card-admin">
            <h4><i class="fas fa-percentage me-2 text-primary"></i>Настройки наценки</h4>
            <p class="mb-2">Текущая наценка: <span class="badge bg-primary fs-6"><?= $markup ?>%</span></p>
            <p class="text-muted mb-3">Все товары автоматически рассчитываются с этой наценкой. Изменить можно в настройках поставщика.</p>
            <a href="edit_supplier.php" class="btn btn-primary btn-admin">
                <i class="fas fa-edit me-2"></i>Изменить наценку
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-admin">
            <h5><i class="fas fa-bolt me-2 text-warning"></i>Быстрые действия</h5>
            <div class="d-grid gap-2">
                <a href="sync_buyaccs.php" class="btn btn-primary btn-admin">
                    <i class="fas fa-sync me-2"></i>Синхронизация товаров
                </a>
                <a href="payments_info.php" class="btn btn-success btn-admin">
                    <i class="fas fa-credit-card me-2"></i>Настройки Lava
                </a>
                <a href="suppliers_info.php" class="btn btn-info btn-admin">
                    <i class="fas fa-truck me-2"></i>Управление поставщиками
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Последние заказы -->
    <div class="col-md-7">
        <div class="card-admin">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Последние заказы</h5>
                <?php if (!empty($recent_orders)): ?>
                    <a href="#" class="btn btn-sm btn-outline-secondary">Все заказы</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($recent_orders)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5>Заказов пока нет</h5>
                    <p class="text-muted">Заказы будут отображаться здесь</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>№ Заказа</th>
                                <th>Пользователь</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['username'] ?? 'Удален') ?></td>
                                    <td><?= number_format($order['total_amount'], 2) ?> ₽</td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'new' => '<span class="badge bg-primary">Новый</span>',
                                            'processing' => '<span class="badge bg-warning">В обработке</span>',
                                            'completed' => '<span class="badge bg-success">Завершен</span>',
                                            'cancelled' => '<span class="badge bg-danger">Отменен</span>'
                                        ];
                                        echo $badges[$order['status']] ?? '<span class="badge bg-secondary">Неизвестно</span>';
                                        ?>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Последние пользователи и информация -->
    <div class="col-md-5">
        <!-- Последние пользователи -->
        <div class="card-admin">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Новые пользователи</h5>
                <?php if (!empty($recent_users)): ?>
                    <a href="users.php" class="btn btn-sm btn-outline-secondary">Все пользователи</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($recent_users)): ?>
                <p class="text-muted text-center py-3">Пользователей пока нет</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recent_users as $user): ?>
                        <div class="list-group-item border-0 p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge bg-warning">Админ</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Пользователь</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Информация о системе -->
        <div class="card-admin mt-4">
            <h5><i class="fas fa-info-circle me-2 text-info"></i>Информация о системе</h5>
            <ul class="list-unstyled mb-0">
                <li class="mb-2">
                    <i class="fas fa-server me-2"></i>
                    PHP: <?= phpversion() ?>
                </li>
                <li class="mb-2">
                    <i class="fas fa-database me-2"></i>
                    Товаров: <?= number_format($stats['products']) ?>
                </li>
                <li class="mb-2">
                    <i class="fas fa-percentage me-2"></i>
                    Наценка: <?= $markup ?>%
                </li>
                <li>
                    <i class="fas fa-user-shield me-2"></i>
                    Админ: <?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Основные функции (ИСПРАВЛЕННЫЕ кнопки) -->
<div class="row mt-4">
    <div class="col-md-3 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-box me-2"></i>Товары</h5>
                <p class="text-muted">Добавление и редактирование товаров</p>
                <!-- ИСПРАВЛЕНО: Была кнопка с alert, теперь ссылка на products.php -->
                <a href="products.php" class="btn btn-primary w-100">
                    <i class="fas fa-cog me-2"></i>Управлять
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-credit-card me-2"></i>Платежи</h5>
                <p class="text-muted">Настройка Lava и просмотр транзакций</p>
                <a href="payments_info.php" class="btn btn-success w-100">
                    <i class="fas fa-sliders-h me-2"></i>Настроить Lava
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-users me-2"></i>Пользователи</h5>
                <p class="text-muted">Управление клиентами и их заказами</p>
                <!-- ИСПРАВЛЕНО: Была кнопка с alert, теперь ссылка на users.php -->
                <a href="users.php" class="btn btn-info w-100">
                    <i class="fas fa-eye me-2"></i>Просмотреть
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-truck me-2"></i>Поставщики</h5>
                <p class="text-muted">Подключение API поставщиков</p>
                <a href="suppliers_info.php" class="btn btn-warning w-100">
                    <i class="fas fa-plus me-2"></i>Добавить поставщика
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Дополнительные функции -->
<div class="row mt-3">
    <div class="col-md-4 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-sync me-2"></i>Синхронизация</h5>
                <p class="text-muted">Обновление товаров от поставщиков</p>
                <a href="sync_buyaccs.php" class="btn btn-primary w-100">
                    Запустить синхронизацию
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-exchange-alt me-2"></i>Курсы валют</h5>
                <p class="text-muted">Настройка конвертации валют</p>
                <a href="currency_rates.php" class="btn btn-success w-100">
                    Управление курсами
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card-admin">
            <div class="card-body text-center">
                <h5><i class="fas fa-chart-line me-2"></i>Статистика</h5>
                <p class="text-muted">Анализ продаж и доходов</p>
                <button class="btn btn-info w-100" onclick="alert('Раздел статистики скоро будет доступен')">
                    Просмотреть отчеты
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Автоматическое обновление страницы каждые 5 минут для актуальной статистики
setTimeout(function() {
    window.location.reload();
}, 300000); // 5 минут = 300000 миллисекунд

// Перехватываем старые кнопки (если они остались где-то)
document.addEventListener('DOMContentLoaded', function() {
    // Перехватываем все кнопки с alert о разработке
    const oldButtons = document.querySelectorAll('button[onclick*="в разработке"]');
    oldButtons.forEach(button => {
        button.onclick = null;
        if (button.textContent.includes('Товары')) {
            button.onclick = function() { window.location.href = 'products.php'; };
        } else if (button.textContent.includes('Пользователи')) {
            button.onclick = function() { window.location.href = 'users.php'; };
        }
    });
    
    // Инициализация тултипов Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Подключаем подвал админ-панели
require_once __DIR__ . '/templates/footer.php';
?>