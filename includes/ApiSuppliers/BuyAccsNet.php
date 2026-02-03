<?php
// /includes/ApiSuppliers/BuyAccsNet.php
// Полностью переработан в соответствии с официальной документацией buy-accs.net
// Использует GET-запросы как рекомендовано в документации

class BuyAccsNet {
    private $api_key = 'm02j0xcsidjlbtlrilapw0hjjbmrzfm5e6e-fvvmkpnbl6hh2a';
    private $api_url = 'https://buy-accs.net/api/';
    private $language = 'ru'; // Русский язык по умолчанию
    private $currency = 'rub'; // Валюта по умолчанию
    
    /**
     * Купить товар
     * @param int $product_id ID товара у поставщика
     * @param int $quantity Количество (по умолчанию 1)
     * @param int $show_ad Показывать рекламу в файле (1-да, 0-нет)
     * @return array Ответ API
     */
    public function purchaseProduct($product_id, $quantity = 1, $show_ad = 1) {
        $url = $this->api_url . "buy";
        
        $params = [
            'api_key' => $this->api_key,
            'id' => $product_id,
            'count' => $quantity,
            'show_ad' => $show_ad,
            'language' => $this->language
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Получить баланс
     * @param string $currency Валюта (rub, usd, eur, cny)
     * @return array Ответ API с балансом
     */
    public function getBalance($currency = 'rub') {
        $url = $this->api_url . "balance";
        
        $params = [
            'api_key' => $this->api_key,
            'currency' => $currency,
            'language' => $this->language
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Получить информацию о заказе
     * @param int $order_id Номер заказа поставщика
     * @return array Ответ API с данными заказа
     */
    public function getOrderInfo($order_id) {
        $url = $this->api_url . "orderData";
        
        $params = [
            'api_key' => $this->api_key,
            'id' => $order_id,
            'language' => $this->language
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Получить список товаров с фильтрацией
     * @param array $filters Параметры фильтрации
     * @return array Ответ API с товарами
     */
    public function getProducts($filters = []) {
        $url = $this->api_url . "goods";
        
        // Базовые параметры
        $params = [
            'api_key' => $this->api_key,
            'currency' => $this->currency,
            'language' => $this->language,
            'limit' => 100 // Стандартный лимит
        ];
        
        // Добавляем фильтры если переданы
        if (!empty($filters)) {
            $params = array_merge($params, $filters);
        }
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Получить список категорий
     * @return array Ответ API с категориями
     */
    public function getCategories() {
        $url = $this->api_url . "categories";
        
        $params = [
            'api_key' => $this->api_key,
            'language' => $this->language
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Получить товар по ID
     * @param int|string $product_ids ID товаров через запятую
     * @return array Ответ API с товарами
     */
    public function getProductById($product_ids) {
        $url = $this->api_url . "goods";
        
        $params = [
            'api_key' => $this->api_key,
            'currency' => $this->currency,
            'language' => $this->language,
            'id' => $product_ids
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    /**
     * Поиск товаров по категории
     * @param int $category_id ID категории
     * @param int $limit Лимит товаров (10-1000)
     * @param string $sort Поле сортировки (id, title, price, count)
     * @param string $sort_direction Направление (ASC/DESC)
     * @return array Ответ API с товарами
     */
    public function getProductsByCategory($category_id, $limit = 100, $sort = 'id', $sort_direction = 'ASC') {
        $url = $this->api_url . "goods";
        
        $params = [
            'api_key' => $this->api_key,
            'currency' => $this->currency,
            'language' => $this->language,
            'category_id' => $category_id,
            'limit' => max(10, min(1000, $limit)), // Ограничение 10-1000
            'sort' => $sort,
            'sort-direction' => strtoupper($sort_direction)
        ];
        
        return $this->makeRequest($url, $params);
    }
    
    // ============ МЕТОДЫ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ ============
    
    /**
     * Метод для обратной совместимости со старым кодом синхронизации
     * Используется в sync_buyaccs.php и sync_full.php
     * @param string $currency Валюта
     * @param array $params Параметры запроса
     * @return array Ответ API с товарами в формате старого кода
     */
    public function getGoods($currency = 'rub', $params = []) {
        // Устанавливаем валюту
        $this->setCurrency($currency);
        
        // Вызываем новый метод
        $result = $this->getProducts($params);
        
        // Если есть ошибка - возвращаем как есть
        if (isset($result['error']) && $result['error']) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Ошибка API',
                'data' => []
            ];
        }
        
        // Преобразуем результат в формат старого кода
        return [
            'success' => true,
            'message' => 'Успешно',
            'data' => [
                'goods' => $result['goods'] ?? $result
            ]
        ];
    }
    
    /**
     * Метод для обратной совместимости
     * Используется в sync_buyaccs.php и sync_full.php
     * @param string $currency Валюта
     * @return array Результат теста в формате старого кода
     */
    public function testConnection($currency = 'rub') {
        $balance = $this->getBalance($currency);
        
        if (isset($balance['error'])) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . ($balance['message'] ?? 'Неизвестная ошибка')
            ];
        }
        
        // Получаем количество товаров для информации
        $products = $this->getProducts(['limit' => 1]);
        $total_in_api = 11007; // Примерное количество, можно получить из API
        
        return [
            'success' => true,
            'message' => '✅ Подключение успешно. Баланс: ' . 
                        ($balance['balance'] ?? 0) . ' ' . $currency,
            'balance' => $balance['balance'] ?? 0,
            'total_in_api' => $total_in_api
        ];
    }
    
    /**
     * Основной метод выполнения запроса к API
     * @param string $url Endpoint URL
     * @param array $params Параметры запроса
     * @return array Ответ API
     */
    private function makeRequest($url, $params = []) {
        // Формируем URL с GET-параметрами
        $query_string = http_build_query($params);
        $full_url = $url . '?' . $query_string;
        
        $ch = curl_init();
        
        // Настройки cURL для GET-запроса
        $options = [
            CURLOPT_URL => $full_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'GameStock-Shop/1.0',
            CURLOPT_HTTPGET => true, // Явно указываем GET метод
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: ' . $this->language
            ]
        ];
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Логирование запроса
        error_log("BuyAccs API Request: " . $full_url);
        error_log("BuyAccs API HTTP Code: " . $http_code);
        error_log("BuyAccs API Response: " . substr($response, 0, 500));
        
        // Обработка ошибок cURL
        if ($error) {
            error_log("BuyAccs API CURL Error: " . $error);
            return [
                'error' => true, 
                'message' => 'CURL Error: ' . $error,
                'http_code' => $http_code
            ];
        }
        
        // Проверка HTTP кода
        if ($http_code == 429) {
            return [
                'error' => true,
                'message' => 'Превышен лимит запросов к API. Подождите 5 минут.',
                'error_code' => 429,
                'http_code' => $http_code
            ];
        }
        
        if ($http_code == 401) {
            return [
                'error' => true,
                'message' => 'Неверный API ключ или отсутствует доступ.',
                'http_code' => $http_code
            ];
        }
        
        if ($http_code != 200) {
            return [
                'error' => true,
                'message' => 'HTTP Error: ' . $http_code,
                'http_code' => $http_code
            ];
        }
        
        // Декодирование JSON
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            error_log("BuyAccs API Invalid JSON: " . substr($response, 0, 200));
            return [
                'error' => true, 
                'message' => 'Invalid JSON response: ' . substr($response, 0, 100),
                'raw_response' => $response,
                'http_code' => $http_code
            ];
        }
        
        // Проверка на ошибки API (возвращаются в ключе errors)
        if (isset($decoded['errors']) && !empty($decoded['errors'])) {
            $error_msg = is_array($decoded['errors']) ? 
                        json_encode($decoded['errors'], JSON_UNESCAPED_UNICODE) : 
                        $decoded['errors'];
            
            error_log("BuyAccs API Errors: " . $error_msg);
            
            return [
                'error' => true, 
                'message' => 'API Errors: ' . $error_msg,
                'errors' => $decoded['errors'],
                'http_code' => $http_code
            ];
        }
        
        // Успешный ответ
        return $decoded;
    }
    
    /**
     * Установить язык для запросов
     * @param string $language Код языка (ru, en, de, es, fr, zh)
     */
    public function setLanguage($language) {
        $allowed_languages = ['ru', 'en', 'de', 'es', 'fr', 'zh'];
        if (in_array($language, $allowed_languages)) {
            $this->language = $language;
        }
        return $this;
    }
    
    /**
     * Установить валюту для запросов
     * @param string $currency Код валюты (rub, usd, eur, cny)
     */
    public function setCurrency($currency) {
        $allowed_currencies = ['rub', 'usd', 'eur', 'cny'];
        if (in_array($currency, $allowed_currencies)) {
            $this->currency = $currency;
        }
        return $this;
    }
    
    /**
     * Получить список категорий с товарами для отображения в магазине
     * @return array Структурированный список категорий с примерами товаров
     */
    public function getCatalogStructure() {
        // Получаем категории
        $categories_result = $this->getCategories();
        
        if (isset($categories_result['error'])) {
            return $categories_result;
        }
        
        $catalog = [];
        
        if (isset($categories_result['categories']) && is_array($categories_result['categories'])) {
            foreach ($categories_result['categories'] as $category) {
                $category_id = $category['id'] ?? 0;
                $category_name = $category['name'] ?? 'Без названия';
                
                // Получаем несколько товаров из категории для примера
                $products_result = $this->getProductsByCategory($category_id, 3);
                
                $category_info = [
                    'id' => $category_id,
                    'name' => $category_name,
                    'subcategories' => $category['subcategories'] ?? [],
                    'search_marks' => $category['searchMarkCategories'] ?? []
                ];
                
                if (isset($products_result['goods']) && is_array($products_result['goods'])) {
                    $category_info['sample_products'] = array_slice($products_result['goods'], 0, 3);
                }
                
                $catalog[] = $category_info;
                
                // Соблюдаем лимит API - не чаще 1 раза в 5 минут для categories
                // Делаем небольшую паузу между запросами товаров
                usleep(100000); // 0.1 секунды
            }
        }
        
        return $catalog;
    }
    
    /**
     * Простой тест API: проверка баланса и получение нескольких товаров
     * @return array Результаты теста
     */
    public function testApi() {
        $results = [];
        
        // Тест 1: Проверка баланса
        $results['balance_test'] = $this->getBalance('rub');
        
        // Тест 2: Получение категорий
        $results['categories_test'] = $this->getCategories();
        
        // Тест 3: Получение 5 товаров
        $results['products_test'] = $this->getProducts(['limit' => 5]);
        
        // Пауза между запросами чтобы не превысить лимит
        sleep(1);
        
        return $results;
    }
    
    /**
     * Автоматическое сопоставление товара по названию
     * @param string $product_name Название товара в вашем магазине
     * @param float $max_price Максимальная цена для поиска
     * @return int|null ID товара на buy-accs.net или null если не найден
     */
    public function findProductIdByName($product_name, $max_price = 5000) {
        $search_terms = $this->extractKeywords($product_name);
        
        // Получаем товары для поиска
        $result = $this->getProducts(['limit' => 50]);
        
        if (isset($result['error']) || !isset($result['goods'])) {
            return null;
        }
        
        $best_match = null;
        $best_score = 0;
        
        foreach ($result['goods'] as $product) {
            // Проверяем цену
            $price = $product['price'] ?? 0;
            if ($price > $max_price) {
                continue;
            }
            
            // Проверяем наличие
            $stock = $product['count'] ?? 0;
            if ($stock < 1) {
                continue;
            }
            
            // Сравниваем название
            $product_title = strtolower($product['title'] ?? '');
            $score = $this->calculateMatchScore($search_terms, $product_title);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $product['id'] ?? null;
            }
        }
        
        // Если нашли хорошее совпадение, возвращаем ID
        return $best_score > 0.3 ? $best_match : null;
    }
    
    /**
     * Извлечение ключевых слов из названия товара
     */
    private function extractKeywords($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Убираем стоп-слова
        $stop_words = ['для', 'на', 'в', 'с', 'и', 'или', 'у', 'по', 'от', 'до'];
        $words = array_diff($words, $stop_words);
        
        return array_values($words);
    }
    
    /**
     * Расчет степени совпадения
     */
    private function calculateMatchScore($search_terms, $product_title) {
        if (empty($search_terms)) {
            return 0;
        }
        
        $score = 0;
        $product_words = preg_split('/\s+/', $product_title, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($search_terms as $term) {
            foreach ($product_words as $product_word) {
                similar_text($term, $product_word, $percent);
                if ($percent > 80) {
                    $score += 1;
                    break;
                }
            }
        }
        
        return $score / count($search_terms);
    }
}
?>