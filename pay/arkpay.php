<?php
require_once __DIR__ . '/../serive/samparka.php';
$config = require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

function payment_error(string $message, int $status = 400): void {
    http_response_code($status);
    echo '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Deposit</title></head><body style="font-family:Arial;padding:24px;text-align:center"><h3>Deposit could not be started</h3><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p>Please try again later or contact support.</p></body></html>';
    exit;
}

$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);
$payTypeId = (int)($_GET['tyid'] ?? 0);
$userId = (int)($_GET['uid'] ?? 0);
$clientSign = trim((string)($_GET['sign'] ?? ''));
if (!$amount || $amount <= 0 || $userId <= 0 || $payTypeId <= 0) {
    payment_error('Invalid deposit details.');
}
if ($amount < 100 || $amount > 50000) {
    payment_error('Deposit amount must be between ₹100 and ₹50,000.');
}

$payNames = [
    1023 => 'SG-pay', 1124 => 'TB-pay', 1030 => 'LG-pay', 1029 => 'FAST-UPIPay',
    1021 => 'YaYa-APPpay', 1010 => 'FAST-UPIpay', 1012 => 'Super-ORpay',
    1013 => 'YaYa-ORpay', 1014 => 'UPI x QR', 1015 => 'SunPay', 2123 => 'UPAY-USDT',
    2190 => 'UU-USDT', 2191 => '7Day-PayTM', 2192 => 'UPI-PayTM'
];
$payName = $payNames[$payTypeId] ?? 'UPI';

$userStmt = $conn->prepare('SELECT mobile, codechorkamukala, createdate FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
if (!$userStmt) {
    payment_error('Payment database is unavailable.', 503);
}
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) {
    payment_error('User account was not found or is inactive.', 403);
}

$serial = date('YmdHis') . random_int(100000, 999999);
$siteUrl = rtrim((string)$config['site_url'], '/');
$notifyUrl = $siteUrl . '/pay/lgwebhook.php';
$returnUrl = $siteUrl . '/#/main';
$money = (string)round($amount * 100);
$params = [
    'app_id' => (string)$config['app_id'],
    'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
    'money' => $money,
    'notify_url' => $notifyUrl,
    'order_sn' => $serial,
    'remark' => 'Jalwa deposit',
    'return_url' => $returnUrl,
    'trade_type' => 'INRUPI'
];
ksort($params);
$signatureString = '';
foreach ($params as $key => $value) {
    $signatureString .= $key . '=' . $value . '&';
}
$signatureString .= 'key=' . $config['secret_key'];
$params['sign'] = strtoupper(md5($signatureString));

$ch = curl_init((string)$config['api_url']);
if (!$ch) {
    payment_error('Payment provider is unavailable.', 503);
}
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($response === false || $curlError !== '') {
    payment_error('Payment provider did not respond.', 503);
}
$responseData = json_decode($response, true);
$payUrl = is_array($responseData) ? (string)($responseData['data']['pay_url'] ?? '') : '';
$success = is_array($responseData) && (string)($responseData['status'] ?? '') === '1' && $payUrl !== '';
if (!$success) {
    error_log('Deposit provider failure HTTP ' . $httpCode . ': ' . substr((string)$response, 0, 500));
    payment_error('Payment provider rejected the request. No wallet balance was changed.', 502);
}

$createdAt = date('Y-m-d H:i:s');
$reference = $serial;
$channel = 'JalwaGateway';
$method = 'UPI';
$depositStmt = $conn->prepare("INSERT INTO thevani (payid, balakedara, motta, dharavahi, mula, ullekha, duravani, ekikrtapavati, dinankavannuracisi, madari, pavatiaidi, sthiti) VALUES ('1', ?, ?, ?, ?, ?, ?, ?, ?, '1005', '2', '0')");
if (!$depositStmt) {
    payment_error('Could not create a pending deposit record.', 503);
}
$depositStmt->bind_param('idssssss', $userId, $amount, $serial, $payName, $reference, $user['mobile'], $method, $createdAt);
if (!$depositStmt->execute()) {
    $depositStmt->close();
    payment_error('Could not create a pending deposit record.', 503);
}
$depositStmt->close();
header('Location: ' . filter_var($payUrl, FILTER_SANITIZE_URL), true, 302);
exit;
