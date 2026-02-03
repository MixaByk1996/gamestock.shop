<?php
/**
 * LavaPayment - Integration with Lava.ru payment system
 * Documentation: https://dev.lava.ru/
 */
class LavaPayment {
    private $shop_id;
    private $secret_key;
    private $webhook_key;
    private $api_url = 'https://api.lava.ru/business/';

    public function __construct() {
        $this->shop_id = defined('LAVA_SHOP_ID') ? LAVA_SHOP_ID : '';
        $this->secret_key = defined('LAVA_SECRET_KEY') ? LAVA_SECRET_KEY : '';
        $this->webhook_key = defined('LAVA_WEBHOOK_KEY') ? LAVA_WEBHOOK_KEY : '';
    }

    /**
     * Check if Lava payment is configured
     */
    public function isConfigured() {
        return !empty($this->shop_id) && !empty($this->secret_key);
    }

    /**
     * Generate signature for API request
     * Uses HMAC SHA256 with the secret key
     */
    private function generateSignature($data) {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return hash_hmac('sha256', $json, $this->secret_key);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature) {
        $key = !empty($this->webhook_key) ? $this->webhook_key : $this->secret_key;
        $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $key);
        return hash_equals($expected, $signature);
    }

    /**
     * Create a payment invoice
     *
     * @param float $sum Amount to pay
     * @param string $orderId Unique order identifier
     * @param string $comment Optional comment
     * @param string $successUrl URL to redirect on success
     * @param string $failUrl URL to redirect on failure
     * @param string $hookUrl Webhook URL for payment notifications
     * @param int $expire Invoice lifetime in minutes (default 300 = 5 hours)
     * @return array API response with invoice URL
     */
    public function createInvoice($sum, $orderId, $comment = '', $successUrl = '', $failUrl = '', $hookUrl = '', $expire = 300) {
        $data = [
            'shopId' => $this->shop_id,
            'sum' => round($sum, 2),
            'orderId' => (string)$orderId,
            'expire' => $expire,
        ];

        if (!empty($comment)) {
            $data['comment'] = mb_substr($comment, 0, 255, 'UTF-8');
        }
        if (!empty($successUrl)) {
            $data['successUrl'] = $successUrl;
        }
        if (!empty($failUrl)) {
            $data['failUrl'] = $failUrl;
        }
        if (!empty($hookUrl)) {
            $data['hookUrl'] = $hookUrl;
        }

        $signature = $this->generateSignature($data);

        $url = $this->api_url . 'invoice/create';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Signature: ' . $signature,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Lava API cURL error: " . $error);
            return ['error' => true, 'message' => 'Connection error: ' . $error];
        }

        $result = json_decode($response, true);

        if ($http_code !== 200 || !isset($result['status_check']) || $result['status_check'] !== true) {
            $err_msg = $result['error'] ?? $result['message'] ?? 'Unknown error';
            error_log("Lava API error (HTTP $http_code): " . json_encode($err_msg));
            return ['error' => true, 'message' => 'Payment system error', 'details' => $err_msg];
        }

        return [
            'error' => false,
            'invoice_id' => $result['data']['id'] ?? '',
            'url' => $result['data']['url'] ?? '',
            'amount' => $result['data']['amount'] ?? $sum,
            'status' => $result['data']['status'] ?? 0,
            'expired' => $result['data']['expired'] ?? '',
        ];
    }

    /**
     * Check invoice status
     *
     * @param string $invoiceId Invoice UUID
     * @return array Invoice status data
     */
    public function getInvoiceStatus($invoiceId) {
        $data = [
            'shopId' => $this->shop_id,
            'invoiceId' => $invoiceId,
        ];

        $signature = $this->generateSignature($data);

        $url = $this->api_url . 'invoice/status';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Signature: ' . $signature,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'Connection error: ' . $error];
        }

        return json_decode($response, true);
    }
}
