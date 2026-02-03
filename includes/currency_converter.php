<?php
// /www/gamestock.shop/includes/currency_converter.php

class CurrencyConverter {
    private $conn;
    private $rates_cache = [];
    
    public function __construct() {
        $this->conn = getDBConnection();
    }
    
    /**
     * Конвертирует цену из валюты поставщика в рубли
     */
    public function convertToRub($price, $supplier_id) {
        if (!$supplier_id || $price <= 0) {
            return $price;
        }
        
        $rate_data = $this->getSupplierRate($supplier_id);
        
        // Если конвертация отключена или валюта уже рубли
        if (!$rate_data['is_active'] || $rate_data['currency_code'] == 'RUB') {
            return $price;
        }
        
        // Конвертируем цену
        return round($price * $rate_data['rate_to_rub'], 2);
    }
    
    /**
     * Получает курс для поставщика
     */
    public function getSupplierRate($supplier_id) {
        if (isset($this->rates_cache[$supplier_id])) {
            return $this->rates_cache[$supplier_id];
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT rate_to_rub, is_active, currency_code 
                FROM supplier_currency_rates 
                WHERE supplier_id = ?
            ");
            $stmt->execute([$supplier_id]);
            $rate_data = $stmt->fetch();
            
            if ($rate_data) {
                $this->rates_cache[$supplier_id] = $rate_data;
                return $rate_data;
            }
        } catch (PDOException $e) {
            error_log("CurrencyConverter error: " . $e->getMessage());
        }
        
        // Если нет настроенного курса, проверяем валюту поставщика
        try {
            $stmt = $this->conn->prepare("SELECT currency_code FROM suppliers WHERE id = ?");
            $stmt->execute([$supplier_id]);
            $supplier = $stmt->fetch();
            
            $currency_code = $supplier['currency_code'] ?? 'RUB';
            
            // Возвращаем значения по умолчанию
            return [
                'rate_to_rub' => $this->getDefaultRate($currency_code),
                'is_active' => false,
                'currency_code' => $currency_code
            ];
        } catch (Exception $e) {
            return [
                'rate_to_rub' => 80.45,
                'is_active' => false,
                'currency_code' => 'USD'
            ];
        }
    }
    
    /**
     * Получает курс по умолчанию для валюты
     */
    private function getDefaultRate($currency_code) {
        $rates = [
            'USD' => 80.45,
            'EUR' => 90.12,
            'RUB' => 1.00
        ];
        
        return $rates[$currency_code] ?? 80.45;
    }
    
    /**
     * Устанавливает или обновляет курс для поставщика
     * и пересчитывает все его товары
     */
    public function setSupplierRate($supplier_id, $rate, $is_active = true, $currency_code = 'USD') {
        try {
            // Сохраняем курс
            $stmt = $this->conn->prepare("
                INSERT INTO supplier_currency_rates 
                (supplier_id, rate_to_rub, is_active, currency_code) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                rate_to_rub = VALUES(rate_to_rub),
                is_active = VALUES(is_active),
                currency_code = VALUES(currency_code)
            ");
            
            $stmt->execute([$supplier_id, $rate, $is_active ? 1 : 0, $currency_code]);
            
            // Обновляем валюту у поставщика
            $stmt = $this->conn->prepare("UPDATE suppliers SET currency_code = ? WHERE id = ?");
            $stmt->execute([$currency_code, $supplier_id]);
            
            // Очищаем кеш
            unset($this->rates_cache[$supplier_id]);
            
            // Пересчитываем все товары этого поставщика
            if ($is_active && $currency_code != 'RUB') {
                $this->recalculateSupplierProducts($supplier_id, $rate, $currency_code);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("CurrencyConverter setRate error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Пересчитывает все товары поставщика при изменении курса
     */
    private function recalculateSupplierProducts($supplier_id, $rate, $currency_code) {
        try {
            // Получаем все товары поставщика
            $stmt = $this->conn->prepare("
                SELECT id, original_price 
                FROM supplier_products 
                WHERE supplier_id = ?
            ");
            $stmt->execute([$supplier_id]);
            $products = $stmt->fetchAll();
            
            // Обновляем каждый товар
            $update_stmt = $this->conn->prepare("
                UPDATE supplier_products 
                SET converted_price = ?,
                    price = ?,
                    currency_code = ?
                WHERE id = ?
            ");
            
            $updated_count = 0;
            foreach ($products as $product) {
                $original_price = $product['original_price'] > 0 ? $product['original_price'] : $product['price'];
                $converted_price = round($original_price * $rate, 2);
                
                $update_stmt->execute([
                    $converted_price,
                    $converted_price,
                    $currency_code,
                    $product['id']
                ]);
                $updated_count++;
            }
            
            return $updated_count;
            
        } catch (PDOException $e) {
            error_log("Ошибка при пересчете товаров: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Получает список всех курсов
     */
    public function getAllRates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT scr.*, s.name as supplier_name, s.currency_code as supplier_default_currency
                FROM supplier_currency_rates scr
                LEFT JOIN suppliers s ON scr.supplier_id = s.id
                ORDER BY s.name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("CurrencyConverter getAllRates error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получает список поставщиков без настроенных курсов
     */
    public function getSuppliersWithoutRates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT s.id, s.name, s.currency_code
                FROM suppliers s
                LEFT JOIN supplier_currency_rates scr ON s.id = scr.supplier_id
                WHERE scr.id IS NULL
                ORDER BY s.name
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("CurrencyConverter getSuppliersWithoutRates error: " . $e->getMessage());
            return [];
        }
    }
}
?>