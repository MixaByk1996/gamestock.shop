<?php
// cabinet/index.php - Личный кабинет с регистрацией и входом
session_start();

require_once __DIR__ . '/../includes/config.php';

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /cabinet/');
    exit();
}

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
// ВХОД
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['admin'] = (bool)$user['is_admin'];
                $_SESSION['email'] = $user['email'];

// Обновляем время последнего входа
                $update = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $update->execute([$user['id']]);

                header('Location: /cabinet/');
                exit();
            } else {
                $login_error = "Неверный логин или пароль";
            }
        } catch (Exception $e) {
            $login_error = "Ошибка базы данных";
        }
    }

    elseif (isset($_POST['register'])) {
// РЕГИСТРАЦИЯ
        $username = trim($_POST['reg_username'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $password_confirm = $_POST['reg_password_confirm'] ?? '';

        $errors = [];

// Валидация
        if (strlen($username) < 3) $errors[] = "Логин должен быть не менее 3 символов";
        if (strlen($username) > 50) $errors[] = "Логин должен быть не более 50 символов";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Некорректный email";
        if (strlen($password) < 6) $errors[] = "Пароль должен быть не менее 6 символов";
        if ($password !== $password_confirm) $errors[] = "Пароли не совпадают";

        if (empty($errors)) {
            try {
                $pdo = getDBConnection();

// Проверяем уникальность
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->fetch()) {
                    $errors[] = "Пользователь с таким логином или email уже существует";
                } else {
// Создаем пользователя БЕЗ БОНУСА
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
INSERT INTO users (username, email, password, balance, is_admin, created_at)
VALUES (?, ?, ?, 0.00, FALSE, NOW())
");

                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $user_id = $pdo->lastInsertId();

                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['admin'] = false;
                        $_SESSION['email'] = $email;

                        $register_success = "Регистрация успешна!";
                        header('Location: /cabinet/');
                        exit();
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Ошибка при регистрации: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $register_error = implode("<br>", $errors);
        }
    }
}

// Если пользователь авторизован - показываем кабинет
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();

// Получаем данные пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            header('Location: /cabinet/');
            exit();
        }

// Получаем заказы
        $orders_stmt = $pdo->prepare("
SELECT * FROM orders
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 10
");
        $orders_stmt->execute([$_SESSION['user_id']]);
        $orders = $orders_stmt->fetchAll();

// Получаем транзакции
        $transactions_stmt = $pdo->prepare("
SELECT * FROM transactions
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 10
");
        $transactions_stmt->execute([$_SESSION['user_id']]);
        $transactions = $transactions_stmt->fetchAll();

// Получаем последний оплаченный заказ с данными аккаунта
        $last_paid_with_account = null;
        foreach ($orders as $order) {
            if ($order['payment_status'] === 'paid' && !empty($order['login_data']) && !empty($order['password_data'])) {
                $last_paid_with_account = $order;
                break;
            }
        }

        $balance = $user['balance'] ?? 0;

    } catch (Exception $e) {
        if (DEBUG_MODE) {
            die("Ошибка БД: " . $e->getMessage());
        }
        $user = [];
        $orders = [];
        $transactions = [];
        $balance = 0;
        $last_paid_with_account = null;
    }
}
?>
<!DOCTYPE html>
<!-- Favicon  -->
<link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
<html lang="ru">
<!-- Chatra {literal} -->
<script>
    (function(d, w, c) {
        w.ChatraID = 'GXdF3eAtsspXao2vf';
        var s = d.createElement('script');
        w[c] = w[c] || function() {
            (w[c].q = w[c].q || []).push(arguments);
        };
        s.async = true;
        s.src = 'https://call.chatra.io/chatra.js';
        if (d.head) d.head.appendChild(s);
    })(document, window, 'Chatra');
</script>
<!-- /Chatra {/literal} -->
<head>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=106588601', 'ym');

        ym(106588601, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/106588601" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_SESSION['user_id']) ? 'Личный кабинет' : 'Вход и регистрация' ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: lightskyblue;
            background: url(https://gamestock.shop/images/background.png), linear-gradient(140deg, royalblue 0%, cornflowerblue 33%, dodgerblue 67%, lightskyblue 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .auth-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
        }
        .auth-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .auth-tab.active {
            background: white;
            border-bottom: 3px solid #007bff;
            color: #007bff;
        }
        .auth-content {
            padding: 40px;
        }
        .auth-form {
            display: none;
        }
        .auth-form.active {
            display: block;
        }
        .test-accounts {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .cabinet-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .user-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
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

        .copyright {
            padding-top: 1.5rem;
            background-color: rgb(2, 55, 241);
            text-align: center;
        }
        .copyright {
            text-align: left;
        }
        .copyright .list-unstyled li {
            display: inline-block;
            margin-right: 1rem;
        }
        .copyright .statement {
            text-align: right;
        }
    </style>
</head>
<body>
<?php if (!isset($_SESSION['user_id'])): ?>
<!-- ФОРМЫ ВХОДА И РЕГИСТРАЦИИ -->
<!-- Favicon  -->

<link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
<?php include_once '../templates/header-main.php'; ?>
<div class="auth-container">
    <div class="auth-tabs">
        <div class="auth-tab active" data-tab ="login">Вход</div>
        <div class="auth-tab" data-tab="register" id="reg" href="#reg">Регистрация</div>
    </div>
    <div class="auth-content">
        <!-- ФОРМА ВХОДА -->
        <div id="login-form" class="auth-form active">

            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger"><?= $login_error ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="hidden" name="login" value="1">
                <div class="mb-3">
                    <label class="form-label">Логин или Email</label>
                    <input type="text" class="form-control" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username">
                </div>
                <div class="mb-3 form-password-toggle">
                    <label class="form-label">Пароль</label>
                    <input type="password" class="form-control" name="password" required
                           id="loginPassword" autocomplete="current-password">
                    <span class="password-toggle" onclick="togglePassword('loginPassword')">
<i class="fas fa-eye"></i>
</span>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <div style="text-align: center;"><a href="/cabinet" class="page-scroll"><button class="glow-on-hover2"><img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon"><b> Вход</b></button>
                        </a></div>
                    <style>
                        .glow-on-hover2-main{
                            width: 220px;
                            height: 50px;
                            border: none;
                            outline: none;
                            color: #fff;
                            background: #111;
                            cursor: pointer;
                            position: relative;
                            z-index: 0;
                            border-radius: 10px;
                        }
                        .glow-on-hover2{
                            display:inline;
                            width: 220px;
                            height: 50px;
                            border: none;
                            outline: none;
                            color: #fff;
                            background: #111;
                            cursor: pointer;
                            position: relative;
                            z-index: 0;
                            border-radius: 10px;
                        }
                        .glow-on-hover2:before {
                            content: '';
                            background: linear-gradient(45deg, #ff0000, #ff7300, #fffb00, #48ff00, #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
                            position: absolute;
                            top: -2px;
                            left:-2px;
                            background-size: 400%;
                            z-index: -1;
                            filter: blur(5px);
                            width: calc(100% + 4px);
                            height: calc(100% + 4px);
                            animation: glowing 20s linear infinite;
                            opacity: 0;
                            transition: opacity .3s ease-in-out;
                            border-radius: 10px;
                        }
                        .glow-on-hover2:active {
                            color: #000;
                        }
                        .glow-on-hover2:active:after {
                            background: transparent;
                        }
                        .glow-on-hover2:hover:before {
                            opacity: 1;
                        }
                        .glow-on-hover2:after {
                            z-index: -1;
                            content: '';
                            position: absolute;
                            width: 100%;
                            height: 100%;
                            background:  darkorange;
                            left: 0;
                            top: 0;
                            border-radius: 10px;
                        }
                    </style>
                </div>
                <div class="text-center">
                    <a href="#" onclick="showTab('register'); return false;">Нет аккаунта? Зарегистрируйтесь</a>
                </div>
            </form>
        </div>
        <!-- ФОРМА РЕГИСТРАЦИИ -->
        <div  class="auth-form" id="register-form">
            <?php if (isset($register_error)): ?>
                <div class="alert alert-danger"><?= $register_error ?></div>
            <?php endif; ?>

            <?php if (isset($register_success)): ?>
                <div class="alert alert-success"><?= $register_success ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <input type="hidden" name="register" value="1">
                <div class="mb-3">
                    <label class="form-label">Логин *</label>
                    <input type="text" class="form-control" name="reg_username" required
                           value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>"
                           minlength="3" maxlength="50"
                           pattern="[a-zA-Z0-9_]+"
                           title="Только латинские буквы, цифры и подчеркивание"
                           autocomplete="username">
                    <div class="form-text">Только латинские буквы, цифры и подчеркивание. 3-50 символов.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="reg_email" required
                           value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>"
                           autocomplete="email">
                </div>
                <div class="mb-3 form-password-toggle">
                    <label class="form-label">Пароль *</label>
                    <input type="password" class="form-control" name="reg_password" required
                           id="regPassword" minlength="6"
                           autocomplete="new-password">
                    <span class="password-toggle" onclick="togglePassword('regPassword')">
<i class="fas fa-eye"></i>
</span>
                    <div class="form-text">Не менее 6 символов</div>
                </div>
                <div class="mb-3 form-password-toggle">
                    <label class="form-label">Подтверждение пароля *</label>
                    <input type="password" class="form-control" name="reg_password_confirm" required
                           id="regPasswordConfirm"
                           autocomplete="new-password">
                    <span class="password-toggle" onclick="togglePassword('regPasswordConfirm')">
<i class="fas fa-eye"></i>
</span>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <div style="text-align: center;"><a href="/cabinet" class="page-scroll"><button class="glow-on-hover2"><img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon"><b> Зарегистрироваться</b></button>
                    </div>
                    <div class="text-center">
                        <a href="#" onclick="showTab('login'); return false;">Уже есть аккаунт? Войдите</a>
                    </div>
            </form>
        </div>
    </div>
    <div class="text-center p-3 border-top">
        <a href="/" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>На главную
        </a>
    </div>
    <?php else: ?>
    <!-- ЛИЧНЫЙ КАБИНЕТ -->
    <div class="cabinet-container">
        <!-- Шапка -->
        <div class="user-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-user-circle me-2"></i>Личный кабинет</h2>
                    <p class="mb-0">
                        Добро пожаловать, <strong><?= htmlspecialchars($user['username']) ?></strong>! |
                        ID: #<?= $user['id'] ?> |
                        <?= $user['is_admin'] ? '👑 Администратор' : '👤 Пользователь' ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h3 class="mb-0">
                        <i class="fas fa-wallet me-2"></i>
                        <?= number_format($balance, 2) ?> ₽
                    </h3>
                    <small>Ваш баланс</small>
                    <div class="mt-2">
                        <a href="?logout" class="btn btn-sm btn-light" onclick="return confirm('Выйти из аккаунта?')">
                            <i class="fas fa-sign-out-alt me-1"></i>Выйти
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Основной контент -->
        <div class="container mt-4">
            <div class="row">
                <div class="col-md-8">
                    <!-- Заказы -->
                    <div class="stats-card">
                        <h4><i class="fas fa-shopping-cart me-2"></i>Мои заказы</h4>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>Заказов пока нет</h5>
                                <p class="text-muted">Вы еще не совершали покупок в нашем магазине</p>
                                <a href="/catalog.php" class="btn btn-primary">Перейти в каталог</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>№</th>
                                        <th>Товар</th>
                                        <th>Дата</th>
                                        <th>Сумма</th>
                                        <th>Статус</th>
                                        <th>Данные</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?= $order['order_number'] ?></td>
                                            <td><?= htmlspecialchars(substr($order['product_name'] ?? 'Без названия', 0, 30)) ?></td>
                                            <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td><?= number_format($order['total_amount'], 2) ?> ₽</td>
                                            <td>
                                                <?php
                                                $status_badges = [
                                                    'new' => '<span class="badge bg-primary">Новый</span>',
                                                    'pending' => '<span class="badge bg-warning">Ожидает</span>',
                                                    'processing' => '<span class="badge bg-info">В обработке</span>',
                                                    'completed' => '<span class="badge bg-success">Завершен</span>',
                                                    'paid' => '<span class="badge bg-success">Оплачен</span>',
                                                    'failed' => '<span class="badge bg-danger">Ошибка</span>',
                                                    'cancelled' => '<span class="badge bg-secondary">Отменен</span>'
                                                ];
                                                echo $status_badges[$order['payment_status']] ?? $status_badges[$order['status']] ?? '<span class="badge bg-secondary">Неизвестно</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($order['login_data']) && !empty($order['password_data']) && $order['payment_status'] === 'paid'): ?>
                                                    <span class="badge bg-success" title="Данные доступны">✓ Есть</span>
                                                <?php elseif ($order['payment_status'] === 'paid'): ?>
                                                    <span class="badge bg-warning" title="Данные генерируются">⏳</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Транзакции -->
                    <div class="stats-card">
                        <h4><i class="fas fa-exchange-alt me-2"></i>История операций</h4>
                        <?php if (empty($transactions)): ?>
                            <p class="text-muted">История операций пуста</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Операция</th>
                                        <th>Сумма</th>
                                        <th>Описание</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?= date('d.m.Y H:i', strtotime($trans['created_at'])) ?></td>
                                            <td>
                                                <?php
                                                $type_names = [
                                                    'deposit' => '<span class="badge bg-success">Пополнение</span>',
                                                    'purchase' => '<span class="badge bg-primary">Покупка</span>',
                                                    'refund' => '<span class="badge bg-warning">Возврат</span>',
                                                    'bonus' => '<span class="badge bg-info">Бонус</span>'
                                                ];
                                                echo $type_names[$trans['type']] ?? '<span class="badge bg-secondary">' . $trans['type'] . '</span>';
                                                ?>
                                            </td>
                                            <td class="<?= $trans['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <strong><?= $trans['amount'] > 0 ? '+' : '' ?><?= number_format($trans['amount'], 2) ?> ₽</strong>
                                            </td>
                                            <td><?= htmlspecialchars($trans['description']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- Профиль -->
                    <div class="stats-card">
                        <h4><i class="fas fa-user me-2"></i>Мой профиль</h4>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Логин</label>
                            <div class="form-control"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Email</label>
                            <div class="form-control"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Баланс</label>
                            <div class="form-control bg-light">
                                <strong><?= number_format($balance, 2) ?> ₽</strong>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" onclick="alert('Редактирование профиля скоро будет доступно')">
                            <i class="fas fa-edit me-2"></i>Редактировать профиль</button>
                    </div>
                    <!-- Быстрые действия -->
                    <div class="stats-card">
                        <h4><i class="fas fa-bolt me-2"></i>Быстрые действия</h4>
                        <div class="d-grid gap-2">
                            <a href="/catalog.php" class="btn btn-outline-primary">
                                <i class="fas fa-store me-2"></i>Каталог товаров
                            </a>
                            <a href="/cabinet/deposit.php" class="btn btn-outline-success">
                                <i class="fas fa-plus-circle me-2"></i>Пополнить баланс
                            </a>
                            <?php if ($user['is_admin']): ?>
                                <a href="/admin/" class="btn btn-outline-warning">
                                    <i class="fas fa-cog me-2"></i>Админ-панель</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Информация -->
                    <div class="stats-card">
                        <h4><i class="fas fa-info-circle me-2"></i>Информация</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-calendar me-2 text-primary"></i>
                                Регистрация: <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-shopping-cart me-2 text-success"></i>
                                Заказов: <?= count($orders) ?>
                            </li>
                            <li>
                                <i class="fas fa-exchange-alt me-2 text-info"></i>
                                Операций: <?= count($transactions) ?>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Футер -->
                <div class="text-center p-3 border-top">
                    <p class="mb-0">© <?= date('Y') ?> <?= SITE_NAME ?>. Личный кабинет v1.0</p>
                </div>
            </div>
            <?php endif; ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                // Глобальная функция для переключения вкладок
                function showTab(tabName) {
// Ждем полной загрузки DOM
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            showTab(tabName);
                        });
                        return;
                    }

// Находим все элементы
                    const tabs = document.querySelectorAll('.auth-tab');
                    const forms = document.querySelectorAll('.auth-form');

// Проверяем, что элементы существуют
                    if (tabs.length === 0 || forms.length === 0) {
                        setTimeout(function() {
                            showTab(tabName);
                        }, 100);
                        return;
                    }

// Переключаем вкладки
                    tabs.forEach(tab => {
                        tab.classList.remove('active');
                    });
                    forms.forEach(form => {
                        form.classList.remove('active');
                    });

// Активируем нужные элементы
                    const activeTab = document.querySelector(`.auth-tab[data-tab="${tabName}"]`);
                    const activeForm = document.getElementById(`${tabName}-form`);

                    if (activeTab) activeTab.classList.add('active');
                    if (activeForm) activeForm.classList.add('active');

// Отменяем стандартное действие ссылки
                    return false;
                }

                // Добавляем обработчики на вкладки после загрузки
                document.addEventListener('DOMContentLoaded', function() {
// Обработчики для вкладок
                    document.querySelectorAll('.auth-tab').forEach(tab => {
                        tab.addEventListener('click', function() {
                            const tabName = this.getAttribute('data-tab');
                            showTab(tabName);
                        });
                    });

// Обработчики для ссылок в формах
                    document.querySelectorAll('a[onclick*="showTab"]').forEach(link => {
                        const oldOnClick = link.getAttribute('onclick');
                        link.removeAttribute('onclick');
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            const tabName = oldOnClick.includes("'register'") ? 'register' : 'login';
                            showTab(tabName);
                        });
                    });

// Автоматический фокус на первой форме
                    <?php if (isset($_POST['register']) || isset($register_error)): ?>
                    showTab('register');
                    <?php else: ?>
                    document.querySelector('input[name="username"]')?.focus();
                    <?php endif; ?>
                });

                // Функция показа/скрытия пароля
                function togglePassword(inputId) {
                    const input = document.getElementById(inputId);
                    const icon = input.nextElementSibling.querySelector('i');

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

                // Копирование в буфер обмена
                function copyToClipboard(inputId) {
                    const input = document.getElementById(inputId);
                    input.select();
                    input.setSelectionRange(0, 99999); // Для мобильных устройств

                    try {
                        const successful = document.execCommand('copy');
                        const button = event.target.closest('button');
                        const originalHTML = button.innerHTML;

                        button.innerHTML = '<i class="fas fa-check"></i>';
                        button.classList.remove('btn-outline-secondary');
                        button.classList.add('btn-success');

                        setTimeout(() => {
                            button.innerHTML = originalHTML;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-outline-secondary');
                        }, 1500);

                    } catch (err) {
                        alert('Не удалось скопировать. Скопируйте вручную.');
                    }
                }

                // Валидация формы регистрации
                document.getElementById('registerForm')?.addEventListener('submit', function(e) {
                    const password = document.querySelector('input[name="reg_password"]');
                    const confirm = document.querySelector('input[name="reg_password_confirm"]');

                    if (password.value !== confirm.value) {
                        e.preventDefault();
                        alert('Пароли не совпадают!');
                        confirm.focus();
                    }
                });
            </script>

</body>
</html>


