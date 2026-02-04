<?php
// cabinet/index.php - Личный кабинет с регистрацией и входом
session_start();

require_once __DIR__ . '/../includes/config.php';

// Определяем активную вкладку по GET-параметру
$default_tab = (isset($_POST['register']) || isset($register_error)) ? 'register' : 'login';
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['login', 'register']) ? $_GET['tab'] : $default_tab;

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
                $active_tab = 'login';
            }
        } catch (Exception $e) {
            $login_error = "Ошибка базы данных";
            $active_tab = 'login';
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
                        $active_tab = 'login';
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
            $active_tab = 'register';
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
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: lightskyblue;
            background: url(https://gamestock.shop/images/background.png), linear-gradient(140deg, royalblue 0%, cornflowerblue 33%, dodgerblue 67%, lightskyblue 100%);
            min-height: 100vh;
            padding: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Мобильные стили */
        @media (max-width: 767px) {
            body {
                padding: 10px;
                background-attachment: fixed;
            }
        }

        /* Контейнер для форм авторизации */
        .auth-container {
            max-width: 500px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        @media (max-width: 767px) {
            .auth-container {
                margin: 20px auto;
                border-radius: 12px;
            }
        }

        @media (max-width: 575px) {
            .auth-container {
                margin: 15px auto;
                border-radius: 10px;
            }
        }

        /* Вкладки */
        .auth-tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 18px 10px;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-size: 16px;
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .auth-tab {
                padding: 16px 8px;
                font-size: 15px;
            }
        }

        @media (max-width: 575px) {
            .auth-tab {
                padding: 14px 6px;
                font-size: 14px;
            }
        }

        .auth-tab.active {
            background: white;
            border-bottom: 3px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Контент форм */
        .auth-content {
            padding: 30px;
        }

        @media (max-width: 767px) {
            .auth-content {
                padding: 25px 20px;
            }
        }

        @media (max-width: 575px) {
            .auth-content {
                padding: 20px 15px;
            }
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Поля формы */
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }

        @media (max-width: 767px) {
            .form-control {
                padding: 10px 12px;
                font-size: 15px;
            }
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .form-text {
            font-size: 13px;
            color: var(--secondary-color);
            margin-top: 5px;
        }

        /* Переключатель пароля */
        .form-password-toggle {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            padding: 8px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        /* Кнопки */
        .btn {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s;
        }

        @media (max-width: 767px) {
            .btn {
                padding: 10px 20px;
                font-size: 15px;
            }
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        /* Стили для кнопки glow-on-hover2 */
        .glow-on-hover2 {
            width: 100%;
            max-width: 300px;
            height: 50px;
            border: none;
            outline: none;
            color: #fff;
            background: #111;
            cursor: pointer;
            position: relative;
            z-index: 0;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            margin: 0 auto;
            display: block;
        }

        @media (max-width: 767px) {
            .glow-on-hover2 {
                height: 48px;
                font-size: 15px;
                max-width: 100%;
            }
        }

        @media (max-width: 575px) {
            .glow-on-hover2 {
                height: 46px;
                font-size: 14px;
            }
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
            background: darkorange;
            left: 0;
            top: 0;
            border-radius: 10px;
        }

        @keyframes glowing {
            0% { background-position: 0 0; }
            50% { background-position: 400% 0; }
            100% { background-position: 0 0; }
        }

        .glow-on-hover2 img.inline {
            height: 20px;
            width: auto;
            vertical-align: middle;
            margin-right: 8px;
        }

        /* Алерты */
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: none;
        }

        @media (max-width: 767px) {
            .alert {
                padding: 12px;
                font-size: 14px;
            }
        }

        /* Контейнер личного кабинета */
        .cabinet-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        @media (max-width: 767px) {
            .cabinet-container {
                border-radius: 12px;
                margin: 15px auto;
            }
        }

        /* Карточка пользователя */
        .user-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
        }

        @media (max-width: 767px) {
            .user-card {
                padding: 25px 15px;
            }
        }

        @media (max-width: 575px) {
            .user-card {
                padding: 20px 12px;
            }
        }

        .user-card h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        @media (max-width: 767px) {
            .user-card h2 {
                font-size: 22px;
            }
        }

        @media (max-width: 575px) {
            .user-card h2 {
                font-size: 20px;
                text-align: center;
            }

            .user-card .row {
                flex-direction: column;
                text-align: center;
            }

            .user-card .text-md-end {
                text-align: center !important;
                margin-top: 15px;
            }
        }

        .user-card h3 {
            font-size: 28px;
            font-weight: 700;
        }

        @media (max-width: 767px) {
            .user-card h3 {
                font-size: 24px;
            }
        }

        /* Статистические карточки */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        @media (max-width: 767px) {
            .stats-card {
                padding: 15px;
                margin-bottom: 15px;
            }
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stats-card h4 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        @media (max-width: 767px) {
            .stats-card h4 {
                font-size: 16px;
                margin-bottom: 15px;
            }
        }

        /* Таблицы для мобильных */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            min-width: 600px; /* Минимальная ширина для прокрутки на мобильных */
            font-size: 14px;
        }

        @media (max-width: 767px) {
            .table {
                font-size: 13px;
            }

            .table th,
            .table td {
                padding: 10px 8px;
            }
        }

        .table th {
            font-weight: 600;
            background-color: var(--light-color);
            border-bottom: 2px solid #dee2e6;
        }

        /* Бейджи */
        .badge {
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }

        @media (max-width: 767px) {
            .badge {
                font-size: 11px;
                padding: 4px 8px;
            }
        }

        /* Списки */
        .list-unstyled li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }

        @media (max-width: 767px) {
            .list-unstyled li {
                font-size: 14px;
                padding: 8px 0;
            }
        }

        /* Кнопки быстрых действий */
        .d-grid.gap-2 .btn {
            text-align: left;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        @media (max-width: 767px) {
            .d-grid.gap-2 .btn {
                padding: 10px 12px;
                font-size: 14px;
            }

            .d-grid.gap-2 .btn i {
                font-size: 16px;
                margin-right: 8px;
            }
        }

        /* Футер кабинета */
        .cabinet-container .text-center {
            padding: 20px;
            background-color: var(--light-color);
            font-size: 14px;
            color: var(--secondary-color);
        }

        @media (max-width: 767px) {
            .cabinet-container .text-center {
                padding: 15px;
                font-size: 13px;
            }
        }

        /* Улучшения для очень маленьких экранов */
        @media (max-width: 360px) {
            body {
                padding: 8px;
            }

            .auth-tab {
                font-size: 13px;
                padding: 12px 5px;
            }

            .form-control {
                font-size: 14px;
                padding: 8px 10px;
            }

            .btn {
                padding: 8px 16px;
                font-size: 14px;
            }

            .glow-on-hover2 {
                height: 44px;
                font-size: 13px;
            }
        }

        /* Улучшение для планшетов */
        @media (min-width: 768px) and (max-width: 991px) {
            .container {
                max-width: 100%;
                padding-left: 20px;
                padding-right: 20px;
            }

            .cabinet-container {
                margin: 25px auto;
            }
        }

        /* Улучшение для ландшафтной ориентации на мобильных */
        @media (max-height: 600px) and (orientation: landscape) {
            .auth-container {
                margin: 10px auto;
                max-height: 90vh;
                overflow-y: auto;
            }

            .auth-content {
                padding: 15px;
            }

            .mb-3 {
                margin-bottom: 10px !important;
            }
        }

        /* Улучшение доступности */
        .nav-link, .auth-tab, .password-toggle, .btn {
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .form-control:focus, .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,.25);
        }

        /* Анимация загрузки */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading-spinner.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- ФОРМЫ ВХОДА И РЕГИСТРАЦИИ -->
    <link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
    <?php include_once '../templates/header-main.php'; ?>

    <div class="auth-container">
        <div class="auth-tabs">
            <div class="auth-tab <?= $active_tab === 'login' ? 'active' : '' ?>" data-tab="login">Вход</div>
            <div class="auth-tab <?= $active_tab === 'register' ? 'active' : '' ?>" data-tab="register" id="reg" href="#reg">Регистрация</div>
        </div>

        <div class="auth-content">
            <!-- ФОРМА ВХОДА -->
            <div id="login-form" class="auth-form <?= $active_tab === 'login' ? 'active' : '' ?>">
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $login_error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <input type="hidden" name="login" value="1">

                    <div class="mb-3">
                        <label class="form-label">Логин или Email</label>
                        <input type="text" class="form-control" name="username" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               autocomplete="username"
                               placeholder="Введите логин или email">
                    </div>

                    <div class="mb-3 form-password-toggle">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" required
                               id="loginPassword" autocomplete="current-password"
                               placeholder="Введите пароль">
                        <span class="password-toggle" onclick="togglePassword('loginPassword')">
                        <i class="fas fa-eye"></i>
                    </span>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="glow-on-hover2">
                            <img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon">
                            <b> Вход</b>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="text-decoration-none" onclick="showTab('register'); return false;">
                            Нет аккаунта? Зарегистрируйтесь
                        </a>
                    </div>
                </form>
            </div>

            <!-- ФОРМА РЕГИСТРАЦИИ -->
            <div id="register-form" class="auth-form <?= $active_tab === 'register' ? 'active' : '' ?>">
                <?php if (isset($register_error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $register_error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($register_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $register_success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
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
                               autocomplete="username"
                               placeholder="Придумайте логин">
                        <div class="form-text">Только латинские буквы, цифры и подчеркивание. 3-50 символов.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="reg_email" required
                               value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>"
                               autocomplete="email"
                               placeholder="Введите ваш email">
                    </div>

                    <div class="mb-3 form-password-toggle">
                        <label class="form-label">Пароль *</label>
                        <input type="password" class="form-control" name="reg_password" required
                               id="regPassword" minlength="6"
                               autocomplete="new-password"
                               placeholder="Придумайте пароль">
                        <span class="password-toggle" onclick="togglePassword('regPassword')">
                        <i class="fas fa-eye"></i>
                    </span>
                        <div class="form-text">Не менее 6 символов</div>
                    </div>

                    <div class="mb-3 form-password-toggle">
                        <label class="form-label">Подтверждение пароля *</label>
                        <input type="password" class="form-control" name="reg_password_confirm" required
                               id="regPasswordConfirm"
                               autocomplete="new-password"
                               placeholder="Повторите пароль">
                        <span class="password-toggle" onclick="togglePassword('regPasswordConfirm')">
                        <i class="fas fa-eye"></i>
                    </span>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="glow-on-hover2">
                            <img class="inline" src="https://gamestock.shop/icons/login.png" alt="icon">
                            <b> Зарегистрироваться</b>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="text-decoration-none" onclick="showTab('login'); return false;">
                            Уже есть аккаунт? Войдите
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center p-3 border-top bg-light">
            <a href="/" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>На главную
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- ЛИЧНЫЙ КАБИНЕТ -->
    <div class="cabinet-container">
        <!-- Шапка -->
        <div class="user-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2"><i class="fas fa-user-circle me-2"></i>Личный кабинет</h2>
                    <p class="mb-0">
                        Добро пожаловать, <strong><?= htmlspecialchars($user['username']) ?></strong>!<br class="d-md-none">
                        <span class="d-none d-md-inline"> | </span>
                        ID: #<?= $user['id'] ?> |
                        <?= $user['is_admin'] ? '👑 Администратор' : '👤 Пользователь' ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h3 class="mb-1">
                        <i class="fas fa-wallet me-2"></i>
                        <?= number_format($balance, 2) ?> ₽
                    </h3>
                    <small class="d-block mb-2">Ваш баланс</small>
                    <a href="?logout" class="btn btn-sm btn-light" onclick="return confirm('Выйти из аккаунта?')">
                        <i class="fas fa-sign-out-alt me-1"></i>Выйти
                    </a>
                </div>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="container-fluid mt-4 px-3 px-md-4">
            <div class="row">
                <!-- Левая колонка - Заказы и транзакции -->
                <div class="col-lg-8 col-md-7 mb-4 mb-md-0">
                    <!-- Заказы -->
                    <div class="stats-card">
                        <h4><i class="fas fa-shopping-cart me-2"></i>Мои заказы</h4>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>Заказов пока нет</h5>
                                <p class="text-muted mb-3">Вы еще не совершали покупок в нашем магазине</p>
                                <a href="/catalog.php" class="btn btn-primary">
                                    <i class="fas fa-store me-2"></i>Перейти в каталог
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                            <td class="fw-semibold"><?= $order['order_number'] ?></td>
                                            <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 120px;"
                                                  title="<?= htmlspecialchars($order['product_name'] ?? 'Без названия') ?>">
                                                <?= htmlspecialchars($order['product_name'] ?? 'Без названия') ?>
                                            </span>
                                            </td>
                                            <td class="text-nowrap"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td class="fw-semibold"><?= number_format($order['total_amount'], 2) ?> ₽</td>
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
                            <?php if (count($orders) >= 10): ?>
                                <div class="text-center mt-3">
                                    <a href="/cabinet/orders.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-list me-1"></i>Все заказы
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Транзакции -->
                    <div class="stats-card">
                        <h4><i class="fas fa-exchange-alt me-2"></i>История операций</h4>
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">История операций пуста</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
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
                                            <td class="text-nowrap"><?= date('d.m.Y H:i', strtotime($trans['created_at'])) ?></td>
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
                                            <td class="fw-semibold <?= $trans['amount'] > 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= $trans['amount'] > 0 ? '+' : '' ?><?= number_format($trans['amount'], 2) ?> ₽
                                            </td>
                                            <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 150px;"
                                                  title="<?= htmlspecialchars($trans['description']) ?>">
                                                <?= htmlspecialchars($trans['description']) ?>
                                            </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($transactions) >= 10): ?>
                                <div class="text-center mt-3">
                                    <a href="/cabinet/transactions.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-history me-1"></i>Вся история
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая колонка - Профиль и действия -->
                <div class="col-lg-4 col-md-5">
                    <!-- Профиль -->
                    <div class="stats-card">
                        <h4><i class="fas fa-user me-2"></i>Мой профиль</h4>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Логин</label>
                            <div class="form-control py-2 bg-light">
                                <?= htmlspecialchars($user['username']) ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">Email</label>
                            <div class="form-control py-2 bg-light">
                                <?= htmlspecialchars($user['email']) ?>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-muted mb-1">Баланс</label>
                            <div class="form-control py-2 bg-light fw-bold">
                                <?= number_format($balance, 2) ?> ₽
                            </div>
                        </div>
                        <button class="btn btn-primary w-100" onclick="alert('Редактирование профиля скоро будет доступно')">
                            <i class="fas fa-edit me-2"></i>Редактировать профиль
                        </button>
                    </div>

                    <!-- Быстрые действия -->
                    <div class="stats-card">
                        <h4><i class="fas fa-bolt me-2"></i>Быстрые действия</h4>
                        <div class="d-grid gap-2">
                            <a href="/catalog.php" class="btn btn-outline-primary text-start">
                                <i class="fas fa-store me-2"></i>Каталог товаров
                            </a>
                            <a href="/cabinet/deposit.php" class="btn btn-outline-success text-start">
                                <i class="fas fa-plus-circle me-2"></i>Пополнить баланс
                            </a>
                            <?php if ($user['is_admin']): ?>
                                <a href="/admin/" class="btn btn-outline-warning text-start">
                                    <i class="fas fa-cog me-2"></i>Админ-панель
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Информация -->
                    <div class="stats-card">
                        <h4><i class="fas fa-info-circle me-2"></i>Информация</h4>
                        <ul class="list-unstyled mb-0">
                            <li class="py-2 d-flex align-items-center">
                                <i class="fas fa-calendar me-3 text-primary" style="width: 20px;"></i>
                                <span>Регистрация: <strong><?= date('d.m.Y', strtotime($user['created_at'])) ?></strong></span>
                            </li>
                            <li class="py-2 d-flex align-items-center">
                                <i class="fas fa-shopping-cart me-3 text-success" style="width: 20px;"></i>
                                <span>Заказов: <strong><?= count($orders) ?></strong></span>
                            </li>
                            <li class="py-2 d-flex align-items-center">
                                <i class="fas fa-exchange-alt me-3 text-info" style="width: 20px;"></i>
                                <span>Операций: <strong><?= count($transactions) ?></strong></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Футер кабинета -->
            <div class="text-center p-3 mt-4 border-top bg-light">
                <p class="mb-0 text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    © <?= date('Y') ?> <?= SITE_NAME ?>. Личный кабинет v1.0
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Загрузка Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Глобальная функция для переключения вкладок
    function showTab(tabName) {
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

        // Обновляем URL без перезагрузки страницы
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);

        // Фокусируемся на первом поле в активной форме
        setTimeout(() => {
            const firstInput = activeForm.querySelector('input[type="text"], input[type="email"]');
            if (firstInput) {
                firstInput.focus();
                // Для мобильных устройств прокручиваем к полю ввода
                if (window.innerWidth <= 768) {
                    firstInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        }, 300);

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

            // Добавляем поддержку клавиатуры
            tab.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const tabName = this.getAttribute('data-tab');
                    showTab(tabName);
                }
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

        // Проверяем GET-параметр из URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');

        // Если есть параметр tab в URL и он соответствует одной из вкладок
        // Но только если пользователь не авторизован
        <?php if (!isset($_SESSION['user_id'])): ?>
        if (urlTab && ['login', 'register'].includes(urlTab)) {
            // Активируем вкладку из URL-параметра
            showTab(urlTab);
        }
        <?php endif; ?>

        // Инициализация Bootstrap компонентов
        var alertList = document.querySelectorAll('.alert');
        alertList.forEach(function (alert) {
            new bootstrap.Alert(alert);
        });

        // Добавляем плавную прокрутку для мобильных
        if (window.innerWidth <= 768) {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    });

    // Функция показа/скрытия пароля
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            icon.setAttribute('title', 'Скрыть пароль');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            icon.setAttribute('title', 'Показать пароль');
        }

        // Возвращаем фокус на поле ввода
        input.focus();
    }

    // Валидация формы регистрации
    document.getElementById('registerForm')?.addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="reg_password"]');
        const confirm = document.querySelector('input[name="reg_password_confirm"]');

        if (password.value !== confirm.value) {
            e.preventDefault();

            // Показываем ошибку
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-2';
            errorDiv.innerHTML = `
                Пароли не совпадают!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            // Вставляем ошибку перед кнопкой
            const submitButton = this.querySelector('button[type="submit"]');
            this.insertBefore(errorDiv, submitButton.parentElement);

            // Фокусируемся на поле подтверждения
            confirm.focus();

            // Добавляем красную рамку
            confirm.classList.add('is-invalid');
            password.classList.add('is-invalid');

            // Убираем рамку при исправлении
            [password, confirm].forEach(input => {
                input.addEventListener('input', function() {
                    if (password.value === confirm.value) {
                        password.classList.remove('is-invalid');
                        confirm.classList.remove('is-invalid');
                    }
                });
            });
        }
    });

    // Добавляем обработку отправки форм для показа загрузки
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Обработка...';
                submitButton.disabled = true;

                // Восстанавливаем кнопку через 5 секунд на случай ошибки
                setTimeout(() => {
                    if (submitButton.disabled) {
                        submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Попробовать снова';
                        submitButton.disabled = false;
                    }
                }, 5000);
            }
        });
    });

    // Сохраняем оригинальный текст кнопок
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.setAttribute('data-original-text', button.innerHTML);
    });

    // Обработка изменения ориентации экрана
    let timeout;
    window.addEventListener('resize', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            // Пересчитываем размеры элементов при изменении размера окна
            if (typeof bootstrap !== 'undefined') {
                // Обновляем Bootstrap компоненты
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }, 250);
    });
</script>

</body>
<?php include('../templates/footer.php'); ?>
</html>