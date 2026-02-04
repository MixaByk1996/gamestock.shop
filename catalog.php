<?php
// catalog.php - Страница каталога товаров с мгновенной покупкой
session_start();
require_once 'includes/config.php';
require_once 'includes/currency_converter.php';

// Получаем подключение к БД
$pdo = getDBConnection();

// Функция для создания заказа и перенаправления на оплату
function createAndRedirectToPayment($pdo, $product_id, $customer_email = '') {
    try {
        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Получаем информацию о товаре
        $stmt = $pdo->prepare("SELECT id, name, our_price, stock FROM supplier_products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Товар не найден");
        }

        if ($product['stock'] < 1) {
            throw new Exception("Товар закончился");
        }

        // Генерируем тестовые данные
        $login_data = 'user_' . strtoupper(substr(md5(uniqid()), 0, 8));
        $password_data = 'pass_' . strtoupper(substr(md5(uniqid()), 0, 10));
        $order_number = 'GS' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));

        // Если email не указан, используем автоматический
        if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $customer_email = 'customer_' . strtoupper(substr(md5(uniqid()), 0, 8)) . '@gamestock.shop';
            $notes = "Мгновенный заказ. Автоматический email";
        } else {
            $notes = "Мгновенный заказ. Email указан клиентом";
        }

        $sql = "
        INSERT INTO orders (
            user_id, order_number, product_id, product_name,
            customer_email, total_amount, login_data, password_data,
            status, payment_status, notes
        ) VALUES (0, ?, ?, ?, ?, ?, ?, ?, 'new', 'pending', ?)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $order_number,
            $product['id'],
            $product['name'],
            $customer_email,
            $product['our_price'],
            $login_data,
            $password_data,
            $notes
        ]);

        $order_id = $pdo->lastInsertId();

        // Уменьшаем количество товара
        $stmt = $pdo->prepare("UPDATE supplier_products SET stock = stock - 1 WHERE id = ?");
        $stmt->execute([$product_id]);

        // Фиксируем транзакцию
        $pdo->commit();

        // Перенаправляем сразу на оплату
        header('Location: payment.php?order_id=' . $order_id);
        exit;

    } catch (Exception $e) {
        // Откатываем транзакцию при ошибке
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Ошибка создания заказа: " . $e->getMessage());
    }
}

// Обработка покупки из формы быстрого заказа
if (isset($_GET['quick_buy']) && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $customer_email = isset($_GET['customer_email']) ? trim($_GET['customer_email']) : '';
    createAndRedirectToPayment($pdo, $product_id, $customer_email);
    exit;
}

// Обработка мгновенной покупки через каталог
if (isset($_GET['buy_now']) && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    createAndRedirectToPayment($pdo, $product_id);
    exit;
}

// Получаем категорию для быстрого заказа (если выбрана)
$quick_category = isset($_GET['quick_category']) ? (int)$_GET['quick_category'] : 0;

// Получаем товары для быстрого заказа по выбранной категории
$quick_products = [];
if ($quick_category > 0) {
    $quick_stmt = $pdo->prepare("
        SELECT id, name, our_price, stock
        FROM supplier_products
        WHERE category = ? AND stock > 0
        ORDER BY name
    ");
    $quick_stmt->execute([$quick_category]);
    $quick_products = $quick_stmt->fetchAll();
}

// Получаем товары для каталога (с пагинацией для отображения)
try {
    // Инициализация конвертера валют
    $converter = new CurrencyConverter();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "sp.name LIKE ?";
        $params[] = "%$search%";
    }

    if ($category > 0) {
        $where_conditions[] = "sp.category = ?";
        $params[] = $category;
    }

    $where_sql = '';
    if (!empty($where_conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Общее количество
    $total_sql = "SELECT COUNT(*) as total FROM supplier_products sp $where_sql";
    $total_stmt = $pdo->prepare($total_sql);
    if (!empty($params)) {
        $total_stmt->execute($params);
    } else {
        $total_stmt->execute();
    }

    $total = $total_stmt->fetchColumn() ?? 0;
    $total_pages = ceil($total / $per_page);

    // Получаем товары
    $sql = "
        SELECT sp.*, sp.description as product_description, s.name as supplier_name, s.id as supplier_id
        FROM supplier_products sp
        LEFT JOIN suppliers s ON sp.supplier_id = s.id
        $where_sql
        ORDER BY sp.last_updated DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch (Exception $e) {
    $products = [];
    $total = 0;
    $total_pages = 1;
    $search = '';
    $category = 0;
}

// Получаем список категорий для выпадающих списков
try {
    $category_stmt = $pdo->query("
        SELECT DISTINCT category
        FROM supplier_products
        WHERE stock > 0
        ORDER BY category
    ");
    $available_categories = $category_stmt->fetchAll();
} catch (Exception $e) {
    $available_categories = [];
}

// ВСЕ КАТЕГОРИИ НА РУССКОМ ЯЗЫКЕ
$category_names = [
    2 => 'Фейсбук',
    5 => 'Мобильные прокси',
    10 => 'Фейсбук Самозарядка',
    13 => 'Дискорд',
    15 => 'Реддит',
    18 => 'Яндекс Дзен',
    21 => 'SEO - Ссылки',
    25 => 'Скайп',
    26 => 'Инстаграм',
    29 => 'Google Ads',
    30 => 'Яндекс.Директ',
    42 => 'Google iOS',
    44 => 'TikTok Ads',
    50 => 'Твиттер',
    51 => 'Epic Games',
    53 => 'Трафик/SEO',
    68 => 'ВКонтакте',
    75 => 'Почта (Email)'
];

$page_title = 'Каталог товаров - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8" />
    <!-- Важное исправление для мобильных устройств -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <!-- SEO Meta Tags -->
    <meta name="description" content="Каталог цифровых товаров - Аккаунты, Буст, Скины, Игровая валюта, Предметы и другое" />
    <!-- Webpage Title -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <!-- Styles -->
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://gamestock.shop/styles/fonts.css" rel="stylesheet" />
    <link href="https://gamestock.shop/styles/awesome.css" rel="stylesheet" />
    <link href="https://gamestock.shop/styles/tailwind.css" rel="stylesheet" />
    <link href="https://gamestock.shop/styles/magnific-popup.css" rel="stylesheet" />
    <link href="https://gamestock.shop/styles/styles.css" rel="stylesheet" />
    <!-- Bootstrap для форм -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Мобильные стили -->
    <style>
        /* ИСПРАВЛЕНИЕ ДЛЯ ХЕДЕРА - ВСЕГДА СИНИЙ */
        .navbar.fixed-top {
            background: linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
            transition: none !important;
        }

        /* Убираем все эффекты изменения цвета при скролле */
        .navbar.fixed-top.scrolled,
        .navbar.fixed-top.navbar-scrolled {
            background: linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        }

        /* Цвет текста в хедере - всегда белый */
        .navbar-brand,
        .navbar-brand:hover,
        .navbar-brand:focus,
        .nav-link,
        .nav-link:hover,
        .nav-link:focus,
        .navbar-toggler-icon {
            color: white !important;
        }

        /* Иконка бургер-меню */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }

        /* Для dropdown меню */
        .dropdown-menu {
            background: white !important;
        }

        .dropdown-item {
            color: #333 !important;
        }

        .dropdown-item:hover {
            background: #f8f9fa !important;
            color: #333 !important;
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            .navbar.fixed-top {
                padding: 10px 0 !important;
            }

            .container {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }

            .mobile-hidden {
                display: none !important;
            }

            .mobile-full-width {
                width: 100% !important;
            }

            .mobile-padding {
                padding: 10px !important;
            }

            /* Адаптивные карточки */
            .product-card {
                margin-bottom: 15px !important;
            }

            .product-card .card-body {
                padding: 15px !important;
            }

            /* Адаптивные заголовки */
            h1 {
                font-size: 1.8rem !important;
                margin-bottom: 20px !important;
            }

            h4, h5 {
                font-size: 1.2rem !important;
            }

            /* Адаптивные кнопки */
            .btn-lg {
                padding: 12px !important;
                font-size: 16px !important;
            }

            /* Адаптивные формы */
            .form-control-lg, .form-select-lg {
                font-size: 16px !important;
                padding: 12px !important;
            }

            /* Скрытие лишних элементов на мобильных */
            .desktop-only {
                display: none !important;
            }

            /* Уменьшаем отступы */
            .mb-4, .mt-5 {
                margin-bottom: 1rem !important;
                margin-top: 1rem !important;
            }

            /* Адаптация статистики */
            .stat-card {
                margin-bottom: 10px !important;
            }

            .stat-card .display-6 {
                font-size: 1.5rem !important;
            }

            /* Пагинация для мобильных */
            .pagination {
                flex-wrap: wrap !important;
            }

            .page-link {
                padding: 8px 12px !important;
                font-size: 14px !important;
            }

            /* Форма быстрого заказа - вертикальная */
            #quickOrderForm .row > div {
                margin-bottom: 15px !important;
            }

            /* Мобильное меню товаров */
            .mobile-category-filter {
                overflow-x: auto;
                white-space: nowrap;
                margin-bottom: 15px;
            }

            .mobile-category-filter .badge {
                margin-right: 5px;
                margin-bottom: 5px;
            }
        }

        /* Для очень маленьких экранов */
        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem !important;
            }

            .card-header h4 {
                font-size: 1.1rem !important;
            }

            .btn-lg {
                padding: 10px !important;
                font-size: 14px !important;
            }

            #totalAmount {
                font-size: 1.5rem !important;
            }

            .product-title {
                font-size: 1rem !important;
                line-height: 1.3 !important;
            }

            .product-description {
                font-size: 0.8rem !important;
                line-height: 1.2 !important;
            }
        }

        /* Общие улучшения для мобильных */
        .mobile-touch-target {
            min-height: 44px !important;
            min-width: 44px !important;
        }

        /* Улучшение скролла на мобильных */
        * {
            -webkit-overflow-scrolling: touch;
        }

        /* Оптимизация для iOS */
        input, select, textarea {
            font-size: 16px !important; /* Предотвращает зум на iOS */
        }

        /* Улучшение отображения выпадающих списков на мобильных */
        select.form-select-lg {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem !important;
        }
    </style>
    <!-- Favicon  -->
    <link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
</head>
<body data-spy="scroll" data-target=".fixed-top">

<?php include 'templates/header-main.php'; ?>

<!-- Адаптивный отступ для фиксированной навигации -->
<div style="height: 60px;" class="mobile-hidden"></div>
<div style="height: 50px;" class="desktop-only"></div>

<div class="container mt-3 mt-md-5 pt-2 pt-md-4 mobile-padding">
    <!-- Заголовок для мобильных -->
    <h1 class="mb-3 mb-md-4 text-center text-md-start">
        <span class="d-inline-block d-md-none">🎮</span>
        <span class="d-none d-md-inline-block">🎮 Каталог товаров</span>
        <span class="d-inline-block d-md-none">Товары</span>
    </h1>

    <!-- Мобильное меню фильтров (только для маленьких экранов) -->
    <div class="d-md-none mobile-category-filter mb-3">
        <?php if (!empty($available_categories)): ?>
            <?php foreach ($available_categories as $cat):
                $cat_id = (int)$cat['category'];
                $cat_name = $category_names[$cat_id] ?? 'Категория ' . $cat_id;
                ?>
                <a href="?category=<?= $cat_id ?>"
                   class="badge <?= ($category == $cat_id) ? 'bg-primary' : 'bg-secondary' ?> text-decoration-none">
                    <?= htmlspecialchars(mb_substr($cat_name, 0, 15, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ФОРМА БЫСТРОГО ЗАКАЗА - АДАПТИРОВАННАЯ -->
    <div class="card mb-3 mb-md-4 shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="card-header bg-primary text-white rounded-top" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h4 class="mb-0">
                <i class="fas fa-bolt me-2"></i>
                <span class="d-none d-md-inline">Быстрый заказ</span>
                <span class="d-inline d-md-none">Быстрый заказ</span>
            </h4>
        </div>
        <div class="card-body p-2 p-md-3">
            <form method="get" id="quickOrderForm">
                <!-- Вертикальный стек на мобильных, горизонтальный на десктопе -->
                <div class="row g-2 g-md-3">
                    <!-- 1. Выберите категорию -->
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold d-block d-md-none">1. Категория *</label>
                        <label class="form-label fw-bold d-none d-md-block">1) Выберите категорию *</label>
                        <select class="form-select form-select-lg mobile-touch-target" name="quick_category" id="quickCategory" required
                                onchange="this.form.submit()">
                            <option value="">-- Выберите категорию --</option>
                            <?php
                            foreach ($category_names as $id => $name) {
                                $has_products = false;
                                foreach ($available_categories as $cat) {
                                    if ((int)$cat['category'] == $id) {
                                        $has_products = true;
                                        break;
                                    }
                                }

                                if ($has_products) {
                                    echo '<option value="' . $id . '" ';
                                    echo ($quick_category == $id) ? 'selected' : '';
                                    echo '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted d-none d-md-block">Отображаются только категории с товарами в наличии</small>
                    </div>
                    <!-- 2. Выберите товар -->
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold d-block d-md-none">2. Товар *</label>
                        <label class="form-label fw-bold d-none d-md-block">2) Выберите товар *</label>
                        <select class="form-select form-select-lg mobile-touch-target" name="product_id" id="productSelect" required
                            <?= empty($quick_products) ? 'disabled' : '' ?>
                                onchange="updateTotalPrice(this)">
                            <option value=""><?= empty($quick_products) ? '-- Сначала выберите категорию --' : '-- Выберите товар --' ?></option>

                            <?php if (!empty($quick_products)): ?>
                                <?php foreach ($quick_products as $product): ?>
                                    <option value="<?= $product['id'] ?>"
                                            data-price="<?= $product['our_price'] ?>">
                                        <?php
                                        // Сокращаем название для мобильных
                                        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                                        if (mb_strlen($name, 'UTF-8') > 30) {
                                            $name = mb_substr($name, 0, 30, 'UTF-8') . '...';
                                        }
                                        echo $name . ' - ' . number_format($product['our_price'], 2) . ' ₽';
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <!-- 3. Email для уведомлений -->
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-bold d-block d-md-none">3. Email (необяз.)</label>
                        <label class="form-label fw-bold d-none d-md-block">3) Ваш email (необязательно)</label>
                        <input type="email" class="form-control form-control-lg mobile-touch-target" name="customer_email"
                               id="customerEmail" placeholder="your@email.com">
                        <small class="form-text text-muted d-none d-md-block">Для уведомлений о заказе</small>
                    </div>
                    <!-- Итоговая сумма -->
                    <div class="col-12 mt-2 mt-md-3">
                        <div class="border p-2 p-md-3 rounded bg-white">
                            <h4 class="mb-0 text-center">
                                <span class="fw-bold d-none d-md-inline">Итоговая сумма к оплате:</span>
                                <span class="fw-bold d-inline d-md-none">Итого:</span>
                                <span id="totalAmount" class="text-success ms-2" style="font-size: 1.5rem; font-size: 1.8rem;">0.00 ₽</span>
                            </h4>
                        </div>
                    </div>
                    <!-- Кнопка оплаты -->
                    <div class="col-12 mt-2 mt-md-3">
                        <button type="submit" class="btn btn-success btn-lg w-100 py-2 py-md-3 mobile-touch-target"
                                id="payButton"
                                name="quick_buy"
                                value="1"
                            <?= empty($quick_products) ? 'disabled' : '' ?>
                                style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none;">
                            <i class="fas fa-credit-card me-2"></i>
                            <span class="d-none d-md-inline">ОПЛАТИТЬ СЕЙЧАС</span>
                            <span class="d-inline d-md-none">ОПЛАТИТЬ</span>
                        </button>
                        <small class="text-muted text-center d-block mt-1 mt-md-2">
                            <i class="fas fa-bolt me-1"></i>
                            <span class="d-none d-md-inline">Мгновенный переход к оплате</span>
                            <span class="d-inline d-md-none">Мгновенная оплата</span>
                        </small>
                    </div>
                </div>
            </form>
            <!-- Сообщение если нет товаров в категории -->
            <?php if ($quick_category > 0 && empty($quick_products)): ?>
                <div class="alert alert-warning mt-2 mt-md-3 p-2 p-md-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    В выбранной категории нет доступных товаров.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- КОНЕЦ ФОРМЫ БЫСТРОГО ЗАКАЗА -->

    <!-- Поиск и фильтры - адаптированные -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <form method="get" class="row g-2 g-md-3">
                <div class="col-12 col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white d-none d-md-inline-block">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg" placeholder="Поиск товаров..."
                               name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-lg mobile-touch-target" name="category">
                        <option value="0">Все категории</option>
                        <?php
                        foreach ($category_names as $id => $name) {
                            echo '<option value="' . $id . '" ';
                            echo ($category == $id) ? 'selected' : '';
                            echo '>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100 mobile-touch-target"
                            style="background: darkorange; border: none;">
                        <span class="d-none d-md-inline">Найти</span>
                        <span class="d-inline d-md-none"><i class="fas fa-search"></i></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-warning text-center py-4 py-md-5">
            <h4 class="mb-3">
                <?php if (!empty($search) || $category > 0): ?>
                    😕 Товаров не найдено
                <?php else: ?>
                    😔 Товаров пока нет
                <?php endif; ?>
            </h4>

            <?php if (!empty($search) || $category > 0): ?>
                <p class="mb-3">По вашему запросу ничего не найдено.</p>
                <a href="catalog.php" class="btn btn-primary mobile-touch-target">Показать все</a>
            <?php else: ?>
                <p class="mb-3">Каталог пуст.</p>
                <?php if (isset($_SESSION['admin'])): ?>
                    <a href="/admin/sync_buyaccs.php" class="btn btn-primary btn-lg mobile-touch-target">
                        <span class="d-none d-md-inline">🔄 Запустить синхронизацию</span>
                        <span class="d-inline d-md-none">🔄 Синхронизировать</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Статистика - адаптированная -->
        <div class="row mb-3 mb-md-4">
            <?php
            $stats = [
                ['value' => $total, 'label' => 'Всего товаров', 'icon' => '📊'],
                ['value' => count($products), 'label' => 'На странице', 'icon' => '📄'],
                ['value' => $total_pages, 'label' => 'Страниц', 'icon' => '📑'],
                ['value' => '', 'label' => 'Мгновенная оплата', 'icon' => '⚡']
            ];

            foreach ($stats as $stat): ?>
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                    <div class="card text-center border-0 shadow-sm stat-card h-100">
                        <div class="card-body p-2 p-md-3">
                            <h5 class="card-title display-6 mb-1"><?= $stat['icon'] ?> <?= $stat['value'] ?></h5>
                            <p class="card-text text-muted mb-0" style="font-size: 0.9rem;"><?= $stat['label'] ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Список товаров - адаптированный -->
        <div class="row">
            <?php foreach ($products as $product): ?>
                <?php
                // Определяем статус наличия
                if ($product['stock'] > 10) {
                    $stock_class = 'bg-success';
                    $stock_text = 'В наличии';
                } elseif ($product['stock'] > 0) {
                    $stock_class = 'bg-warning';
                    $stock_text = 'Мало';
                } else {
                    $stock_class = 'bg-secondary';
                    $stock_text = 'Нет в наличии';
                }

                // Определяем категорию
                $product_category = $product['category'] ?? 0;
                $category_name = $category_names[$product_category] ?? 'Категория ' . $product_category;

                // Сокращаем описание для мобильных
                $desc = $product['product_description'] ?? '';
                if (!empty($desc)) {
                    $short_desc = htmlspecialchars(mb_substr($desc, 0, 100, 'UTF-8'), ENT_QUOTES, 'UTF-8');
                    if (mb_strlen($desc, 'UTF-8') > 100) $short_desc .= '...';
                } else {
                    $short_desc = htmlspecialchars(mb_substr($product['name'], 0, 80, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '...';
                }
                ?>

                <div class="col-12 col-md-6 col-lg-4 mb-3">
                    <div class="card product-card h-100 border-0 shadow-sm"
                         style="transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;">
                        <div class="card-body d-flex flex-column p-3">
                            <!-- Заголовок и бейджи -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark text-truncate" style="max-width: 50%;">
                                    <?= htmlspecialchars(mb_substr($category_name, 0, 20, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="badge <?= $stock_class ?>">
                                    <?= $stock_text ?>
                                </span>
                            </div>

                            <!-- Название товара -->
                            <h5 class="card-title product-title mb-2" style="min-height: 3em;">
                                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                            </h5>

                            <!-- Описание -->
                            <p class="text-muted mb-3 flex-grow-1 product-description" style="font-size: 0.9rem;">
                                <?= $short_desc ?>
                            </p>

                            <!-- Цена и кнопка -->
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="h4 text-success mb-0">
                                            <?= number_format($product['our_price'], 2) ?> ₽
                                        </div>
                                        <small class="text-muted">
                                            Осталось: <?= $product['stock'] ?> шт.
                                        </small>
                                    </div>
                                </div>

                                <!-- Кнопка покупки -->
                                <div class="d-grid">
                                    <?php if ($product['stock'] > 0): ?>
                                        <a href="catalog.php?buy_now=1&product_id=<?= $product['id'] ?>"
                                           class="btn btn-primary btn-lg mobile-touch-target"
                                           style="background: darkorange; border: none;">
                                            <?php if ($product['stock'] > 10): ?>
                                                <span class="d-none d-md-inline">🚀 Купить сейчас</span>
                                                <span class="d-inline d-md-none">Купить</span>
                                            <?php else: ?>
                                                <span class="d-none d-md-inline">⚡ Купить (<?= $product['stock'] ?> шт.)</span>
                                                <span class="d-inline d-md-none">Купить (<?= $product['stock'] ?>)</span>
                                            <?php endif; ?>
                                        </a>
                                        <small class="text-muted text-center mt-1 d-none d-md-block">
                                            <i class="fas fa-bolt"></i> Мгновенный переход к оплате
                                        </small>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg mobile-touch-target" disabled>
                                            ❌ Нет в наличии
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Пагинация - адаптированная -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center flex-wrap">
                    <?php
                    // Показываем только первые 5 страниц на мобильных
                    $max_pages_mobile = 5;
                    $start_page = max(1, min($page - 2, $total_pages - $max_pages_mobile + 1));
                    $end_page = min($total_pages, $start_page + $max_pages_mobile - 1);

                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link mobile-touch-target"
                               href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($total_pages > $max_pages_mobile): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link mobile-touch-target"
                               href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>">
                                <?= $total_pages ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div><!-- /.container -->

<!-- Footer -->
<?php include('templates/footer.php'); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Функция обновления итоговой суммы
    function updateTotalPrice(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const totalAmountSpan = document.getElementById('totalAmount');
        const payButton = document.getElementById('payButton');

        if (selectedOption && selectedOption.value !== '') {
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            totalAmountSpan.textContent = price.toFixed(2) + ' ₽';
            payButton.disabled = false;
        } else {
            totalAmountSpan.textContent = '0.00 ₽';
            payButton.disabled = true;
        }
    }

    // ФОРСИРОВАННОЕ ИСПРАВЛЕНИЕ ЦВЕТА ХЕДЕРА
    document.addEventListener('DOMContentLoaded', function() {
        // Немедленно устанавливаем синий цвет хедера
        const navbar = document.querySelector('.navbar.fixed-top');
        if (navbar) {
            navbar.style.background = 'linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%)';
            navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            navbar.classList.remove('scrolled', 'navbar-scrolled');

            // Убираем все inline стили, которые могут менять цвет
            navbar.removeAttribute('style');
            setTimeout(() => {
                navbar.style.background = 'linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%) !important';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1) !important';
            }, 10);
        }

        // Отключаем любые скрипты, меняющие цвет хедера при скролле
        window.addEventListener('scroll', function() {
            if (navbar) {
                navbar.style.background = 'linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                navbar.classList.remove('scrolled', 'navbar-scrolled');
            }
        }, { passive: true });

        // Принудительное обновление каждые 100 мс на случай конфликтов
        const forceBlueHeader = setInterval(function() {
            if (navbar) {
                navbar.style.background = 'linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
                navbar.classList.remove('scrolled', 'navbar-scrolled');

                // Также исправляем все дочерние элементы
                const navBrand = document.querySelector('.navbar-brand');
                if (navBrand) {
                    navBrand.style.color = 'white';
                }

                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.style.color = 'white';
                });
            }
        }, 100);

        // Останавливаем через 5 секунд (после полной загрузки страницы)
        setTimeout(() => {
            clearInterval(forceBlueHeader);
        }, 5000);

        // Оптимизация для мобильных устройств
        // Предотвращение двойного тапа на мобильных
        let lastTap = 0;
        document.addEventListener('touchend', function(event) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            if (tapLength < 500 && tapLength > 0) {
                event.preventDefault();
            }
            lastTap = currentTime;
        }, false);

        // Автоматически выбираем первый товар, если он один
        const productSelect = document.getElementById('productSelect');
        if (productSelect && productSelect.options.length === 2) {
            productSelect.selectedIndex = 1;
            updateTotalPrice(productSelect);
        }

        // Улучшение UX для мобильных выпадающих списков
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('focus', function() {
                if (window.innerWidth <= 768) {
                    this.style.fontSize = '16px'; // Предотвращает зум на iOS
                }
            });

            select.addEventListener('blur', function() {
                if (window.innerWidth <= 768) {
                    this.style.fontSize = '';
                }
            });
        });

        // Валидация формы быстрого заказа (упрощенная для мобильных)
        const quickOrderForm = document.getElementById('quickOrderForm');
        if (quickOrderForm) {
            quickOrderForm.addEventListener('submit', function(e) {
                const category = document.getElementById('quickCategory')?.value;
                const product = document.getElementById('productSelect')?.value;

                if (!category || category === '') {
                    e.preventDefault();
                    alert('Выберите категорию');
                    return false;
                }

                if (!product || product === '') {
                    e.preventDefault();
                    alert('Выберите товар');
                    return false;
                }

                return true;
            });
        }
    });

    // Обработка изменения ориентации экрана
    window.addEventListener('orientationchange', function() {
        // Даем время на перерисовку
        setTimeout(function() {
            window.scrollTo(0, 0);

            // Снова фиксируем цвет хедера
            const navbar = document.querySelector('.navbar.fixed-top');
            if (navbar) {
                navbar.style.background = 'linear-gradient(135deg, rgb(2, 55, 241) 0%, rgb(1, 45, 200) 100%)';
                navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
            }
        }, 100);
    });
</script>
</body>
</html>