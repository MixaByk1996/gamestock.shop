<?php
// /www/gamestock.shop/fix_test_accounts.php
session_start();
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Исправление тестовых аккаунтов</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>";

try {
    $pdo = getDBConnection();
    
    echo "<h2>Исправление тестовых аккаунтов</h2>";
    
    // Удаляем старые аккаунты с неправильными логинами
    $stmt = $pdo->query("DELETE FROM test_accounts");
    $deleted = $stmt->rowCount();
    echo "<div class='alert alert-info'>Удалено старых аккаунтов: $deleted</div>";
    
    // Создаем новые аккаунты с правильными логинами
    $test_accounts = [
        // Email аккаунты
        ['product_type' => 'email', 'product_subtype' => 'gmail', 'login' => 'gmail_account_test1', 'password' => 'GmailTest123!'],
        ['product_type' => 'email', 'product_subtype' => 'gmail', 'login' => 'gmail_account_test2', 'password' => 'GmailTest456!'],
        ['product_type' => 'email', 'product_subtype' => 'gmail', 'login' => 'gmail_account_test3', 'password' => 'GmailTest789!'],
        ['product_type' => 'email', 'product_subtype' => 'outlook', 'login' => 'outlook_test_account1', 'password' => 'OutlookTest123!'],
        ['product_type' => 'email', 'product_subtype' => 'hotmail', 'login' => 'hotmail_test_account1', 'password' => 'HotmailTest123!'],
        ['product_type' => 'email', 'product_subtype' => 'yahoo', 'login' => 'yahoo_test_account1', 'password' => 'YahooTest123!'],
        ['product_type' => 'email', 'product_subtype' => 'proton', 'login' => 'proton_test_account1', 'password' => 'ProtonTest123!'],
        
        // Социальные сети
        ['product_type' => 'social', 'product_subtype' => 'instagram', 'login' => 'instagram_test_01', 'password' => 'InstaTest123!'],
        ['product_type' => 'social', 'product_subtype' => 'instagram', 'login' => 'instagram_test_02', 'password' => 'InstaTest456!'],
        ['product_type' => 'social', 'product_subtype' => 'instagram', 'login' => 'instagram_test_03', 'password' => 'InstaTest789!'],
        ['product_type' => 'social', 'product_subtype' => 'facebook', 'login' => 'facebook_test_01', 'password' => 'FbTest123!'],
        ['product_type' => 'social', 'product_subtype' => 'facebook', 'login' => 'facebook_test_02', 'password' => 'FbTest456!'],
        ['product_type' => 'social', 'product_subtype' => 'twitter', 'login' => 'twitter_test_01', 'password' => 'TwitterTest123!'],
        ['product_type' => 'social', 'product_subtype' => 'twitter', 'login' => 'twitter_test_02', 'password' => 'TwitterTest456!'],
        ['product_type' => 'social', 'product_subtype' => 'tiktok', 'login' => 'tiktok_test_01', 'password' => 'TikTokTest123!'],
        ['product_type' => 'social', 'product_subtype' => 'tiktok', 'login' => 'tiktok_test_02', 'password' => 'TikTokTest456!'],
        
        // Игровые аккаунты
        ['product_type' => 'game', 'product_subtype' => 'steam', 'login' => 'steam_test_01', 'password' => 'SteamTest123!'],
        ['product_type' => 'game', 'product_subtype' => 'steam', 'login' => 'steam_test_02', 'password' => 'SteamTest456!'],
        ['product_type' => 'game', 'product_subtype' => 'epic', 'login' => 'epic_test_01', 'password' => 'EpicTest123!'],
        ['product_type' => 'game', 'product_subtype' => 'origin', 'login' => 'origin_test_01', 'password' => 'OriginTest123!'],
        ['product_type' => 'game', 'product_subtype' => 'battlenet', 'login' => 'bnet_test_01', 'password' => 'BnetTest123!'],
        
        // Общие аккаунты (для любых категорий)
        ['product_type' => 'general', 'product_subtype' => '', 'login' => 'universal_acc_001', 'password' => 'Universal123!'],
        ['product_type' => 'general', 'product_subtype' => '', 'login' => 'universal_acc_002', 'password' => 'Universal456!'],
        ['product_type' => 'general', 'product_subtype' => '', 'login' => 'universal_acc_003', 'password' => 'Universal789!'],
        ['product_type' => 'general', 'product_subtype' => '', 'login' => 'universal_acc_004', 'password' => 'Universal012!'],
        ['product_type' => 'general', 'product_subtype' => '', 'login' => 'universal_acc_005', 'password' => 'Universal345!'],
        
        // СМС аккаунты
        ['product_type' => 'sms', 'product_subtype' => '', 'login' => 'sms_acc_001', 'password' => 'SmsTest123!'],
        ['product_type' => 'sms', 'product_subtype' => '', 'login' => 'sms_acc_002', 'password' => 'SmsTest456!'],
        ['product_type' => 'sms', 'product_subtype' => '', 'login' => 'sms_acc_003', 'password' => 'SmsTest789!'],
        
        // Почтовые рассылки
        ['product_type' => 'mailing', 'product_subtype' => '', 'login' => 'mailing_acc_001', 'password' => 'Mailing123!'],
        ['product_type' => 'mailing', 'product_subtype' => '', 'login' => 'mailing_acc_002', 'password' => 'Mailing456!'],
    ];
    
    // Добавляем аккаунты в базу
    $added = 0;
    $stmt = $pdo->prepare("INSERT INTO test_accounts (product_type, product_subtype, login, password) VALUES (?, ?, ?, ?)");
    
    foreach ($test_accounts as $account) {
        try {
            $stmt->execute([
                $account['product_type'],
                $account['product_subtype'],
                $account['login'],
                $account['password']
            ]);
            $added++;
            
            echo "<div class='alert alert-success'>
                    ✅ {$account['login']} | {$account['password']} | {$account['product_type']}" . 
                    ($account['product_subtype'] ? "/{$account['product_subtype']}" : "") .
                 "</div>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>
                    ❌ Ошибка: {$account['login']} - " . $e->getMessage() .
                 "</div>";
        }
    }
    
    echo "<div class='alert alert-success mt-3'>
            <h4>✅ Готово! Добавлено $added тестовых аккаунтов с правильными логинами</h4>
            <p>Все логины теперь содержат только: латинские буквы, цифры и подчеркивания</p>
        </div>";
        
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>