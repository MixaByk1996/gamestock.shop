<?php
// test_buyaccs_key.php
require_once 'includes/ApiSuppliers/BuyAccsNet.php';

$api_key = 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a';

echo "<h2>Тестирование API ключа buy-accs.net</h2>";

try {
    $buyaccs = new BuyAccsNet($api_key);
    
    // Тест подключения
    echo "<h3>1. Тест подключения:</h3>";
    $test = $buyaccs->testConnection();
    echo $test['success'] ? 
        "<div style='color:green; font-weight:bold;'>✅ " . $test['message'] . "</div>" : 
        "<div style='color:red; font-weight:bold;'>❌ " . $test['message'] . "</div>";
    
    if ($test['success']) {
        // Проверка баланса
        echo "<h3>2. Проверка баланса (RUB):</h3>";
        $url = "https://buy-accs.net/api/balance?api_key=" . $api_key . "&currency=rub";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);
        
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        
        // Получение первых 5 товаров
        echo "<h3>3. Тест получения товаров (первые 5):</h3>";
        $goods = $buyaccs->getGoods('rub', ['limit' => 5]);
        echo "<pre>";
        print_r($goods);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red; font-weight:bold;'>❌ Ошибка: " . $e->getMessage() . "</div>";
}
?>