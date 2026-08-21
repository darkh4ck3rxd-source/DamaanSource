<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

date_default_timezone_set('Asia/Kolkata');
function vip_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') vip_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
if ($_SERVER['REQUEST_METHOD'] === 'GET') vip_response(['code' => 11, 'msg' => 'Method not allowed', 'msgCode' => 12], 405);

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$language = (int)($body['language'] ?? 0);
$random = (string)($body['random'] ?? '');
$signature = strtoupper((string)($body['signature'] ?? ''));
$expected = strtoupper(md5('{"language":' . $language . ',"random":"' . $random . '"}'));
if ($signature === '' || !hash_equals($expected, $signature)) {
    vip_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$parts = preg_split('/\s+/', $authorization);
$token = (string)($parts[1] ?? ($parts[0] ?? ''));
$jwt = json_decode($token === '' ? '{}' : is_jwt_valid($token), true);
$userId = (int)($jwt['payload']['id'] ?? 0);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success' || $userId <= 0) {
    vip_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
if (!$userStmt) vip_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) vip_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);

$currentExp = 0;
$currentLevel = 0;
$vipStmt = @$conn->prepare('SELECT expe, lvl FROM vip WHERE userid = ? LIMIT 1');
if ($vipStmt) {
    $vipStmt->bind_param('i', $userId);
    $vipStmt->execute();
    $vipRow = $vipStmt->get_result()->fetch_assoc();
    $currentExp = (int)($vipRow['expe'] ?? 0);
    $currentLevel = (int)($vipRow['lvl'] ?? 0);
    $vipStmt->close();
}

$thresholds = [3000, 30000, 400000, 4000000, 20000000, 100000000, 500000000, 1000000000, 2000000000, 5000000000];
$data = [];
foreach ($thresholds as $index => $upgrade) {
    $id = $index + 1;
    $previous = $index === 0 ? 0 : $thresholds[$index - 1];
    $data[] = [
        'id' => $id,
        'vipName' => 'VIP' . $id,
        'status' => 1,
        'currentExp' => $currentExp,
        'upgrade' => $upgrade,
        'relegationExp' => $currentExp,
        'relegation' => $previous,
        'deductExp' => (int)floor($upgrade / 2),
        'amount' => 1,
        'upgradeStatus' => $currentLevel >= $id ? 1 : 0
    ];
}
vip_response(['data' => $data, 'code' => 0, 'msg' => 'Succeed', 'msgCode' => 0, 'serviceNowTime' => date('Y-m-d H:i:s')]);
