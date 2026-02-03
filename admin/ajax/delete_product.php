<?php
// delete_product.php - Удаление товара
session_start();
require_once '../../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$pdo = getDBConnection();

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID товара']);
    exit;
}

try {
    // Проверяем, есть ли заказы на этот товар
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_id = ?");
    $stmt->execute([$id]);
    $order_count = $stmt->fetchColumn();
    
    if ($order_count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Нельзя удалить товар, на который есть заказы'
        ]);
        exit;
    }
    
    // Удаляем товар
    $stmt = $pdo->prepare("DELETE FROM supplier_products WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Товар успешно удален'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Товар не найден'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>