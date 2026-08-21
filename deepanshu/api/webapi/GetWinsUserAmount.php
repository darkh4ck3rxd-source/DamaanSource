<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');

date_default_timezone_set('Asia/Kolkata');
function wins_amount_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    wins_amount_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    wins_amount_response(['code' => 11, 'msg' => 'Method not allowed', 'msgCode' => 12], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$language = (int)($body['language'] ?? 0);
$random = (string)($body['random'] ?? '');
$signature = strtoupper((string)($body['signature'] ?? ''));
$expected = strtoupper(md5('{"language":' . $language . ',"random":"' . $random . '"}'));
if ($signature === '' || !hash_equals($expected, $signature)) {
    wins_amount_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$authParts = preg_split('/\s+/', $authorization);
$token = (string)($authParts[1] ?? ($authParts[0] ?? ''));
if ($token === '') {
    wins_amount_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$jwt = json_decode(is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    wins_amount_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE akshinak = ? LIMIT 1');
if (!$userStmt) {
    wins_amount_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
}
$userStmt->bind_param('s', $token);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) {
    wins_amount_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$userId = (int)$user['id'];
$amount = 0.0;
$walletStmt = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1');
if ($walletStmt) {
    $walletStmt->bind_param('i', $userId);
    $walletStmt->execute();
    $wallet = $walletStmt->get_result()->fetch_assoc();
    $amount = (float)($wallet['motta'] ?? 0);
    $walletStmt->close();
}

wins_amount_response([
    'data' => [
        'amount' => number_format($amount, 2, '.', ''),
        'uRate' => 0,
        'uGold' => 0,
    ],
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date('Y-m-d H:i:s'),
]);
?>
