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
function vip_rewards_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') vip_rewards_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
if ($_SERVER['REQUEST_METHOD'] === 'GET') vip_rewards_response(['code' => 11, 'msg' => 'Method not allowed', 'msgCode' => 12], 405);

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$language = (int)($body['language'] ?? 0);
$random = (string)($body['random'] ?? '');
$rewardType = (int)($body['rewardType'] ?? $body['taskId'] ?? 1);
$vipLevel = (int)($body['vipLevel'] ?? 1);
$signature = strtoupper((string)($body['signature'] ?? ''));
$expected = strtoupper(md5('{"language":' . $language . ',"random":"' . $random . '","taskId":' . $rewardType . '}'));
if ($signature === '' || !hash_equals($expected, $signature)) {
    vip_rewards_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$parts = preg_split('/\s+/', $authorization);
$token = (string)($parts[1] ?? ($parts[0] ?? ''));
$jwt = json_decode($token === '' ? '{}' : is_jwt_valid($token), true);
$userId = (int)($jwt['payload']['id'] ?? 0);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success' || $userId <= 0) {
    vip_rewards_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
if (!$userStmt) vip_rewards_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
if (!$user) vip_rewards_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);

$data = [
    ['id' => 1, 'rewardType' => 1, 'integral' => 0, 'balance' => 0, 'status' => 1, 'rate' => 0.0],
    ['id' => 2, 'rewardType' => 2, 'integral' => 0, 'balance' => 0, 'status' => 1, 'rate' => 0.0]
];
$vipStmt = @$conn->prepare('SELECT lvl FROM vip WHERE userid = ? LIMIT 1');
$currentLevel = 0;
if ($vipStmt) {
    $vipStmt->bind_param('i', $userId);
    $vipStmt->execute();
    $vipRow = $vipStmt->get_result()->fetch_assoc();
    $currentLevel = (int)($vipRow['lvl'] ?? 0);
    $vipStmt->close();
}
if ($currentLevel > 0 && $vipLevel <= $currentLevel) {
    $rewardStmt = @$conn->prepare('SELECT type, motta, status FROM viprec WHERE user_id = ? AND lvl = ? AND type IN (1, 2) ORDER BY created_at DESC');
    if ($rewardStmt) {
        $rewardStmt->bind_param('ii', $userId, $vipLevel);
        $rewardStmt->execute();
        $rows = $rewardStmt->get_result();
        while ($row = $rows->fetch_assoc()) {
            foreach ($data as &$reward) {
                if ((int)$reward['rewardType'] === (int)$row['type']) {
                    $reward['balance'] = (float)$row['motta'];
                    $reward['status'] = (int)$row['status'];
                }
            }
            unset($reward);
        }
        $rewardStmt->close();
    }
}
vip_rewards_response(['data' => $data, 'code' => 0, 'msg' => 'Succeed', 'msgCode' => 0, 'serviceNowTime' => date('Y-m-d H:i:s')]);
