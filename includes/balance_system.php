<?php
// /includes/balance_system.php - Система работы с балансом

class BalanceSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Получить баланс пользователя
     */
    public function getUserBalance($user_id) {
        $stmt = $this->pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? floatval($result['balance']) : 0.00;
    }
    
    /**
     * Списание с баланса (покупка товара)
     */
    public function makePurchase($user_id, $amount, $product_id, $product_name, $customer_email = '', $customer_telegram = '') {
        $this->pdo->beginTransaction();
        
        try {
            // 1. Проверяем баланс
            $balance = $this->getUserBalance($user_id);
            
            if ($balance < $amount) {
                throw new Exception("Недостаточно средств на балансе. Доступно: {$balance} ₽, требуется: {$amount} ₽");
            }
            
            // 2. Получаем товар
            $stmt = $this->pdo->prepare("SELECT * FROM supplier_products WHERE id = ? AND stock > 0");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Товар не найден или нет в наличии");
            }
            
            // 3. Создаем заказ
            $order_number = 'GS' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
            $login_data = 'user_' . strtoupper(substr(md5(uniqid()), 0, 8));
            $password_data = 'pass_' . strtoupper(substr(md5(uniqid()), 0, 10));
            
            // Получаем email пользователя если не передан
            if (empty($customer_email)) {
                $user_stmt = $this->pdo->prepare("SELECT email FROM users WHERE id = ?");
                $user_stmt->execute([$user_id]);
                $user = $user_stmt->fetch();
                $customer_email = $user['email'];
            }
            
            $sql = "
                INSERT INTO orders (
                    user_id, order_number, product_id, product_name,
                    customer_email, customer_telegram, total_amount, 
                    login_data, password_data, status, payment_status, 
                    payment_method, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', 'paid', 'balance', ?)
            ";
            
            $notes = "Оплата с баланса. Пользователь ID: {$user_id}";
            if (!empty($customer_telegram)) {
                $notes .= ", Telegram: {$customer_telegram}";
            }
            
            $order_stmt = $this->pdo->prepare($sql);
            $order_stmt->execute([
                $user_id,
                $order_number,
                $product_id,
                $product_name,
                $customer_email,
                $customer_telegram,
                $amount,
                $login_data,
                $password_data,
                $notes
            ]);
            
            $order_id = $this->pdo->lastInsertId();
            
            // 4. Списание с баланса
            $this->pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")
                ->execute([$amount, $user_id]);
            
            // 5. Запись транзакции
            $txn_sql = "
                INSERT INTO transactions (
                    user_id, type, amount, description, status,
                    payment_system, transaction_id, related_order_id
                ) VALUES (?, 'purchase', ?, ?, 'completed', 'balance', ?, ?)
            ";
            
            $txn_id = 'BAL_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
            $description = "Покупка: {$product_name} (ID: {$product_id})";
            
            $txn_stmt = $this->pdo->prepare($txn_sql);
            $txn_stmt->execute([
                $user_id,
                $amount,
                $description,
                $txn_id,
                $order_id
            ]);
            
            // 6. Уменьшаем остаток товара
            $this->pdo->prepare("UPDATE supplier_products SET stock = stock - 1 WHERE id = ?")
                ->execute([$product_id]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'login' => $login_data,
                'password' => $password_data,
                'balance_left' => $balance - $amount
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Быстрая покупка с регистрацией по email
     */
    public function quickPurchase($email, $telegram, $product_id, $product_name, $amount) {
        $this->pdo->beginTransaction();
        
        try {
            // 1. Ищем или создаем пользователя
            $stmt = $this->pdo->prepare("SELECT id, balance FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $user_id = $user['id'];
                $balance = floatval($user['balance']);
                
                // Обновляем телеграм если нужно
                if (!empty($telegram)) {
                    $this->pdo->prepare("UPDATE users SET telegram = ? WHERE id = ?")
                        ->execute([$telegram, $user_id]);
                }
            } else {
                // Создаем нового пользователя без пароля (быстрая регистрация)
                $username = 'user_' . strtoupper(substr(md5(uniqid()), 0, 8));
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, telegram, password, balance, created_at)
                    VALUES (?, ?, ?, '', 0, NOW())
                ");
                $stmt->execute([$username, $email, $telegram]);
                
                $user_id = $this->pdo->lastInsertId();
                $balance = 0;
            }
            
            // 2. Проверяем баланс
            if ($balance < $amount) {
                throw new Exception("Недостаточно средств на балансе. Пополните баланс в личном кабинете.");
            }
            
            // 3. Совершаем покупку
            return $this->makePurchase($user_id, $amount, $product_id, $product_name, $email, $telegram);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>