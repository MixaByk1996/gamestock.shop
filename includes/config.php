<?php
// /www/gamestock.shop/includes/config.php

// ============ БЕЗОПАСНЫЙ КОНФИГУРАЦИОННЫЙ ФАЙЛ ============

//// Настройки базы данных
//define('DB_HOST', 'localhost');
//define('DB_NAME', 'u3377233_default');
//define('DB_USER', 'u3377233_default');
//define('DB_PASS', 'IhgUaZ9tXP97I2k6');

define('DB_HOST', 'localhost');
define('DB_NAME', 'shop');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Настройки сайта
define('SITE_URL', 'http://localhost:8000/');
define('SITE_NAME', 'GameStock Shop');
define('ADMIN_EMAIL', 'admin@gamestock.shop');

// Пути к файлам
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('TEMPLATES_PATH', ROOT_PATH . 'templates' . DIRECTORY_SEPARATOR);

// Настройки безопасности
define('SITE_KEY', 'gamestock_' . md5(__FILE__ . DB_PASS));
define('CSRF_TOKEN_NAME', 'csrf_token');

// ============ API НАСТРОЙКИ ============

// BuyAccs.net API (главный поставщик)
define('BUYACCS_API_KEY', 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a');
define('BUYACCS_API_URL', 'https://buy-accs.net/api/');
define('BUYACCS_API_LIMIT', 100); // лимит запросов: 100 в 5 минут
define('BUYACCS_DEFAULT_CURRENCY', 'rub');

// Резервные поставщики (можно добавить позже)
// define('YOOMARKET_API_TOKEN', 'ваш_токен');
// define('KINGUIN_API_KEY', 'ваш_ключ');

// Lava.ru Payment System (https://lava.ru/)
// Get credentials from https://lava.ru/ business dashboard
define('LAVA_SHOP_ID', ''); // UUID проекта из кабинета Lava
define('LAVA_SECRET_KEY', ''); // Секретный ключ для подписи запросов
define('LAVA_WEBHOOK_KEY', ''); // Дополнительный ключ для проверки подписи в хуках

// Общие настройки API
define('API_TIMEOUT', 30);
define('API_USER_AGENT', 'GameStock-Shop/1.0');
define('MAX_API_RETRIES', 3);
define('API_REQUEST_DELAY', 1); // задержка между запросами в секундах

// Настройки кэширования
define('CACHE_ENABLED', true);
define('CACHE_TTL_PRODUCTS', 1800); // 30 минут для товаров
define('CACHE_TTL_CATEGORIES', 3600); // 1 час для категорий
define('MAX_LOG_SIZE', 10485760); // 10MB

// Режим отладки
define('DEBUG_MODE', true);

// ============ БЕЗОПАСНОСТЬ ============

// Предотвращаем прямой доступ к файлу
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    die('Прямой доступ запрещен.');
}

// ============ НАСТРОЙКИ ОШИБОК ============

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/php_errors.log');
}

// Создаем папку для логов если нет
if (!is_dir(ROOT_PATH . 'logs')) {
    mkdir(ROOT_PATH . 'logs', 0755, true);
}

// Создаем папку для кэша если нет
if (CACHE_ENABLED && !is_dir(ROOT_PATH . 'cache')) {
    mkdir(ROOT_PATH . 'cache', 0755, true);
}

// ============ СЕССИЯ ============

// Проверяем, запущена ли уже сессия
$session_active = (session_status() === PHP_SESSION_ACTIVE);

if (!$session_active) {
    // Безопасные настройки сессии (ТОЛЬКО если сессия еще не запущена)
    
    // Регенерация ID сессии для безопасности
    if (empty($_SESSION['created'])) {
       } elseif (time() - $_SESSION['created'] > 1800) { // 30 минут
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Сессия уже запущена, просто обновляем время создания если нужно
    if (empty($_SESSION['created'])) {
        $_SESSION['created'] = time();
    }
}

// ============ ЗАГОЛОВКИ БЕЗОПАСНОСТИ ============

// Отправляем заголовки только если они еще не отправлены
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    if (!DEBUG_MODE && isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Скрываем версию PHP
    header_remove('X-Powered-By');
}

// ============ ФУНКЦИИ БАЗЫ ДАННЫХ ============

/**
 * Подключение к базе данных с кэшированием
 * @return PDO
 */
function getDBConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Устанавливаем часовой пояс
            $connection->exec("SET time_zone = '+03:00'");
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                die("Ошибка подключения к базе данных: " . htmlspecialchars($e->getMessage()));
            } else {
                die("Временные технические неполадки. Пожалуйста, попробуйте позже.");
            }
        }
    }
    
    return $connection;
}

/**
 * Безопасный запрос к базе данных
 * @param string $sql SQL запрос
 * @param array $params Параметры для подстановки
 * @return PDOStatement|false
 */
function dbQuery($sql, $params = []) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
        
        if (DEBUG_MODE) {
            throw $e;
        }
        return false;
    }
}

/**
 * Вставка данных с возвратом ID
 * @param string $table Таблица
 * @param array $data Данные для вставки
 * @return int|false ID вставленной записи
 */
function dbInsert($table, $data) {
    if (empty($data)) return false;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Database insert error: " . $e->getMessage());
        return false;
    }
}

// ============ ФУНКЦИИ ДЛЯ РАБОТЫ С API ============

/**
 * Получить баланс от поставщика
 * @param string $currency Валюта
 * @return array|false
 */
function getSupplierBalance($currency = 'rub') {
    $api_key = BUYACCS_API_KEY;
    $url = BUYACCS_API_URL . "balance?api_key=" . $api_key . "&currency=" . $currency;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => API_TIMEOUT,
        CURLOPT_USERAGENT => API_USER_AGENT
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        return $data;
    }
    
    return false;
}

/**
 * Кэширование данных
 * @param string $key Ключ кэша
 * @param mixed $data Данные для кэширования
 * @param int $ttl Время жизни в секундах
 */
function cacheSet($key, $data, $ttl = 3600) {
    if (!CACHE_ENABLED) return false;
    
    $cache_file = ROOT_PATH . 'cache/' . md5($key) . '.cache';
    $cache_data = [
        'expires' => time() + $ttl,
        'data' => $data
    ];
    
    return file_put_contents($cache_file, serialize($cache_data));
}

/**
 * Получение данных из кэша
 * @param string $key Ключ кэша
 * @return mixed|false Данные или false если нет/истек
 */
function cacheGet($key) {
    if (!CACHE_ENABLED) return false;
    
    $cache_file = ROOT_PATH . 'cache/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) return false;
    
    $cache_data = unserialize(file_get_contents($cache_file));
    
    if ($cache_data && isset($cache_data['expires']) && $cache_data['expires'] > time()) {
        return $cache_data['data'];
    }
    
    // Удаляем просроченный кэш
    unlink($cache_file);
    return false;
}

// ============ ФУНКЦИИ БЕЗОПАСНОСТИ ============

/**
 * Защита от XSS
 * @param string $data Данные для очистки
 * @return string Очищенные данные
 */
function clean($data) {
    if (is_array($data)) {
        return array_map('clean', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

/**
 * Генерация CSRF токена
 * @return string CSRF токен
 */
function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Проверка CSRF токена
 * @param string $token Токен из формы
 * @return bool Валиден ли токен
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Хэширование пароля
 * @param string $password Пароль
 * @return string Хэш пароля
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Проверка пароля
 * @param string $password Пароль
 * @param string $hash Хэш из БД
 * @return bool Совпадает ли пароль
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ============ ФУНКЦИИ ПРОВЕРКИ ДОСТУПА ============

/**
 * Проверка админ-доступа с редиректом
 */
function checkAdminAccess() {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/');
        exit();
    }
}

/**
 * Проверка пользовательского доступа с редиректом
 */
function checkUserAccess() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /cabinet/');
        exit();
    }
}

/**
 * Проверка авторизации (без редиректа)
 * @return bool Авторизован ли пользователь
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Проверка админ-статуса (без редиректа)
 * @return bool Админ ли пользователь
 */
function isAdmin() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// ============ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ============

/**
 * Получить IP адрес пользователя
 * @return string IP адрес
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Форматирование цены
 * @param float $price Цена
 * @param string $currency Валюта
 * @return string Отформатированная цена
 */
function formatPrice($price, $currency = 'rub') {
    $symbols = [
        'rub' => '₽',
        'usd' => '$',
        'eur' => '€',
        'cny' => '¥'
    ];
    
    $symbol = $symbols[$currency] ?? '₽';
    
    if ($currency === 'rub') {
        return number_format($price, 0, '.', ' ') . ' ' . $symbol;
    } else {
        return $symbol . number_format($price, 2, '.', ' ');
    }
}

/**
 * Перенаправление с сообщением
 * @param string $url URL для редиректа
 * @param string $message Сообщение (будет передано через сессию)
 * @param string $type Тип сообщения (success, error, warning, info)
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    // Проверяем, не отправлены ли уже заголовки
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        // Если заголовки уже отправлены, используем JavaScript редирект
        echo '<script>window.location.href="' . htmlspecialchars($url) . '";</script>';
        exit();
    }
}

/**
 * Показать флеш-сообщение
 * @return string HTML флеш-сообщения или пустая строка
 */
function showFlashMessage() {
    if (empty($_SESSION['flash_message'])) {
        return '';
    }
    
    $message = $_SESSION['flash_message'];
    $type = $_SESSION['flash_type'] ?? 'info';
    
    $types = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $types[$type] ?? $types['info'];
    
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
}

/**
 * Логирование действий
 * @param string $action Действие
 * @param mixed $data Данные для логирования
 */
function logAction($action, $data = null) {
    $logFile = ROOT_PATH . 'logs/actions.log';
    $time = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userId = $_SESSION['user_id'] ?? 0;
    
    $logData = [
        'time' => $time,
        'ip' => $ip,
        'user_id' => $userId,
        'action' => $action,
        'data' => $data
    ];
    
    $logLine = json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// ============ ЗАГРУЗКА КЛАССОВ (автозагрузка) ============

spl_autoload_register(function ($class_name) {
    // Ищем класс в includes/
    $class_file = INCLUDES_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
        return;
    }
    
    // Ищем в подпапках includes/ApiSuppliers/
    $class_file = INCLUDES_PATH . 'ApiSuppliers/' . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
        return;
    }
});

// ============ ИНИЦИАЛИЗАЦИЯ ============

// Генерируем CSRF токен если нет
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    generateCsrfToken();
}

// Проверяем наличие обязательных констант
if (!defined('BUYACCS_API_KEY') || empty(BUYACCS_API_KEY)) {
    error_log('BUYACCS_API_KEY не задан в config.php');
    
    if (DEBUG_MODE) {
        die('Ошибка конфигурации: API ключ не задан');
    }
}

?>