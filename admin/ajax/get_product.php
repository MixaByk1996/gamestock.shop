<?php
// get_product.php - Получение данных товара по ID
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

try {
    $stmt = $pdo->prepare("
        SELECT sp.*, s.name as supplier_name
        FROM supplier_products sp
        LEFT JOIN suppliers s ON sp.supplier_id = s.id
        WHERE sp.id = ?
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo json_encode([
            'success' => true,
            'product' => $product
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