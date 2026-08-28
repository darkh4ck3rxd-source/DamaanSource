<?php
/**
 * Rupayex deposit callback. The callback payload is never trusted for crediting;
 * the provider order-status endpoint is queried first.
 */
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/../serive/samparka.php';
require_once __DIR__ . '/../main/rupayex_payout.php';

function rupayex_callback_input(): array
{
    $raw = file_get_contents('php://input');
    $body = json_decode($raw ?: '', true);
    if (is_array($body)) { return $body; }
    return $_REQUEST;
}

function rupayex_callback_finish(array $payload, int $status = 200): never
{
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: https://www.jalwagames.site/#/wallet/RechargeHistory', true, 302);
        exit;
    }
    rupayex_json_response($payload, $status);
}

$input = rupayex_callback_input();
$orderId = trim((string)($input['order_id'] ?? $input['orderId'] ?? ''));
if ($orderId === '' || !preg_match('/^[A-Za-z0-9._-]{4,100}$/', $orderId)) {
    rupayex_callback_finish(['status' => false, 'message' => 'Invalid order ID.'], 400);
}

try {
    if (!rupayex_deposit_enabled()) {
        rupayex_callback_finish(['status' => false, 'message' => 'Rupayex deposit is not enabled.'], 503);
    }
    rupayex_ensure_deposit_schema($conn);
    $provider = rupayex_order_status($orderId);
    $providerBody = $provider['body'];
    $providerData = is_array($providerBody['data'] ?? null) ? $providerBody['data'] : $providerBody;
    $returnedOrderId = trim((string)($providerData['order_id'] ?? $providerData['orderId'] ?? $orderId));
    if ($returnedOrderId !== $orderId) {
        rupayex_callback_finish(['status' => false, 'message' => 'Provider order mismatch.'], 409);
    }
    $status = (string)($providerData['payment_status'] ?? $providerData['status'] ?? 'UNKNOWN');
    $verifiedAmount = (float)($providerData['amount'] ?? 0);
    $utr = trim((string)($providerData['utr'] ?? $providerData['transaction_id'] ?? ''));
    $auditStatus = rupayex_normalize_status($status);
    $rawProvider = json_encode($providerBody, JSON_UNESCAPED_SLASHES);
    $audit = $conn->prepare('UPDATE rupayex_orders SET provider_status = ?, utr = ?, provider_response = ?, updated_at = NOW() WHERE local_order_id = ?');
    if (!$audit) { throw new RuntimeException('Could not prepare payment audit update.'); }
    $audit->bind_param('ssss', $auditStatus, $utr, $rawProvider, $orderId);
    if (!$audit->execute()) { throw new RuntimeException('Could not save payment status.'); }
    $audit->close();
    if ($conn->affected_rows === 0) {
        $fallback = $conn->prepare("INSERT INTO rupayex_orders (local_order_id, provider_order_id, user_id, amount, provider_status, utr, provider_response, updated_at) SELECT h.dharavahi, h.dharavahi, h.balakedara, h.motta, ?, ?, ?, NOW() FROM thevani h WHERE h.dharavahi = ? LIMIT 1");
        if (!$fallback) { throw new RuntimeException('Could not prepare fallback payment audit.'); }
        $fallback->bind_param('ssss', $auditStatus, $utr, $rawProvider, $orderId);
        if (!$fallback->execute()) { throw new RuntimeException('Could not save fallback payment status.'); }
        $fallback->close();
    }

    if ($auditStatus === 'SUCCESS') {
        $result = rupayex_credit_verified_order($conn, $orderId, $utr, $status, $verifiedAmount);
        rupayex_callback_finish(['status' => true, 'message' => $result['message'], 'order_id' => $orderId, 'payment_status' => 'SUCCESS'], 200);
    }
    rupayex_callback_finish(['status' => true, 'message' => 'Payment status recorded; no credit was made.', 'order_id' => $orderId, 'payment_status' => $auditStatus], 200);
} catch (Throwable $exception) {
    error_log('Rupayex callback error: ' . $exception->getMessage());
    rupayex_callback_finish(['status' => false, 'message' => 'Payment verification failed.'], 500);
}
