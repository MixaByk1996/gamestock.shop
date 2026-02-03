<?php
require_once 'includes/config.php';
$pdo = getDBConnection();

// Категории и их типы аккаунтов
$account_types = [
    // Facebook категория 2
    ['type' => 'social', 'subtype' => 'facebook', 'count' => 10, 'prefix' => 'fb_'],
    // Instagram категория 26
    ['type' => 'social', 'subtype' => 'instagram', 'count' => 10, 'prefix' => 'insta_'],
    // Discord категория 13
    ['type' => 'social', 'subtype' => 'discord', 'count' => 10, 'prefix' => 'discord_'],
    // VK категория 68
    ['type' => 'social', 'subtype' => 'vk', 'count' => 10, 'prefix' => 'vk_'],
    // Proxy категория 5
    ['type' => 'proxy', 'subtype' => 'ipv4', 'count' => 20, 'prefix' => 'proxy_'],
    // Email категория 75
    ['type' => 'email', 'subtype' => 'gmail', 'count' => 10, 'prefix' => 'gmail_'],
    // SEO/Трафик категория 53
    ['type' => 'seo', 'subtype' => 'links', 'count' => 10, 'prefix' => 'seo_'],
];

echo "<h2>Создание реальных тестовых аккаунтов:</h2>";

$total_created = 0;

foreach ($account_types as $account_type) {
    echo "<h3>Создаем {$account_type['count']} аккаунтов типа: {$account_type['type']}/{$account_type['subtype']}</h3>";
    
    for ($i = 1; $i <= $account_type['count']; $i++) {
        $login = $account_type['prefix'] . date('Ymd') . '_' . $i . '_' . substr(md5(uniqid()), 0, 6);
        
        // Генерация надежных паролей
        $password = generateSecurePassword($account_type['type'], $i);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO test_accounts 
                (product_type, product_subtype, login, password, status, created_at) 
                VALUES (?, ?, ?, ?, 'available', NOW())
            ");
            
            $stmt->execute([
                $account_type['type'],
                $account_type['subtype'],
                $login,
                $password
            ]);
            
            echo "Создан: {$login} / {$password}<br>";
            $total_created++;
            
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h3>Всего создано: {$total_created} аккаунтов</h3>";

// Функция генерации надежных паролей
function generateSecurePassword($type, $index) {
    $special_chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $base_passwords = [
        'social' => [
            'SocialPass' . $index . rand(1000, 9999) . '!',
            'Secure' . ucfirst($type) . rand(10000, 99999) . '@',
            'Account' . date('md') . $index . '#',
        ],
        'proxy' => [
            'Proxy' . rand(100000, 999999) . 'Pass!',
            'IP_' . rand(1000, 9999) . '_' . rand(1000, 9999) . '@',
            'SecureProxy' . $index . date('d') . '!',
        ],
        'email' => [
            'EmailPass' . $index . rand(1000, 9999) . '!',
            'Gmail' . rand(100000, 999999) . 'Secure#',
            'MailAccount' . date('m') . $index . '@',
        ],
        'seo' => [
            'SeoLinks' . $index . rand(1000, 9999) . '!',
            'Traffic' . rand(10000, 99999) . 'Pass#',
            'Backlink' . date('d') . $index . '@',
        ]
    ];
    
    $type_passwords = $base_passwords[$type] ?? $base_passwords['social'];
    return $type_passwords[array_rand($type_passwords)];
}
?>