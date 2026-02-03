<?php
// get_order_credentials.php - Получение данных заказа
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID заказа']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Получаем данные заказа
    $stmt = $pdo->prepare("
        SELECT login_data, password_data 
        FROM orders 
        WHERE id = ? AND status = 'completed' AND payment_status = 'paid'
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order && !empty($order['login_data']) && !empty($order['password_data'])) {
        echo json_encode([
            'success' => true,
            'login' => $order['login_data'],
            'password' => $order['password_data']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Данные не найдены']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>