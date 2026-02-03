<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/balance_system.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['product_id'] ?? 0);
$product_name = $_POST['product_name'] ?? '';
$email = $_POST['email'] ?? '';
$telegram = $_POST['telegram'] ?? '';

try {
    $pdo = getDBConnection();
    $balanceSystem = new BalanceSystem();
    
    if ($action === 'buy_with_balance') {
        // Если пользователь авторизован
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Получаем цену товара
            $stmt = $pdo->prepare("SELECT our_price FROM supplier_products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Товар не найден");
            }
            
            $amount = floatval($product['our_price']);
            
            // Совершаем покупку
            $result = $balanceSystem->makePurchase($user_id, $amount, $product_id, $product_name);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'order_number' => $result['order_number'],
                    'login' => $result['login'],
                    'password' => $result['password'],
                    'balance_left' => $result['balance_left'],
                    'message' => 'Покупка успешна!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        }
        // Быстрая покупка по email
        elseif (!empty($email)) {
            // Получаем цену товара
            $stmt = $pdo->prepare("SELECT our_price FROM supplier_products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Товар не найден");
            }
            
            $amount = floatval($product['our_price']);
            
            // Быстрая покупка
            $result = $balanceSystem->quickPurchase($email, $telegram, $product_id, $product_name, $amount);
            
            if ($result['success']) {
                // Авторизуем пользователя
                $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $user_stmt->execute([$email]);
                $user = $user_stmt->fetch();
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                }
                
                echo json_encode([
                    'success' => true,
                    'order_number' => $result['order_number'],
                    'login' => $result['login'],
                    'password' => $result['password'],
                    'balance_left' => $result['balance_left'],
                    'message' => 'Покупка успешна!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Требуется авторизация или email'
            ]);
        }
    }
    else {
        echo json_encode([
            'success' => false,
            'message' => 'Неизвестное действие'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>