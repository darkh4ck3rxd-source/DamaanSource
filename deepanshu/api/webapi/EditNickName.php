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

function nickname_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$nickname = trim((string)($body['nikeName'] ?? $body['nickName'] ?? ''));
if ($nickname === '' || mb_strlen($nickname) > 12 || !preg_match('/^[\p{L}\p{N}_ -]+$/u', $nickname)) {
    nickname_response(['code' => 7, 'msg' => 'Invalid nickname', 'msgCode' => 6]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$parts = preg_split('/\s+/', $authorization);
$token = (string)($parts[1] ?? ($parts[0] ?? ''));
$jwt = json_decode($token === '' ? '{}' : is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    nickname_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$stmt = $conn->prepare('UPDATE shonu_subjects SET codechorkamukala = ? WHERE akshinak = ? LIMIT 1');
$stmt->bind_param('ss', $nickname, $token);
$ok = $stmt->execute();
$stmt->close();
nickname_response($ok ? ['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0] : ['code' => 9, 'msg' => 'Unable to save nickname', 'msgCode' => 9], $ok ? 200 : 500);
