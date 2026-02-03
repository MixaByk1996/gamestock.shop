<?php
// test_yoomarket_connection.php

// Подключаем автозагрузку или сам класс
require_once 'includes/ApiSuppliers/YoOMarket.php';

$api_token = "ec30c61ad20f54313c9c20f1048debfae951f4cfee9219032792ccb76ad24d8e";

// Проверяем существование класса
if (!class_exists('YoOMarket')) {
    die("❌ Класс YoOMarket не найден. Проверьте путь: includes/ApiSuppliers/YoOMarket.php");
}

$yoomarket = new YoOMarket($api_token);

echo "<h2>Тестирование подключения к YoOMarket</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>";

// Тест подключения
echo "<h3>1. Тест подключения:</h3>";
$test = $yoomarket->testConnection();
echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd;'>";
print_r($test);
echo "</pre>";

if ($test['success']) {
    echo "<div style='color: green; font-weight: bold;'>✅ " . $test['message'] . "</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ " . $test['message'] . "</div>";
}

// Тест получения товаров
echo "<h3>2. Тест получения товаров (первые 3):</h3>";
$goods = $yoomarket->getGoods('rub', ['per_page' => 3]);
echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd;'>";
print_r($goods);
echo "</pre>";

// Тест баланса
echo "<h3>3. Тест получения баланса:</h3>";
$balance = $yoomarket->getBalance();
echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd;'>";
print_r($balance);
echo "</pre>";

echo "</div>";

// Дополнительная информация
echo "<hr>";
echo "<h4>Информация о запросе:</h4>";
if (isset($goods['http_code'])) {
    echo "HTTP код: " . $goods['http_code'] . "<br>";
}
if (isset($goods['error'])) {
    echo "Ошибка cURL: " . $goods['error'] . "<br>";
}
?>