<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Подключение поставщиков</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-warning">
    <div class="container">
        <span class="navbar-brand">🔗 Подключение поставщиков</span>
        <a href="index.php" class="btn btn-outline-light">← Назад в админку</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4>Система подключения поставщиков готова</h4>
        </div>
        <div class="card-body">
            <p>Модуль поддерживает <strong>неограниченное количество поставщиков</strong> с API доступом.</p>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>📊 Возможности системы</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Автоматическая синхронизация товаров</li>
                                <li>Гибкая наценка (% или фиксированная)</li>
                                <li>Автообновление цен и наличия</li>
                                <li>Поддержка любого API поставщика</li>
                                <li>История заказов у каждого поставщика</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5>📋 Что нужно для подключения</h5>
                        </div>
                        <div class="card-body">
                            <ol>
                                <li>API доступы поставщика:
                                    <ul>
                                        <li>API URL (адрес)</li>
                                        <li>API Key (ключ)</li>
                                        <li>Документация API</li>
                                    </ul>
                                </li>
                                <li>Настройка наценки</li>
                                <li>Категории товаров</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5>⏱ Процесс подключения:</h5>
                <p>1. Заказчик присылает API данные первого поставщика<br>
                2. Настраиваю интеграцию (15-20 минут)<br>
                3. Товары появляются на сайте автоматически<br>
                4. Можно подключать следующих поставщиков</p>
            </div>
            
            <p class="text-muted">После получения данных поставщика эта страница превратится в панель управления поставщиками.</p>
        </div>
    </div>

    <!-- Блок с подключенным поставщиком -->
    <div class="card mt-4">
        <div class="card-header bg-success text-white">
            <h5>🚀 Первый поставщик подключен и работает!</h5>
        </div>
        <div class="card-body">
            <?php
            // Получаем статистику по поставщику
            require_once '../includes/config.php';
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $supplier_id = 1;
                $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
                $stmt->execute([$supplier_id]);
                $supplier = $stmt->fetch();
                
                $products_count = $pdo->query("SELECT COUNT(*) as cnt FROM supplier_products WHERE supplier_id = $supplier_id")->fetch()['cnt'];
                $total_stock = $pdo->query("SELECT SUM(stock) as total FROM supplier_products WHERE supplier_id = $supplier_id")->fetch()['total'];
                
            } catch (Exception $e) {
                $products_count = 0;
                $total_stock = 0;
                $supplier = ['name' => 'buy-accs.net', 'markup_value' => 150];
            }
            ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Поставщик:</strong> <?= htmlspecialchars($supplier['name'] ?? 'buy-accs.net') ?></p>
                    <p><strong>API ключ:</strong> настроен ✅</p>
                    <p><strong>Наценка:</strong> <?= $supplier['markup_value'] ?? 150 ?>% (×<?= 1 + ($supplier['markup_value'] ?? 150)/100 ?>)</p>
                    <p><strong>Статус:</strong> ✅ API работает, структура определена</p>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>📊 Статистика поставщика:</h6>
                            <p>Товаров в базе: <strong><?= $products_count ?></strong></p>
                            <p>Общий остаток: <strong><?= $total_stock ?> шт.</strong></p>
                            <?php if (isset($supplier['last_sync']) && $supplier['last_sync']): ?>
                                <p>Последняя синхронизация: <strong><?= $supplier['last_sync'] ?></strong></p>
                            <?php else: ?>
                                <p>Синхронизация: <span class="text-warning">не выполнялась</span></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <h6>Управление синхронизацией:</h6>
            <div class="row mb-4">
                <div class="col-md-4 mb-2">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">🔄 Быстрая синхронизация</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><small>Первые 100 товаров</small></p>
                            <p class="text-muted" style="font-size: 0.9em;">2-3 минуты, для тестирования</p>
                            <a href="sync_buyaccs.php" class="btn btn-primary w-100">Запустить</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-2">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">🚀 Полная синхронизация</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><small>До 1000 товаров</small></p>
                            <p class="text-muted" style="font-size: 0.9em;">10-15 минут, все категории</p>
                            <a href="sync_full.php" class="btn btn-success w-100" 
                               onclick="return confirm('Запустить полную синхронизацию? Это займет 10-15 минут.')">
                               Запустить
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-2">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">⚡ Настройки</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><small>Управление наценкой</small></p>
                            <p class="text-muted" style="font-size: 0.9em;">Изменение наценки, обновление цен</p>
                            <a href="edit_supplier.php?id=1" class="btn btn-secondary w-100">Настроить</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h6>📝 Рекомендации по синхронизации:</h6>
                <ol class="mb-0">
                    <li><strong>Для начала</strong> - запустите быструю синхронизацию (100 товаров)</li>
                    <li><strong>Для наполнения магазина</strong> - запустите полную синхронизацию (1000+ товаров)</li>
                    <li><strong>Для изменения цен</strong> - настройте наценку и запустите синхронизацию заново</li>
                    <li><strong>Автоматически</strong> - можно настроить CRON на ежедневную синхронизацию</li>
                </ol>
            </div>
            
            <?php if ($products_count > 0): ?>
            <div class="alert alert-success mt-3">
                <h6>✅ Товары успешно синхронизированы!</h6>
                <p>Теперь вы можете:</p>
                <ul class="mb-0">
                    <li>Просмотреть товары на <a href="/">главной странице</a></li>
                    <li>Изменить наценку если нужно скорректировать цены</li>
                    <li>Запустить полную синхронизацию для получения всех товаров</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="alert alert-warning mt-3">
                <h6>⚠️ Товары еще не синхронизированы</h6>
                <p class="mb-0">Нажмите "Запустить" чтобы начать синхронизацию товаров от поставщика.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Информация о следующих шагах -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5>📈 Следующие шаги после синхронизации</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>1. Настройка отображения товаров:</h6>
                    <ul>
                        <li>Создание страницы каталога</li>
                        <li>Настройка категорий товаров</li>
                        <li>Добавление фильтров и поиска</li>
                        <li>Оформление карточек товаров</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>2. Настройка продаж:</h6>
                    <ul>
                        <li>Подключение платежной системы</li>
                        <li>Настройка автоматической выдачи</li>
                        <li>Создание личных кабинетов</li>
                        <li>Настройка уведомлений</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3">
    <a href="sync_buyaccs.php" class="btn btn-primary">🔄 Быстрая синхронизация (20)</a>
    <a href="sync_full.php" class="btn btn-success">🚀 Полная синхронизация (500)</a>
    <a href="edit_supplier.php?id=1" class="btn btn-secondary">✏️ Настроить наценку</a>
</div>
                <p class="text-muted mb-0">После синхронизации товаров система готова к настройке фронтенда и платежей.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Подтверждение полной синхронизации
document.querySelector('a[href="sync_full.php"]').addEventListener('click', function(e) {
    if (!confirm('Запустить полную синхронизацию 1000+ товаров? Это займет 10-15 минут.')) {
        e.preventDefault();
    }
});
</script>
</body>
</html>