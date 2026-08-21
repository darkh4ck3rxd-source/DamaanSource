<?php
include '../../conn.php';
include '../../functions2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');

define('PAYMENT_SETTINGS_DEFAULT_WAKE_MIN', 200.0);
define('PAYMENT_SETTINGS_DEFAULT_USDT_MIN', 10.0);

function payment_settings_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    payment_settings_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    payment_settings_response(['code' => 11, 'msg' => 'Method not allowed', 'msgCode' => 12], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$language = (int)($body['language'] ?? 0);
$random = (string)($body['random'] ?? '');
$signature = strtoupper((string)($body['signature'] ?? ''));
$expected = strtoupper(md5('{"language":' . $language . ',"random":"' . $random . '"}'));
if ($signature !== '' && !hash_equals($expected, $signature)) {
    payment_settings_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$authParts = preg_split('/\s+/', $authorization);
$token = (string)($authParts[1] ?? ($authParts[0] ?? ''));
if ($token === '') {
    payment_settings_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$jwt = json_decode(is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    payment_settings_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE akshinak = ? AND status = 1 LIMIT 1');
if (!$userStmt) {
    payment_settings_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
}
$userStmt->bind_param('s', $token);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) {
    payment_settings_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$conn->query("CREATE TABLE IF NOT EXISTS jalwa_payment_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$settings = [
    'wake_upi_qr' => '',
    'wake_upi_id' => '',
    'wake_min_amount' => (string)PAYMENT_SETTINGS_DEFAULT_WAKE_MIN,
    'usdt_qr' => '',
    'usdt_address' => '',
    'usdt_network' => 'TRC20',
    'usdt_min_amount' => (string)PAYMENT_SETTINGS_DEFAULT_USDT_MIN,
];
$result = $conn->query('SELECT setting_key, setting_value FROM jalwa_payment_settings');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['setting_key'], $settings)) {
            $settings[$row['setting_key']] = (string)$row['setting_value'];
        }
    }
}

$wakeMin = max(1.0, (float)$settings['wake_min_amount']);
$usdtMin = max(1.0, (float)$settings['usdt_min_amount']);
payment_settings_response([
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'data' => [
        'wakeUpiQr' => $settings['wake_upi_qr'],
        'wakeUpiId' => $settings['wake_upi_id'],
        'wakeMinAmount' => $wakeMin,
        'usdtQr' => $settings['usdt_qr'],
        'usdtAddress' => $settings['usdt_address'],
        'usdtNetwork' => $settings['usdt_network'],
        'usdtMinAmount' => $usdtMin,
    ],
]);
