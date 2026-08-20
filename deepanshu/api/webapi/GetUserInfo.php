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

function user_info_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    user_info_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    user_info_response(['code' => 11, 'msg' => 'Method not allowed', 'msgCode' => 12], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$language = (int)($body['language'] ?? 0);
$random = (string)($body['random'] ?? '');
$signature = strtoupper((string)($body['signature'] ?? ''));
$expected = strtoupper(md5('{"language":' . $language . ',"random":"' . $random . '"}'));
if ($signature !== '' && !hash_equals($expected, $signature)) {
    user_info_response(['code' => 5, 'msg' => 'Wrong signature', 'msgCode' => 3]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$authParts = preg_split('/\s+/', $authorization);
$token = (string)($authParts[1] ?? ($authParts[0] ?? ''));
if ($token === '') {
    user_info_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}
$jwt = json_decode(is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    user_info_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$createProfiles = "CREATE TABLE IF NOT EXISTS jalwa_user_profiles (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    avatar_data MEDIUMTEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $createProfiles);

$stmt = $conn->prepare('SELECT id, mobile, owncode, codechorkamukala, createdate, shonullgnt FROM shonu_subjects WHERE akshinak = ? LIMIT 1');
if (!$stmt) {
    user_info_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
}
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    user_info_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$userId = (int)$user['id'];
$wallet = 0.0;
$walletStmt = $conn->prepare('SELECT motta FROM shonu_kaichila WHERE balakedara = ? LIMIT 1');
if ($walletStmt) {
    $walletStmt->bind_param('i', $userId);
    $walletStmt->execute();
    $walletRow = $walletStmt->get_result()->fetch_assoc();
    $wallet = (float)($walletRow['motta'] ?? 0);
    $walletStmt->close();
}

$avatar = '/assets/png/avatar1-2f23f3bd.png';
$avatarStmt = $conn->prepare('SELECT avatar_data FROM jalwa_user_profiles WHERE user_id = ? LIMIT 1');
if ($avatarStmt) {
    $avatarStmt->bind_param('i', $userId);
    $avatarStmt->execute();
    $avatarRow = $avatarStmt->get_result()->fetch_assoc();
    if (!empty($avatarRow['avatar_data'])) {
        $avatar = (string)$avatarRow['avatar_data'];
    }
    $avatarStmt->close();
}

$uRate = 0.0;
$rateResult = @mysqli_query($conn, "SELECT rate FROM tbl_pg WHERE value = 'usdt' LIMIT 1");
if ($rateResult) {
    $rateRow = mysqli_fetch_assoc($rateResult);
    $uRate = (float)($rateRow['rate'] ?? 0);
}

$unread = 0;
$noticeStmt = $conn->prepare('SELECT COUNT(*) AS total FROM notification WHERE user_id = ? AND state = 0');
if ($noticeStmt) {
    $noticeStmt->bind_param('i', $userId);
    $noticeStmt->execute();
    $noticeRow = $noticeStmt->get_result()->fetch_assoc();
    $unread = (int)($noticeRow['total'] ?? 0);
    $noticeStmt->close();
}

$nickname = (string)($user['codechorkamukala'] ?: ('Member' . $userId));
$lastLogin = (string)($user['shonullgnt'] ?: $user['createdate']);
$data = [
    'userId' => $userId,
    'uid' => $userId,
    'ownCode' => (string)$user['owncode'],
    'userPhoto' => $avatar,
    'userName' => '91' . (string)$user['mobile'],
    'nickName' => $nickname,
    'amount' => $wallet,
    'uRate' => $uRate,
    'userLoginDate' => $lastLogin,
    'vipLevel' => 0,
    'sign' => strtoupper(hash('sha256', '{"userId":' . $userId . ',"userPhoto":"' . $avatar . '","userName":91' . $user['mobile'] . ',"nickName":"' . $nickname . '","createdate":"' . $user['createdate'] . '"}')),
    'amountofCode' => 0.0,
    'isWithdraw' => null,
    'message' => null,
    'withdrawCount' => 0,
    'addTime' => $user['createdate'],
    'startTime' => null,
    'endTime' => null,
    'fee' => 0.0,
    'unRead' => $unread,
    'verifyMethods' => ['mobile' => '91' . (string)$user['mobile'], 'email' => '', 'google' => '0'],
    'regType' => 1,
    'bindReward' => 0.0,
    'isGoogle' => '0',
    'isOpenChampion' => '0',
    'isAllowWithdraw' => 1,
    'isRePwd' => '1',
    'integral' => 0,
    'isOpenPointMall' => '0',
    'isOpenAmountOfCode' => '1',
    'isAllowUserAddUSDT' => '1',
    'isShowWalletTotalCT' => '0',
    'isShowRechargeBankList' => '0',
    'isPopupCommissionSwitch' => '0',
    'userGroupAuth' => ['0','1','2','3','4','5','6','7','8','9'],
    'groupDataShowAuth' => array_map(fn($id) => ['id' => $id, 'isShow' => true], [11,12,15,16,17,18,19,20])
];

user_info_response(['data' => $data, 'code' => 0, 'msg' => 'Succeed', 'msgCode' => 0, 'serviceNowTime' => date('Y-m-d H:i:s')]);
