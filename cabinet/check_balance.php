<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/balance_system.php';

header('Content-Type: application/json');

try {
    if (isset($_SESSION['user_id'])) {
        $balanceSystem = new BalanceSystem();
        $balance = $balanceSystem->getUserBalance($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'balance' => $balance,
            'user_id' => $_SESSION['user_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'balance' => 0,
            'message' => 'Пользователь не авторизован'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'balance' => 0,
        'message' => $e->getMessage()
    ]);
}
?>