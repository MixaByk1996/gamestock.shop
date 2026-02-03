<?php
/**
 * lava_webhook.php - Webhook handler for Lava.ru payment notifications
 *
 * This endpoint receives POST requests from Lava.ru when payment status changes.
 * Configure this URL in your Lava.ru business dashboard or pass as hookUrl
 * when creating invoices.
 *
 * Expected URL: https://gamestock.shop/lava_webhook.php
 */

// Don't start a session for webhook - it's a server-to-server call
require_once 'includes/config.php';
require_once 'includes/LavaPayment.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Read raw POST body
$raw_body = file_get_contents('php://input');
$payload = json_decode($raw_body, true);

if (!$payload) {
    error_log("Lava webhook: Invalid JSON body");
    http_response_code(400);
    exit('Invalid JSON');
}

// Log webhook for debugging
error_log("Lava webhook received: " . $raw_body);

// Verify signature from Authorization header
$lava = new LavaPayment();
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!empty($auth_header) && $lava->isConfigured()) {
    if (!$lava->verifyWebhookSignature($payload, $auth_header)) {
        error_log("Lava webhook: Invalid signature");
        http_response_code(403);
        exit('Invalid signature');
    }
}

// Extract payment data
$invoice_id = $payload['invoice_id'] ?? '';
$order_id = $payload['order_id'] ?? '';
$status = $payload['status'] ?? '';
$amount = $payload['amount'] ?? 0;
$pay_time = $payload['pay_time'] ?? '';
$credited = $payload['credited'] ?? 0;

if (empty($order_id) || empty($status)) {
    error_log("Lava webhook: Missing required fields");
    http_response_code(400);
    exit('Missing required fields');
}

// Only process successful payments
if ($status !== 'success') {
    error_log("Lava webhook: Status is '$status' for order $order_id, skipping");
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Non-success status ignored']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Find the order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
    $stmt->execute([$order_id, $order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        error_log("Lava webhook: Order not found: $order_id");
        http_response_code(404);
        exit('Order not found');
    }

    // Skip if already paid
    if ($order['payment_status'] === 'paid') {
        error_log("Lava webhook: Order $order_id already paid, skipping");
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Already processed']);
        exit;
    }

    // Get real account data from supplier
    require_once 'includes/ApiSuppliers/BuyAccsNet.php';

    // Include the functions from payment.php needed for account provisioning
    require_once __DIR__ . '/payment.php';

    $account_data = getRealAccountForOrder($pdo, $order['id']);

    $account_source = '';
    if (in_array($account_data['type'], ['buyaccs_api', 'buyaccs_file'])) {
        $account_source = 'Куплено у поставщика BuyAccs (заказ #' . ($account_data['supplier_order_id'] ?? 'N/A') . ')';
    } else {
        $account_source = 'Сгенерирован уникальный аккаунт';
    }

    // Update order as paid
    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'completed',
            payment_status = 'paid',
            payment_id = ?,
            payment_method = 'lava',
            login_data = ?,
            password_data = ?,
            notes = CONCAT(COALESCE(notes, ''), ' | Lava payment: ', ?),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $invoice_id,
        $account_data['login'],
        $account_data['password'],
        $account_source,
        $order['id']
    ]);

    // Record transaction if user is associated
    if ($order['user_id'] > 0) {
        $txn_id = 'LAVA_' . date('YmdHis') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, amount, description, status, payment_system, transaction_id, related_order_id)
            VALUES (?, 'purchase', ?, ?, 'completed', 'lava', ?, ?)
        ");
        $stmt->execute([
            $order['user_id'],
            $amount,
            "Оплата заказа #" . $order['order_number'] . " через Lava",
            $txn_id,
            $order['id']
        ]);
    }

    error_log("Lava webhook: Order {$order['id']} successfully processed. Invoice: $invoice_id");

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    error_log("Lava webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
