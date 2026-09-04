<?php

declare(strict_types=1);

require_once __DIR__ . '/../serive/samparka.php';

const RSPAYMENT_API_URL = 'https://rspayment.shop/api.php';
const RSPAYMENT_WEBHOOK_URL = 'https://www.jalwagames.site/pay/webhook.php';
const RSPAYMENT_RETURN_URL = 'https://www.jalwagames.site/pay/success.php';
const RSPAYMENT_FALLBACK_URL = 'https://www.jalwagames.site/aritsulation/index.php?gateway=rupayex&tyid=3030';

function rspayment_redirect_fallback(array $query = []): never
{
    $query = array_intersect_key($query, array_flip(['amount', 'tyid', 'uid', 'sign', 'urlInfo']));
    $fallback = RSPAYMENT_FALLBACK_URL;
    if ($query !== []) {
        $fallback .= '&' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
    header('Location: ' . $fallback, true, 302);
    exit;
}

function rspayment_invalid_request(): never
{
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid deposit amount. Choose ₹200, ₹300, ₹500, ₹1000, ₹2000, or ₹5000.';
    exit;
}

function rspayment_fail_order(mysqli $conn, string $orderId): void
{
    $stmt = $conn->prepare("UPDATE thevani SET sthiti = '2' WHERE dharavahi = ? AND sthiti = '0'");
    if ($stmt) {
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $stmt->close();
    }
}

$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);
$userId = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
$allowedAmounts = [200, 300, 500, 1000, 2000, 5000];

if ($amount === false || $amount === null || $userId === false || $userId === null) {
    rspayment_invalid_request();
}

$amount = round((float)$amount, 2);
$userId = (int)$userId;
if ($userId < 1 || !in_array((int)$amount, $allowedAmounts, true) || $amount !== (float)(int)$amount) {
    rspayment_invalid_request();
}

$orderId = 'JW' . date('YmdHis') . random_int(100000, 999999);
$createdAt = date('Y-m-d H:i:s');
$providerName = 'Expert UPI-QR';
$mobile = '';
$mobileStmt = $conn->prepare('SELECT mobile FROM shonu_subjects WHERE id = ? LIMIT 1');
if ($mobileStmt) {
    $mobileStmt->bind_param('i', $userId);
    $mobileStmt->execute();
    $mobile = (string)(($mobileStmt->get_result()->fetch_assoc() ?: [])['mobile'] ?? '');
    $mobileStmt->close();
}

$insert = $conn->prepare("INSERT INTO thevani (balakedara, motta, dharavahi, mula, ullekha, duravani, ekikrtapavati, dinankavannuracisi, madari, pavatiaidi, sthiti) VALUES (?, ?, ?, ?, 'rspayment.shop', ?, 'N/A', ?, '1006', '2', '0')");
if (!$insert) {
    rspayment_redirect_fallback($_GET);
}
$insert->bind_param('idssss', $userId, $amount, $orderId, $providerName, $mobile, $createdAt);
if (!$insert->execute()) {
    $insert->close();
    rspayment_redirect_fallback($_GET);
}
$insert->close();

$query = http_build_query([
    'amount' => (int)$amount,
    'user_id' => 'INR59092',
    'order_id' => $orderId,
    'ext' => 'JalwaGame',
    'webhook_url' => RSPAYMENT_WEBHOOK_URL,
    'return_url' => RSPAYMENT_RETURN_URL,
], '', '&', PHP_QUERY_RFC3986);

$ch = curl_init(RSPAYMENT_API_URL . '?' . $query);
if (!$ch) {
    rspayment_fail_order($conn, $orderId);
    rspayment_redirect_fallback();
}
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$raw = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response = is_string($raw) ? json_decode($raw, true) : null;
$status = strtolower(trim((string)($response['status'] ?? $response['data']['status'] ?? '')));
$payUrl = trim((string)($response['data']['payUrl'] ?? $response['payUrl'] ?? ''));
$success = $curlError === '' && $httpCode >= 200 && $httpCode < 300 && $status === 'success' && preg_match('#^https://#i', $payUrl) === 1;

if (!$success) {
    error_log('rspayment create-link failed: HTTP=' . $httpCode . ' error=' . substr($curlError, 0, 200) . ' response=' . substr((string)$raw, 0, 1000));
    rspayment_fail_order($conn, $orderId);
    rspayment_redirect_fallback($_GET);
}

header('Cache-Control: no-store');
header('Location: ' . $payUrl, true, 302);
exit;
?>
