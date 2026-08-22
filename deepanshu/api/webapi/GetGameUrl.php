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

function game_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    game_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
}
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$gameCode = trim((string)($body['gameCode'] ?? ''));
$vendorCode = trim((string)($body['vendorCode'] ?? ''));
$random = trim((string)($body['random'] ?? ''));
$signature = strtoupper(trim((string)($body['signature'] ?? '')));
$hasDeviceType = array_key_exists('deviceType', $body);
$hasPhoneType = array_key_exists('phonetype', $body);
$requiredFieldsPresent = $gameCode !== ''
    && $vendorCode !== ''
    && $random !== ''
    && $signature !== ''
    && array_key_exists('language', $body)
    && array_key_exists('timestamp', $body)
    && ($hasDeviceType || $hasPhoneType);
if (!$requiredFieldsPresent) {
    game_response(['code' => 7, 'msg' => 'Param is Invalid', 'msgCode' => 6], 200);
}

// The web client signs the complete request data after sorting keys and
// excluding only signature/track/xosoBettingData. Keep numeric zero values.
$signedPayload = $body;
foreach (['signature', 'track', 'xosoBettingData'] as $excludedKey) {
    unset($signedPayload[$excludedKey]);
}
foreach ($signedPayload as $key => $value) {
    if ($value === null || $value === '') {
        unset($signedPayload[$key]);
    }
}
ksort($signedPayload, SORT_STRING);
$signatureString = json_encode($signedPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$expectedSignature = strtoupper(md5($signatureString === false ? '' : $signatureString));
if (!hash_equals($expectedSignature, $signature)) {
    game_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3], 200);
}
$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$parts = preg_split('/\s+/', $authorization);
$token = (string)($parts[1] ?? ($parts[0] ?? ''));
$jwt = json_decode($token === '' ? '{}' : is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    game_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$stmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE akshinak = ? AND status = 1 LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    game_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$site = rtrim(getenv('APP_URL') ?: 'https://damaansource-production.up.railway.app', '/');
$localUrl = $site . '/jet/index.html?gameCode=' . rawurlencode($gameCode) . '&vendorCode=' . rawurlencode($vendorCode);
$data = ['url' => $localUrl, 'returnType' => 1, 'gameCode' => $gameCode, 'vendorCode' => $vendorCode];
game_response(['data' => $data, 'code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
