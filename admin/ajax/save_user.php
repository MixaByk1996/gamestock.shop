<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

$pdo = getDBConnection();
$user_id = intval($_POST['user_id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$balance = floatval($_POST['balance'] ?? 0);
$is_admin = isset($_POST['is_admin']) ? 1 : 0;

try {
    if ($user_id > 0) {
        // Обновление существующего пользователя
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, balance = ?, is_admin = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$username, $email, $balance, $is_admin, $user_id]);
    } else {
        // Создание нового пользователя
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, balance, is_admin, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $email, $balance, $is_admin]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}