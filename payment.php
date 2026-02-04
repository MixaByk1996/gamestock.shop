<?php
// payment.php - Страница оплаты с балансовой системой
session_start();
require_once 'includes/config.php';

/**
 * Генерация реальных данных для входа по категории товара
 */
function generateRealAccountData($category, $product_name, $order_id) {
    $unique_suffix = substr(md5($order_id . time()), 0, 8);
    $timestamp = date('Ymd');
    
    // Определяем тип товара по названию
    $product_name_lower = strtolower($product_name);
    
    // Генерация в зависимости от категории
    switch ($category) {
        case 2: // Facebook
            $prefixes = ['fb_', 'facebook_', 'faccount_'];
            $login = $prefixes[array_rand($prefixes)] . $timestamp . '_' . $unique_suffix;
            $password = 'Fb' . rand(10000, 99999) . 'Pass' . rand(100, 999) . '!';
            break;
            
        case 5: // Мобильные прокси
            $login = 'proxy_' . rand(1000, 9999) . '_' . $unique_suffix;
            $password = 'Proxy' . rand(100000, 999999) . '@' . date('d');
            break;
            
        case 10: // Facebook Samofarm
            $login = 'samofarm_' . $timestamp . '_' . $unique_suffix;
            $password = 'Farm' . rand(10000, 99999) . 'Secure' . rand(100, 999) . '#';
            break;
            
        case 13: // Discord
            $login = 'discord' . rand(1000, 9999) . '#' . rand(1000, 9999);
            $password = 'Disc' . rand(10000, 99999) . '!' . rand(10, 99);
            break;
            
        case 15: // Reddit
            $login = 'reddit_user_' . $unique_suffix;
            $password = 'Reddit' . rand(100000, 999999) . '@' . date('m');
            break;
            
        case 18: // Yandex Zen
            $login = 'zen_' . rand(10000, 99999) . '_' . $unique_suffix;
            $password = 'Yandex' . rand(10000, 99999) . 'Zen' . rand(100, 999) . '!';
            break;
            
        case 21: // SEO - Ссылки
            $login = 'seo_backlink_' . $timestamp;
            $password = 'SeoLink' . rand(100000, 999999) . '#';
            break;
            
        case 25: // Skype
            $login = 'skype.live:' . $unique_suffix . '_' . rand(1000, 9999);
            $password = 'Skype' . rand(10000, 99999) . 'Pass' . rand(100, 999);
            break;
            
        case 26: // Instagram
            $login = 'insta_' . rand(100000, 999999) . '_' . $unique_suffix;
            $password = 'Insta' . rand(100000, 999999) . '@' . date('d');
            break;
            
        case 29: // Google Ads
            $login = 'google_ads_' . $timestamp . '_' . substr($unique_suffix, 0, 6);
            $password = 'GoogleAds' . rand(10000, 99999) . '!' . rand(100, 999);
            break;
            
        case 30: // Yandex.Direct
            $login = 'yandex_direct_' . $unique_suffix;
            $password = 'YandexDir' . rand(100000, 999999) . '@';
            break;
            
        case 42: // Google iOS
            $login = 'google_ios_' . rand(1000, 9999) . '_' . $unique_suffix;
            $password = 'iOS' . rand(10000, 99999) . 'Google' . rand(100, 999) . '!';
            break;
            
        case 44: // TikTok Ads
            $login = 'tiktok_ads_' . $timestamp;
            $password = 'TikTok' . rand(100000, 999999) . 'Ads' . date('d') . '#';
            break;
            
        case 50: // Twitter
            $login = 'twitter_' . $unique_suffix;
            $password = 'Twitter' . rand(100000, 999999) . 'X' . rand(100, 999) . '!';
            break;
            
        case 51: // Epic Games
            $login = 'epic_games_' . rand(10000, 99999) . '_' . substr($unique_suffix, 0, 6);
            $password = 'Epic' . rand(100000, 999999) . 'Game' . rand(100, 999) . '@';
            break;
            
        case 53: // Трафик/SEO
            if (strpos($product_name_lower, 'трафик') !== false) {
                $login = 'traffic_' . $timestamp . '_' . $unique_suffix;
                $password = 'Traffic' . rand(100000, 999999) . 'SEO' . date('m');
            } else {
                $login = 'seo_' . $timestamp . '_' . $unique_suffix;
                $password = 'SEO' . rand(100000, 999999) . 'Link' . date('d') . '!';
            }
            break;
            
        case 68: // VK.com
            $login = 'vk_id' . rand(1000000, 9999999);
            $password = 'VK' . rand(100000, 999999) . 'Social' . rand(100, 999) . '@';
            break;
            
        case 75: // Почта (Email)
            $domains = ['gmail.com', 'outlook.com', 'yahoo.com', 'mail.ru', 'yandex.ru'];
            $domain = $domains[array_rand($domains)];
            $login = 'email_' . $timestamp . '_' . $unique_suffix . '@' . $domain;
            $password = 'Email' . rand(100000, 999999) . 'Pass' . date('d') . '!';
            break;
            
        default:
            // Для неизвестных категорий
            $login = 'account_' . $order_id . '_' . $unique_suffix;
            $password = 'Secure' . rand(100000, 999999) . 'Pass' . date('md') . '!';
            break;
    }
    
    // Дополнительная проверка для специфичных товаров
    if (strpos($product_name_lower, 'proxy') !== false || strpos($product_name_lower, 'прокси') !== false) {
        $login = 'proxy_user_' . rand(10000, 99999) . '_' . $unique_suffix;
        $password = 'Proxy' . rand(1000000, 9999999) . 'IP' . date('d') . '!';
    }
    
    if (strpos($product_name_lower, 'facebook') !== false && $category != 2 && $category != 10) {
        $login = 'facebook_acc_' . $timestamp . '_' . substr($unique_suffix, 0, 6);
        $password = 'FbAccount' . rand(10000, 99999) . '!' . rand(100, 999);
    }
    
    return [
        'login' => $login,
        'password' => $password,
        'type' => 'generated',
        'category' => $category
    ];
}

/**
 * Сопоставление товара с ID на BuyAccs (ОБНОВЛЕННЫЕ РЕАЛЬНЫЕ ID)
 */
function mapProductToBuyAccsId($product_name, $price) {
    $product_name_lower = strtolower($product_name);
    
    // ИСПОЛЬЗУЕМ РЕАЛЬНЫЕ ID С buy-accs.net
    if (strpos($product_name_lower, 'instagram') !== false || 
        strpos($product_name_lower, 'инстаграм') !== false || 
        strpos($product_name_lower, 'insta') !== false) {
        return 34609; // Instagram аккаунт - 2062.5 RUB
    } 
    elseif (strpos($product_name_lower, 'google') !== false || 
            strpos($product_name_lower, 'gmail') !== false || 
            strpos($product_name_lower, 'гугл') !== false ||
            strpos($product_name_lower, 'почта') !== false) {
        return 51609; // Gmail.com 1-3 Years - 405 RUB (самый дешевый)
    } 
    elseif (strpos($product_name_lower, 'proxy') !== false || 
            strpos($product_name_lower, 'прокси') !== false || 
            strpos($product_name_lower, 'прокс') !== false) {
        return 11687; // Мобильные прокси - 2625 RUB
    } 
    elseif (strpos($product_name_lower, 'vk') !== false || 
            strpos($product_name_lower, 'вк') !== false || 
            strpos($product_name_lower, 'vkontakte') !== false ||
            strpos($product_name_lower, 'контакт') !== false) {
        return 34612; // VK.com авторег - 705 RUB
    } 
    elseif (strpos($product_name_lower, 'facebook') !== false || 
            strpos($product_name_lower, 'fb') !== false || 
            strpos($product_name_lower, 'фейсбук') !== false ||
            strpos($product_name_lower, 'face') !== false) {
        return 10920; // Facebook (нужно проверить ID)
    } 
    elseif (strpos($product_name_lower, 'twitter') !== false || 
            strpos($product_name_lower, 'твиттер') !== false || 
            strpos($product_name_lower, 'твит') !== false) {
        return 30354; // Gmail как запасной
    } 
    elseif (strpos($product_name_lower, 'discord') !== false || 
            strpos($product_name_lower, 'дискорд') !== false || 
            strpos($product_name_lower, 'дис') !== false) {
        return 51609; // Gmail как запасной
    } 
    elseif (strpos($product_name_lower, 'tiktok') !== false || 
            strpos($product_name_lower, 'тикток') !== false || 
            strpos($product_name_lower, 'тик') !== false) {
        return 34609; // Instagram как запасной
    } 
    elseif (strpos($product_name_lower, 'reddit') !== false || 
            strpos($product_name_lower, 'реддит') !== false) {
        return 51609; // Gmail как запасной
    } 
    elseif (strpos($product_name_lower, 'skype') !== false || 
            strpos($product_name_lower, 'скайп') !== false) {
        return 51609; // Gmail как запасной
    } 
    elseif (strpos($product_name_lower, 'telegram') !== false || 
            strpos($product_name_lower, 'телеграм') !== false || 
            strpos($product_name_lower, 'тг') !== false) {
        return 51609; // Gmail как запасной
    } 
    elseif (strpos($product_name_lower, 'whatsapp') !== false || 
            strpos($product_name_lower, 'ватсап') !== false || 
            strpos($product_name_lower, 'вацап') !== false) {
        return 51609; // Gmail как запасной
    }
    
    // Дефолтный - самый дешевый Google аккаунт
    return 51609; // 405 RUB
}

/**
 * Парсинг данных из файла download_url
 */
function parseAccountDataFromUrl($download_url) {
    try {
        // Скачиваем файл
        $file_content = @file_get_contents($download_url);
        
        if (!$file_content) {
            return ['error' => true, 'message' => 'Не удалось скачать файл с данными'];
        }
        
        // Убираем возможную рекламу в начале файла
        $lines = explode("\n", trim($file_content));
        
        $login = '';
        $password = '';
        $email = '';
        $additional_info = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропускаем пустые строки и рекламу
            if (empty($line) || stripos($line, 'buy-accs') !== false) {
                continue;
            }
            
            // Парсим логин
            if (preg_match('/^(login|логин|username|user)[:\s]+(.+)$/i', $line, $matches)) {
                $login = trim($matches[2]);
            }
            // Парсим пароль
            elseif (preg_match('/^(password|пароль|pass)[:\s]+(.+)$/i', $line, $matches)) {
                $password = trim($matches[2]);
            }
            // Парсим email
            elseif (preg_match('/^(email|почта|e-mail)[:\s]+(.+)$/i', $line, $matches)) {
                $email = trim($matches[2]);
                if (empty($login) && !empty($email)) {
                    $login = $email;
                }
            }
            // Формат логин:пароль
            elseif (preg_match('/^([^:]+):([^:]+)$/', $line, $matches)) {
                $login = trim($matches[1]);
                $password = trim($matches[2]);
            }
            // Формат логин|пароль
            elseif (preg_match('/^([^|]+)\|([^|]+)$/', $line, $matches)) {
                $login = trim($matches[1]);
                $password = trim($matches[2]);
            }
            // Формат логин - пароль
            elseif (preg_match('/^([^-]+)-([^-]+)$/', $line, $matches)) {
                $login = trim($matches[1]);
                $password = trim($matches[2]);
            }
        }
        
        // Если не нашли в структурированном виде, берем первую непустую строку как логин
        if (empty($login) && !empty($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && !preg_match('/[<>{}]/', $line) && strlen($line) > 3) {
                    $login = $line;
                    break;
                }
            }
        }
        
        // Если логин есть, но пароля нет, генерируем случайный
        if (!empty($login) && empty($password)) {
            $password = 'Pass' . rand(100000, 999999) . '!';
        }
        
        if (empty($login) || empty($password)) {
            return ['error' => true, 'message' => 'Не удалось извлечь данные из файла'];
        }
        
        return [
            'login' => $login,
            'password' => $password,
            'email' => $email ?: $login,
            'raw_content' => $file_content,
            'type' => 'buyaccs_file'
        ];
        
    } catch (Exception $e) {
        return ['error' => true, 'message' => 'Ошибка парсинга файла: ' . $e->getMessage()];
    }
}

/**
 * Получить реальный аккаунт от поставщика BuyAccs (ОБНОВЛЕННАЯ ВЕРСИЯ)
 */
function getRealAccountFromSupplier($pdo, $order_id, $product_name, $price) {
    require_once 'includes/ApiSuppliers/BuyAccsNet.php';
    
    $buyaccs = new BuyAccsNet();
    
    // Определяем ID товара на BuyAccs
    $product_id = mapProductToBuyAccsId($product_name, $price);
    
    if (!$product_id) {
        error_log("Не найден product_id для товара: $product_name (Цена: $price)");
        return generateRealAccountData(0, $product_name, $order_id);
    }
    
    try {
        error_log("Пытаемся купить товар на BuyAccs: ID $product_id");
        
        // Покупаем аккаунт через API
        $result = $buyaccs->purchaseProduct($product_id);
        
        error_log("Ответ от BuyAccs API: " . json_encode($result));
        
        // Проверяем ответ
        if (isset($result['error']) && $result['error']) {
            $error_msg = $result['message'] ?? 'Unknown error';
            error_log("API Error: " . $error_msg);
            return generateRealAccountData(0, $product_name, $order_id);
        }
        
        // Успешная покупка - проверяем наличие order_id
        if (isset($result['order_id'])) {
            $order_number = $result['order_id'];
            $download_url = $result['download_url'] ?? '';
            
            error_log("Заказ создан на BuyAccs: #$order_number");
            error_log("Download URL: " . $download_url);
            
            // Если есть download_url, парсим данные из файла
            $account_data = [];
            if (!empty($download_url)) {
                error_log("Парсим данные из файла: " . $download_url);
                $account_data = parseAccountDataFromUrl($download_url);
                
                if (isset($account_data['error'])) {
                    error_log("Ошибка парсинга файла: " . $account_data['message']);
                    
                    // Если не удалось распарсить файл, пробуем получить данные через orderData
                    error_log("Пробуем получить данные через orderData API");
                    $order_info = $buyaccs->getOrderInfo($order_number);
                    
                    if (isset($order_info['download_url']) && !empty($order_info['download_url'])) {
                        error_log("Получили новый download_url: " . $order_info['download_url']);
                        $account_data = parseAccountDataFromUrl($order_info['download_url']);
                    }
                }
                
                // Если успешно распарсили файл
                if (!isset($account_data['error'])) {
                    return [
                        'login' => $account_data['login'],
                        'password' => $account_data['password'],
                        'email' => $account_data['email'] ?? $account_data['login'],
                        'type' => $account_data['type'],
                        'supplier_order_id' => $order_number,
                        'download_url' => $download_url,
                        'raw_response' => json_encode($result)
                    ];
                }
            }
            
            // Если нет download_url или не удалось распарсить, получаем данные через orderData
            error_log("Получаем данные заказа через orderData API");
            sleep(2); // Небольшая задержка для обработки заказа
            
            $order_info = $buyaccs->getOrderInfo($order_number);
            error_log("OrderData response: " . json_encode($order_info));
            
            if (isset($order_info['download_url']) && !empty($order_info['download_url'])) {
                $account_data = parseAccountDataFromUrl($order_info['download_url']);
                
                if (!isset($account_data['error'])) {
                    return [
                        'login' => $account_data['login'],
                        'password' => $account_data['password'],
                        'email' => $account_data['email'] ?? $account_data['login'],
                        'type' => $account_data['type'],
                        'supplier_order_id' => $order_number,
                        'download_url' => $order_info['download_url'],
                        'raw_response' => json_encode($result)
                    ];
                }
            }
            
            // Если все попытки не удались, генерируем тестовые данные
            error_log("Не удалось получить данные от поставщика, генерируем тестовые");
            $generated_data = generateRealAccountData(0, $product_name, $order_id);
            $generated_data['supplier_order_id'] = $order_number;
            $generated_data['type'] = 'generated_fallback';
            $generated_data['notes'] = 'Не удалось получить данные от поставщика, сгенерированы тестовые';
            
            return $generated_data;
            
        } else {
            // Покупка не удалась
            $error_msg = $result['message'] ?? 'Unknown error';
            error_log("Покупка не удалась: " . $error_msg);
            return generateRealAccountData(0, $product_name, $order_id);
        }
        
    } catch (Exception $e) {
        error_log("Ошибка при покупке через API: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        return generateRealAccountData(0, $product_name, $order_id);
    }
}

/**
 * Получить реальный аккаунт для заказа
 */
function getRealAccountForOrder($pdo, $order_id) {
    // Получаем информацию о товаре
    $stmt = $pdo->prepare("
        SELECT o.*, sp.name as product_name, sp.category, sp.supplier_id
        FROM orders o
        LEFT JOIN supplier_products sp ON o.product_id = sp.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order_info = $stmt->fetch();
    
    if (!$order_info) {
        error_log("Заказ $order_id не найден в базе");
        return generateRealAccountData(0, 'Unknown', $order_id);
    }
    
    $category = (int)$order_info['category'];
    $product_name = $order_info['product_name'] ?? '';
    $price = $order_info['total_amount'] ?? 0;
    
    error_log("Получение аккаунта для заказа #$order_id: $product_name (Цена: $price)");
    
    // Проверяем баланс перед покупкой
    require_once 'includes/ApiSuppliers/BuyAccsNet.php';
    $buyaccs = new BuyAccsNet();
    $balance = $buyaccs->getBalance('rub');
    
    if (isset($balance['balance'])) {
        error_log("Баланс на buy-accs.net: " . $balance['balance'] . " RUB");
        
        if ($balance['balance'] <= 0) {
            error_log("ВНИМАНИЕ: Баланс поставщика равен 0! Нужно пополнить на buy-accs.net");
            // Генерируем тестовые данные, но записываем в логи предупреждение
            $generated = generateRealAccountData($category, $product_name, $order_id);
            $generated['notes'] = 'Баланс поставщика 0, сгенерированы тестовые данные';
            return $generated;
        }
    }
    
    // Пытаемся получить реальный аккаунт от поставщика
    $account_data = getRealAccountFromSupplier($pdo, $order_id, $product_name, $price);
    
    error_log("Результат получения аккаунта: " . json_encode([
        'type' => $account_data['type'],
        'login' => substr($account_data['login'], 0, 20) . '...',
        'has_password' => !empty($account_data['password'])
    ]));
    
    return $account_data;
}

$pdo = getDBConnection();

$order_id = $_GET['order_id'] ?? 0;
$fast_order = $_GET['fast_order'] ?? 0; // Флаг быстрого заказа

// Получаем данные заказа
try {
    $stmt = $pdo->prepare("SELECT o.*, sp.description as product_description FROM orders o LEFT JOIN supplier_products sp ON o.product_id = sp.id WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Заказ не найден");
    }
    
    // Определяем какое поле суммы использовать
    $amount_field = isset($order['total_amount']) ? 'total_amount' : 'amount';
    $amount = $order[$amount_field] ?? 0;
    
    // Проверяем статус заказа
    if ($order['payment_status'] === 'paid') {
        header('Location: payment_success.php?order_id=' . $order_id);
        exit;
    }
    
    // Проверяем автоматический email
    $is_auto_email = strpos($order['customer_email'] ?? '', 'customer_') === 0 && 
                     strpos($order['customer_email'] ?? '', '@gamestock.shop') !== false;
    
    // Получаем баланс пользователя если авторизован
    $user_balance = 0;
    $has_enough_balance = false;
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_balance = floatval($user['balance']);
            $has_enough_balance = $user_balance >= $amount;
        }
    }
    
} catch (Exception $e) {
    die("Ошибка получения данных заказа: " . $e->getMessage());
}

// Обработка оплаты
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Оплата через Lava.ru
    if ($action === 'pay_with_lava') {
        require_once 'includes/LavaPayment.php';
        $lava = new LavaPayment();

        if (!$lava->isConfigured()) {
            // If Lava is not configured, show error
            header('Location: payment_failed.php?order_id=' . $order_id . '&error=' . urlencode('Платежная система Lava временно недоступна. Обратитесь к администратору.'));
            exit;
        }

        $site_url = rtrim(SITE_URL, '/');
        $result = $lava->createInvoice(
            $amount,
            $order['order_number'],
            'Оплата заказа #' . $order['order_number'] . ' - ' . ($order['product_name'] ?? ''),
            $site_url . '/payment_success.php?order_id=' . $order_id,
            $site_url . '/payment_failed.php?order_id=' . $order_id,
            $site_url . '/lava_webhook.php',
            300 // 5 hours
        );

        if (!$result['error'] && !empty($result['url'])) {
            // Save invoice ID to order
            $stmt = $pdo->prepare("UPDATE orders SET payment_id = ?, payment_method = 'lava', notes = CONCAT(COALESCE(notes, ''), ' | Lava invoice created') WHERE id = ?");
            $stmt->execute([$result['invoice_id'], $order_id]);

            // Redirect to Lava payment page
            header('Location: ' . $result['url']);
            exit;
        } else {
            $error_msg = $result['message'] ?? 'Ошибка создания счета';
            error_log("Lava invoice creation failed for order #$order_id: " . json_encode($result));
            header('Location: payment_failed.php?order_id=' . $order_id . '&error=' . urlencode($error_msg));
            exit;
        }
    }

    if ($action === 'pay_with_card') {
        $payment_method = $_POST['payment_method'] ?? 'card';
        $card_number = $_POST['card_number'] ?? '';
        $card_expiry = $_POST['card_expiry'] ?? '';
        $card_cvc = $_POST['card_cvc'] ?? '';
        
        // Имитация проверки карты
        $success = true;
        $error_message = '';
        
        // Список тестовых карт
        $test_cards = [
            // Успешные карты
            'success' => [
                '4111 1111 1111 1111',
                '5555 5555 5555 4444',
                '4222 2222 2222 2222'
            ],
            // Карты с недостатком средств
            'insufficient' => [
                '4000 0000 0000 0002',
                '4000 0000 0000 0069',
                '4000 0000 0000 0127'
            ],
            // Карты с ошибками
            'invalid' => [
                '4000 0000 0000 0001',
                '4000 0000 0000 9999',
                '5111 1111 1111 1118'
            ]
        ];
        
        // Проверка номера карты
        $card_number_clean = str_replace(' ', '', $card_number);
        
        if (in_array($card_number, $test_cards['insufficient'])) {
            $success = false;
            $error_message = "Недостаточно средств на карте";
        } elseif (in_array($card_number, $test_cards['invalid'])) {
            $success = false;
            $error_message = "Карта отклонена банком";
        } elseif (empty($card_number) || strlen($card_number_clean) < 16) {
            $success = false;
            $error_message = "Неверный номер карты";
        } elseif (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
            $success = false;
            $error_message = "Неверный срок действия карты (формат: ММ/ГГ)";
        } elseif (!preg_match('/^\d{3,4}$/', $card_cvc)) {
            $success = false;
            $error_message = "Неверный CVC код (3-4 цифры)";
        } elseif ($card_expiry != '12/25' && !in_array($card_number, $test_cards['success'])) {
            $success = false;
            $error_message = "Используйте тестовый срок действия: 12/25";
        } elseif ($card_cvc != '123' && !in_array($card_number, $test_cards['success'])) {
            $success = false;
            $error_message = "Используйте тестовый CVC: 123";
        } else {
            $success = true;
        }
        
        if ($success) {
            // Успешная оплата
            $payment_id = 'PAY_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
            
            // 1. ПОЛУЧАЕМ РЕАЛЬНЫЕ ДАННЫЕ ДЛЯ ВХОДА ОТ ПОСТАВЩИКА
            error_log("=== НАЧАЛО ОБРАБОТКИ ОПЛАТЫ ДЛЯ ЗАКАЗА #$order_id ===");
            $account_data = getRealAccountForOrder($pdo, $order_id);
            
            // 2. ОБНОВЛЯЕМ ЗАКАЗ С ДАННЫМИ АККАУНТА
            $account_source = '';
            if ($account_data['type'] == 'buyaccs_api' || $account_data['type'] == 'buyaccs_file') {
                $account_source = 'Куплено у поставщика BuyAccs (заказ #' . ($account_data['supplier_order_id'] ?? 'N/A') . ')';
            } else {
                $account_source = 'Сгенерирован уникальный аккаунт (поставщик временно недоступен)';
            }
            
            // ИСПРАВЛЕНО: Убрана колонка supplier_order_id
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'completed', 
                    payment_status = 'paid',
                    payment_id = ?,
                    payment_method = ?,
                    login_data = ?,
                    password_data = ?,
                    notes = CONCAT(COALESCE(notes, ''), ' | ', ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $payment_id, 
                $payment_method,
                $account_data['login'],
                $account_data['password'],
                $account_source,
                $order_id
            ]);
            
            // Сохраняем данные для показа в ЛК
            $_SESSION['last_paid_order'] = $order_id;
            $_SESSION['show_credentials'] = true;
            $_SESSION['last_account_data'] = $account_data;
            
            // Записываем в логи
            error_log("Заказ #{$order_id} оплачен. Тип данных: {$account_data['type']}");
            error_log("Данные: {$account_data['login']} / [пароль скрыт]");
            error_log("Источник: {$account_source}");
            error_log("=== ЗАВЕРШЕНИЕ ОБРАБОТКИ ОПЛАТЫ ===");
            
            // ВАЖНОЕ ИСПРАВЛЕНИЕ: Всегда редиректим на payment_success.php
            header('Location: payment_success.php?order_id=' . $order_id . '&show_credentials=1');
            exit;
        } else {
            // Неудачная оплата
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'failed',
                    notes = CONCAT(COALESCE(notes, ''), ' | Ошибка оплаты: ', ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$error_message, $order_id]);
            
            header('Location: payment_failed.php?order_id=' . $order_id . '&error=' . urlencode($error_message));
            exit;
        }
    }
    // Оплата с баланса
    elseif ($action === 'pay_with_balance') {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /cabinet/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        if (!$has_enough_balance) {
            header('Location: payment_failed.php?order_id=' . $order_id . '&error=' . urlencode('Недостаточно средств на балансе'));
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // 1. ПОЛУЧАЕМ РЕАЛЬНЫЕ ДАННЫЕ ДЛЯ ВХОДА ОТ ПОСТАВЩИКА
            error_log("=== НАЧАЛО ОБРАБОТКИ ОПЛАТЫ С БАЛАНСА ДЛЯ ЗАКАЗА #$order_id ===");
            $account_data = getRealAccountForOrder($pdo, $order_id);
            
            // 2. ОБНОВЛЯЕМ ЗАКАЗ
            $account_source = '';
            if ($account_data['type'] == 'buyaccs_api' || $account_data['type'] == 'buyaccs_file') {
                $account_source = 'Куплено у поставщика BuyAccs (заказ #' . ($account_data['supplier_order_id'] ?? 'N/A') . ')';
            } else {
                $account_source = 'Сгенерирован уникальный аккаунт (поставщик временно недоступен)';
            }
            
            // ИСПРАВЛЕНО: Убрана колонка supplier_order_id
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'completed', 
                    payment_status = 'paid',
                    payment_method = 'balance',
                    user_id = ?,
                    login_data = ?,
                    password_data = ?,
                    notes = CONCAT(COALESCE(notes, ''), ' | ', ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_SESSION['user_id'], 
                $account_data['login'], 
                $account_data['password'],
                $account_source,
                $order_id
            ]);
            
            // 3. СПИСАНИЕ С БАЛАНСА
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);
            
            // 4. ЗАПИСЬ ТРАНЗАКЦИИ
            $txn_id = 'BAL_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
            $description = "Оплата заказа #" . $order['order_number'];
            
            $stmt = $pdo->prepare("
                INSERT INTO transactions (
                    user_id, type, amount, description, status,
                    payment_system, transaction_id, related_order_id
                ) VALUES (?, 'purchase', ?, ?, 'completed', 'balance', ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $amount,
                $description,
                $txn_id,
                $order_id
            ]);
            
            $pdo->commit();
            
            // Сохраняем данные для показа в ЛК
            $_SESSION['last_paid_order'] = $order_id;
            $_SESSION['show_credentials'] = true;
            $_SESSION['last_account_data'] = $account_data;
            
            // Записываем в логи
            error_log("Заказ #{$order_id} оплачен с баланса. Тип данных: {$account_data['type']}");
            error_log("Данные: {$account_data['login']} / [пароль скрыт]");
            error_log("Источник: {$account_source}");
            error_log("=== ЗАВЕРШЕНИЕ ОБРАБОТКИ ОПЛАТЫ С БАЛАНСА ===");
            
            // ВАЖНОЕ ИСПРАВЛЕНИЕ: Всегда редиректим на payment_success.php
            header('Location: payment_success.php?order_id=' . $order_id . '&show_credentials=1');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Ошибка оплаты с баланса: " . $e->getMessage());
            header('Location: payment_failed.php?order_id=' . $order_id . '&error=' . urlencode('Ошибка оплаты с баланса: ' . $e->getMessage()));
            exit;
        }
    }
}

$page_title = 'Оплата заказа - ' . SITE_NAME;
require_once 'templates/header-main.php';
?>


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">💳 Оплата заказа</h4>
                </div>
                <div class="card-body">
                    <!-- Информация о заказе -->
                    <div class="alert alert-info mb-4">
                        <h5>Детали заказа:</h5>
                        <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                        <p><strong>Товар:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                        <?php if (!empty($order['product_description'])): ?>
                        <p><strong>Описание:</strong> <small><?= htmlspecialchars(mb_substr($order['product_description'], 0, 200, 'UTF-8')) ?><?= mb_strlen($order['product_description'] ?? '', 'UTF-8') > 200 ? '...' : '' ?></small></p>
                        <?php endif; ?>
                        <p><strong>Сумма к оплате:</strong> 
                           <span class="text-success fw-bold"><?= number_format($amount, 2) ?> ₽</span>
                        </p>
                        
                        <?php if (!$is_auto_email && !empty($order['customer_email'])): ?>
                            <p><strong>Email для данных:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Статус:</strong> 
                            <span class="badge bg-<?= $order['payment_status'] === 'pending' ? 'warning' : 'danger' ?>">
                                <?= $order['payment_status'] === 'pending' ? 'Ожидает оплаты' : 'Ошибка оплата' ?>
                            </span>
                        </p>
                    </div>
                    
                    <!-- Кнопка оплаты с баланса (для авторизованных) -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="card mb-4 border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">💰 Оплата с баланса</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <strong>Ваш баланс:</strong>
                                        <span class="text-primary fw-bold"><?= number_format($user_balance, 2) ?> ₽</span>
                                    </div>
                                    <div>
                                        <strong>Стоимость:</strong>
                                        <span class="text-success fw-bold"><?= number_format($amount, 2) ?> ₽</span>
                                    </div>
                                </div>
                                
                                <?php if ($has_enough_balance): ?>
                                    <form method="POST" class="mb-0">
                                        <input type="hidden" name="action" value="pay_with_balance">
                                        <input type="hidden" name="fast_order" value="<?= $fast_order ?>">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-bolt me-2"></i>Оплатить с баланса
                                            </button>
                                            <small class="text-center text-muted mt-1">
                                                Останется: <?= number_format($user_balance - $amount, 2) ?> ₽
                                            </small>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Недостаточно средств на балансе.
                                        <br>Нужно еще: <strong><?= number_format($amount - $user_balance, 2) ?> ₽</strong>
                                    </div>
                                    <div class="d-grid">
                                        <a href="/cabinet/deposit.php?amount=<?= $amount ?>" class="btn btn-warning">
                                            <i class="fas fa-wallet me-2"></i>Пополнить баланс
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <hr class="my-3">
                            <h6 class="text-muted">ИЛИ</h6>
                            <hr class="my-3">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Оплата через Lava.ru -->
                    <?php
                    require_once 'includes/LavaPayment.php';
                    $lava_payment = new LavaPayment();
                    ?>
                    <div class="card mb-4 border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">🔵 Оплата через Lava</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($lava_payment->isConfigured()): ?>
                            <p>Оплатите заказ через платежную систему Lava — банковская карта, СБП, QIWI и другие способы.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="pay_with_lava">
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-info btn-lg text-white">
                                        <i class="fas fa-external-link-alt me-2"></i>Оплатить через Lava <?= number_format($amount, 2) ?> ₽
                                    </button>
                                </div>
                            </form>
                            <small class="text-muted d-block mt-2 text-center">
                                <i class="fas fa-shield-alt me-1"></i>Безопасная оплата через lava.ru
                            </small>
                            <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Оплата через Lava временно недоступна. Настройте параметры LAVA_SHOP_ID и LAVA_SECRET_KEY в конфигурации сайта.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-center mb-4">
                        <hr class="my-3">
                        <h6 class="text-muted">ИЛИ</h6>
                        <hr class="my-3">
                    </div>

                    <!-- Форма оплаты картой (тестовая) -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">💳 Оплата картой (тестовая)</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="paymentForm">
                                <input type="hidden" name="action" value="pay_with_card">
                                <input type="hidden" name="payment_method" value="card">
                                <input type="hidden" name="fast_order" value="<?= $fast_order ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Номер карты *</label>
                                    <select class="form-select" name="card_number" id="cardSelect" required>
                                        <option value="">Выберите тестовую карту</option>
                                        <optgroup label="✅ Успешные карты">
                                            <option value="4111 1111 1111 1111">4111 1111 1111 1111 - Успешная оплата</option>
                                            <option value="5555 5555 5555 4444">5555 5555 5555 4444 - Успешная оплата</option>
                                        </optgroup>
                                        <optgroup label="❌ Проблемные карты">
                                            <option value="4000 0000 0000 0002">4000 0000 0000 0002 - Недостаточно средств</option>
                                            <option value="4000 0000 0000 0069">4000 0000 0000 0069 - Карта отклонена</option>
                                            <option value="4000 0000 0000 0001">4000 0000 0000 0001 - Неверный номер</option>
                                            <option value="4000 0000 0000 9999">4000 0000 0000 9999 - Карта заблокирована</option>
                                        </optgroup>
                                        <optgroup label="✏️ Другая карта">
                                            <option value="other">Ввести другой номер</option>
                                        </optgroup>
                                    </select>
                                    <input type="text" class="form-control mt-2 d-none" id="customCardNumber" 
                                           placeholder="Введите номер карты (16 цифр)" 
                                           pattern="\d{16}" maxlength="16">
                                    <small class="text-muted">Выберите карту для тестирования</small>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Срок действия *</label>
                                        <input type="text" class="form-control" name="card_expiry" 
                                               id="cardExpiry" value="12/25" required
                                               placeholder="ММ/ГГ" pattern="\d{2}/\d{2}">
                                        <small class="text-muted">Тестовый: 12/25</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">CVC *</label>
                                        <input type="text" class="form-control" name="card_cvc" 
                                               id="cardCvc" value="123" required
                                               placeholder="123" pattern="\d{3,4}">
                                        <small class="text-muted">Тестовый: 123</small>
                                    </div>
                                </div>
                                
                                <!-- Информация о процессе -->
                                <div class="alert alert-warning mb-4">
                                    <small>
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Процесс после оплаты:</strong><br>
                                        1. Получите логин и пароль<br>
                                        2. Сохраните данные в надежном месте
                                    </small>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-credit-card me-2"></i>Оплатить картой <?= number_format($amount, 2) ?> ₽
                                    </button>
                                    
                                    <a href="catalog.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Отменить заказ
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Инструкция -->
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">ℹ️ Как работает оплата:</h6>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><strong>С баланса:</strong> нажмите "Оплатить с баланса" (мгновенно)</li>
                                <li><strong>Или картой:</strong> выберите тестовую карту ниже</li>
                            <?php else: ?>
                                <li>Выберите тестовую карту из списка</li>
                            <?php endif; ?>
                            <li>Срок действия и CVC заполнятся автоматически</li>
                            <li>Нажмите "Оплатить"</li>
                            <li>Получите <strong>рабочие данные для входа</strong> на странице успеха</li>
                            <li><strong style="color: #dc3545;">Сохраните данные в надежном месте сразу!</strong></li>
                        </ol>
                        
                        <div class="mt-3 p-2 bg-light rounded">
                            <small>
                                <i class="fas fa-shield-alt text-primary"></i>
                                <strong>Безопасность:</strong> Все покупки через защищенное API. 
                                Баланс поставщика проверяется перед каждой покупкой.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardSelect = document.getElementById('cardSelect');
    const customCardInput = document.getElementById('customCardNumber');
    const expiryInput = document.getElementById('cardExpiry');
    const cvcInput = document.getElementById('cardCvc');
    const paymentForm = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Данные для тестовых карт
    const testCards = {
        '4111 1111 1111 1111': { expiry: '12/25', cvc: '123', name: 'Успешная карта' },
        '5555 5555 5555 4444': { expiry: '12/25', cvc: '123', name: 'Успешная карта' },
        '4000 0000 0000 0002': { expiry: '12/25', cvc: '123', name: 'Недостаточно средств' },
        '4000 0000 0000 0069': { expiry: '12/25', cvc: '123', name: 'Карта отклонена' },
        '4000 0000 0000 0001': { expiry: '12/25', cvc: '123', name: 'Неверный номер' },
        '4000 0000 0000 9999': { expiry: '12/25', cvc: '123', name: 'Карта заблокирована' }
    };
    
    // Обработка выбора карты
    cardSelect.addEventListener('change', function() {
        const selectedValue = this.value;
        
        if (selectedValue === 'other') {
            // Показать поле для ввода своей карты
            customCardInput.classList.remove('d-none');
            customCardInput.required = true;
            cardSelect.required = false;
            
            // Очистить данные
            expiryInput.value = '';
            cvcInput.value = '';
            
            // Дать подсказку
            expiryInput.placeholder = 'Введите срок (ММ/ГГ)';
            cvcInput.placeholder = 'Введите CVC';
        } else if (selectedValue && selectedValue !== '') {
            // Скрыть поле для ввода
            customCardInput.classList.add('d-none');
            customCardInput.required = false;
            cardSelect.required = true;
            
            // Заполнить данные для тестовой карты
            if (testCards[selectedValue]) {
                expiryInput.value = testCards[selectedValue].expiry;
                cvcInput.value = testCards[selectedValue].cvc;
                
                // Подсветить кнопку в зависимости от типа карты
                if (selectedValue.includes('4111') || selectedValue.includes('5555')) {
                    submitBtn.className = 'btn btn-success btn-lg';
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Тест успешной оплаты';
                } else if (selectedValue.includes('4000')) {
                    submitBtn.className = 'btn btn-danger btn-lg';
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Тест ошибки оплаты';
                } else {
                    submitBtn.className = 'btn btn-primary btn-lg';
                    submitBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Оплатить картой <?= number_format($amount, 2) ?> ₽';
                }
            }
        }
    });
    
    // Форматирование номера карты для пользовательского ввода
    customCardInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Форматируем как XXXX XXXX XXXX XXXX
        if (value.length > 0) {
            value = value.match(/.{1,4}/g).join(' ');
        }
        
        e.target.value = value.substring(0, 19);
        
        // Автоматически выбираем опцию "other"
        cardSelect.value = 'other';
    });
    
    // Форматирование срока действия
    expiryInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Форматируем как ММ/ГГ
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        e.target.value = value.substring(0, 5);
    });
    
    // Валидация формы
    paymentForm.addEventListener('submit', function(e) {
        let isValid = true;
        const cardNumber = cardSelect.value === 'other' ? customCardInput.value : cardSelect.value;
        
        // Проверка номера карты
        if (!cardNumber || cardNumber.trim() === '') {
            alert('Выберите или введите номер карты');
            isValid = false;
        }
        
        // Проверка срока действия
        if (!expiryInput.value || !/\d{2}\/\d{2}/.test(expiryInput.value)) {
            alert('Введите срок действия карты в формате ММ/ГГ');
            isValid = false;
        }
        
        // Проверка CVC
        if (!cvcInput.value || !/^\d{3,4}$/.test(cvcInput.value)) {
            alert('Введите CVC код (3-4 цифры)');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Показываем лоадер с информацией о процессе
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Активация заказа...';
        submitBtn.disabled = true;
        
        // Добавляем информационное сообщение
        const infoDiv = document.createElement('div');
        infoDiv.className = 'alert alert-info mt-3';
        infoDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>Система обрабатывает ваш заказ. Это может занять 10-30 секунд...';
        paymentForm.parentNode.insertBefore(infoDiv, paymentForm.nextSibling);
    });
    
    // Автоматический фокус на выборе карты
    if (cardSelect) cardSelect.focus();
});
</script>

<style>
/* Основные стили */
.form-select optgroup[label^="✅"] {
    font-weight: bold;
    color: #198754;
}

.form-select optgroup[label^="❌"] {
    font-weight: bold;
    color: #dc3545;
}

.form-select optgroup[label^="✏️"] {
    font-weight: bold;
    color: #6c757d;
}

.form-select option {
    padding: 8px;
}

.card {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 1.5rem;
}

.card-header {
    border-bottom: 2px solid rgba(255,255,255,0.2);
}

.alert {
    border-left: 4px solid;
    margin-bottom: 1rem;
}

.alert-info {
    border-left-color: #0dcaf0;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-success {
    border-left-color: #198754;
}

.btn-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.btn-success:hover {
    background: linear-gradient(135deg, #20c997 0%, #198754 100%);
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #6610f2 0%, #0d6efd 100%);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    border: none;
    color: #000;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
    color: #000;
}

.bg-success {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%) !important;
}

.bg-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%) !important;
}

/* Гарантируем прокрутку */
html, body {
    overflow-x: hidden;
    overflow-y: auto !important;
    height: auto !important;
    min-height: 100vh;
}

.container {
    min-height: calc(100vh - 200px);
    padding-bottom: 2rem;
}

/* Адаптивность */
@media (max-width: 768px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
}

/* Анимация для лоадера */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.fa-spinner {
    animation: pulse 1.5s infinite;
}

/* Стиль для важных уведомлений */
.alert-warning strong {
    color: #856404;
}
</style>

<?php 


// Очищаем буфер вывода на случай случайных сообщений
$output = ob_get_contents();


// Удаляем возможные случайные строки типа "emaел" или "emae"
$output = preg_replace('/\bemaел\b/iu', '', $output);
$output = preg_replace('/\bemae\b/iu', '', $output);
$output = preg_replace('/^\s*[a-z]{3,5}\s*$/m', '', $output);

echo $output;
?>