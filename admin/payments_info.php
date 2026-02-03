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
    <title>Настройка Lava</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <span class="navbar-brand">💰 Настройка платежей Lava</span>
        <a href="index.php" class="btn btn-outline-light">← Назад в админку</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-info text-white">
            <h4>Ожидаем API ключи от заказчика</h4>
        </div>
        <div class="card-body">
            <p>Платежная система <strong>Lava</strong> полностью готова к подключению!</p>
            
            <div class="alert alert-warning">
                <h5>📋 Что нужно от заказчика:</h5>
                <ol>
                    <li>Зарегистрироваться на <a href="https://lava.ru" target="_blank">Lava.ru</a></li>
                    <li>В личном кабинете Lava получить:
                        <ul>
                            <li><strong>Shop ID</strong> (ID магазина)</li>
                            <li><strong>Secret Key</strong> (Секретный ключ API)</li>
                        </ul>
                    </li>
                    <li>Прислать оба ключа для настройки</li>
                </ol>
            </div>
            
            <div class="alert alert-success">
                <h5>⚡ Что будет после получения ключей:</h5>
                <ul>
                    <li>Автоматически заработает прием платежей</li>
                    <li>Появятся способы оплаты: карты, QIWI, ЮMoney, СБП</li>
                    <li>Автоподтверждение заказов после оплаты</li>
                    <li>Статистика платежей в админке</li>
                </ul>
                <p><strong>Время настройки:</strong> 15-20 минут</p>
            </div>
            
            <p class="text-muted">После получения ключей эта страница превратится в панель управления платежами.</p>
        </div>
    </div>
</div>
</body>
</html>