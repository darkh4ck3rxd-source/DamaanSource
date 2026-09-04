<?php

declare(strict_types=1);

require_once __DIR__ . '/../serive/samparka.php';
require_once __DIR__ . '/../main/rupayex_payout.php';

function rspayment_webhook_input(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);
    if (is_array($json)) {
        return $json;
    }
    return is_array($_POST) ? $_POST : [];
}

function rspayment_webhook_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = rspayment_webhook_input();
$orderId = trim((string)($payload['order_id'] ?? $payload['merchant_order_id'] ?? $payload['merchantOrderId'] ?? $payload['data']['order_id'] ?? $payload['data']['merchant_order_id'] ?? ''));
$status = strtolower(trim((string)($payload['status'] ?? $payload['payment_status'] ?? $payload['paymentStatus'] ?? $payload['data']['status'] ?? '')));
$amount = (float)($payload['amount'] ?? $payload['data']['amount'] ?? 0);
$utr = trim((string)($payload['utr'] ?? $payload['transaction_id'] ?? $payload['transactionId'] ?? $payload['data']['utr'] ?? $payload['data']['transaction_id'] ?? ''));

if ($orderId === '') {
    rspayment_webhook_response(['status' => false, 'message' => 'Missing order_id.'], 400);
}

if (!in_array($status, ['success', 'successful', 'paid', 'completed'], true)) {
    rspayment_webhook_response(['status' => true, 'message' => 'Payment status recorded; no credit was made.', 'order_id' => $orderId], 200);
}

if ($amount <= 0) {
    rspayment_webhook_response(['status' => false, 'message' => 'Missing or invalid amount.'], 400);
}

try {
    rupayex_ensure_deposit_schema($conn);
    $result = rupayex_credit_verified_order($conn, $orderId, $utr, 'SUCCESS', $amount);
    rspayment_webhook_response([
        'status' => true,
        'message' => $result['message'] ?? 'Payment processed.',
        'order_id' => $orderId,
        'payment_status' => 'SUCCESS',
        'credited' => (bool)($result['credited'] ?? false),
    ]);
} catch (Throwable $exception) {
    error_log('rspayment webhook error: ' . $exception->getMessage());
    rspayment_webhook_response(['status' => false, 'message' => 'Payment verification failed.'], 500);
}
?>
