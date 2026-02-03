<?php
// templates/header.php - Общий заголовок для всего сайта (публичный!)
if (!isset($page_title)) $page_title = SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="https://gamestock.shop/images/favicon.ico" />
    <meta name="lava-verify" content="S3a0fe43f5k4a1dr" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .nav-link.active {
            font-weight: 600;
            color: #0d6efd !important;
        }
        .product-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            height: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-price {
            font-size: 1.5rem;
            color: #28a745;
            font-weight: bold;
        }
        .product-stock {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .stock-available {
            background-color: #d4edda;
            color: #155724;
        }
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        .category-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .search-info {
            background: #e8f4fd;
            border-left: 4px solid #0d6efd;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        /* Убираем ВСЕ админские стили */
        .sidebar, .admin-header, .card-admin, .btn-admin {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Публичная навигация (БЕЗ админ-панели!) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                🎮 <strong><?php echo SITE_NAME; ?></strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['admin'])) ? 'active' : ''; ?>" href="/">
                            Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'catalog.php' ? 'active' : ''; ?>" href="/catalog.php">
                            Каталог
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/cabinet/') !== false ? 'active' : ''; ?>" href="/cabinet/">
                            Личный кабинет
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/cabinet/reg/">
                            Регистрация
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">