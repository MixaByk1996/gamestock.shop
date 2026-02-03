<?php
// save_product.php - Сохранение изменений товара
session_start();
require_once '../../includes/config.php';

// Проверка авторизации
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$pdo = getDBConnection();

// Получаем данные
$id = $_POST['product_id'] ?? 0;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$our_price = $_POST['our_price'] ?? 0;
$category = $_POST['category'] ?? 0;
$stock = $_POST['stock'] ?? 0;
$external_id = $_POST['external_id'] ?? '';

// Валидация
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Название не может быть пустым']);
    exit;
}

if (empty($external_id)) {
    echo json_encode(['success' => false, 'message' => 'Внешний ID обязателен']);
    exit;
}

try {
    // Проверяем, существует ли товар
    $stmt = $pdo->prepare("SELECT id FROM supplier_products WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        // Обновляем существующий товар
        $stmt = $pdo->prepare("
            UPDATE supplier_products 
            SET name = ?, 
                price = ?, 
                our_price = ?,
                category = ?,
                stock = ?,
                external_id = ?,
                description = ?,
                last_updated = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$name, $price, $our_price, $category, $stock, $external_id, $description, $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Товар успешно обновлен'
        ]);
    } else {
        // Создаем новый товар
        $stmt = $pdo->prepare("
            INSERT INTO supplier_products 
            (name, price, our_price, category, stock, external_id, supplier_id, currency_code, last_updated,description)
            VALUES (?, ?, ?, ?, ?, ?, 1, 'RUB', NOW())
        ");
        
        $stmt->execute([$name, $price, $our_price, $category, $stock, $external_id, $description]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Товар успешно добавлен',
            'new_id' => $pdo->lastInsertId()
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>