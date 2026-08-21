<?php
include "../../conn.php";
include "../../functions2.php";

header('Content-Type: application/json; charset=utf-8');
header('Strict-Transport-Security: max-age=31536000');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');

date_default_timezone_set('Asia/Kolkata');
$serviceNowTime = date('Y-m-d H:i:s');
$res = [
    'code' => 11,
    'msg' => 'Method not allowed',
    'msgCode' => 12,
    'serviceNowTime' => $serviceNowTime,
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(405);
    echo json_encode($res);
    exit;
}

$body = file_get_contents('php://input');
$post = json_decode($body, true);
if (!is_array($post)) {
    $res['code'] = 7;
    $res['msg'] = 'Param is Invalid';
    $res['msgCode'] = 6;
    echo json_encode($res);
    exit;
}

$accountNo = trim((string)($post['accountNo'] ?? $post['accountno'] ?? ''));
$beneficiaryName = trim((string)($post['beneficiaryName'] ?? $post['beneficiaryname'] ?? ''));
$language = (string)($post['language'] ?? '');
$random = (string)($post['random'] ?? '');
$signature = strtoupper((string)($post['signature'] ?? ''));
$timestamp = (string)($post['timestamp'] ?? '');

if ($accountNo === '' || $beneficiaryName === '' || $language === '' || $random === '' || $signature === '') {
    $res['code'] = 7;
    $res['msg'] = 'Param is Invalid';
    $res['msgCode'] = 6;
    echo json_encode($res);
    exit;
}

// The client has used both camelCase and lowercase field names across wallet builds.
// Accept only a signature generated from one of the exact supported payload shapes.
$canonicalPayloads = [
    '{"accountNo":"' . $accountNo . '","beneficiaryName":"' . $beneficiaryName . '","language":' . $language . ',"random":"' . $random . '"}',
    '{"accountno":"' . $accountNo . '","beneficiaryname":"' . $beneficiaryName . '","language":' . $language . ',"random":"' . $random . '"}',
];
if ($timestamp !== '') {
    $canonicalPayloads[] = '{"accountNo":"' . $accountNo . '","beneficiaryName":"' . $beneficiaryName . '","language":' . $language . ',"random":"' . $random . '","timestamp":' . $timestamp . '}';
    $canonicalPayloads[] = '{"accountno":"' . $accountNo . '","beneficiaryname":"' . $beneficiaryName . '","language":' . $language . ',"random":"' . $random . '","timestamp":' . $timestamp . '}';
}
$signatureValid = false;
foreach ($canonicalPayloads as $canonicalPayload) {
    if (hash_equals(strtoupper(md5($canonicalPayload)), $signature)) {
        $signatureValid = true;
        break;
    }
}
if (!$signatureValid) {
    $res['code'] = 5;
    $res['msg'] = 'Wrong signature';
    $res['msgCode'] = 3;
    echo json_encode($res);
    exit;
}

$authHeader = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$bearer = preg_split('/\s+/', $authHeader);
$author = (count($bearer) >= 2 && strtolower($bearer[0]) === 'bearer') ? $bearer[1] : '';
if ($author === '') {
    http_response_code(401);
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    echo json_encode($res);
    exit;
}

$dataAuth = json_decode(is_jwt_valid($author), true);
if (($dataAuth['status'] ?? '') !== 'Success' || empty($dataAuth['payload']['id'])) {
    http_response_code(401);
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    echo json_encode($res);
    exit;
}

$subjectStmt = $conn->prepare('SELECT akshinak FROM shonu_subjects WHERE akshinak = ? LIMIT 1');
$subjectStmt->bind_param('s', $author);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();
if ($subjectResult->num_rows !== 1) {
    http_response_code(401);
    $res['code'] = 4;
    $res['msg'] = 'No operation permission';
    $res['msgCode'] = 2;
    echo json_encode($res);
    exit;
}

$userId = (int)$dataAuth['payload']['id'];
$codeType = 'UPI';
$empty = '';
$upiName = 'UPI';
$bankId = 0;

$insertStmt = $conn->prepare('INSERT INTO khate (byabaharkarta, khatesankhye, khatakrama, phalanubhavi, kodprakara, daka, kod, khatehesaru, duravani, sthiti) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
$insertStmt->bind_param('isissssss', $userId, $accountNo, $bankId, $beneficiaryName, $codeType, $empty, $empty, $upiName, $empty);
if (!$insertStmt->execute()) {
    http_response_code(500);
    $res['code'] = 1;
    $res['msg'] = 'Database insertion error';
    $res['msgCode'] = 101;
    echo json_encode($res);
    exit;
}

$res['data'] = null;
$res['code'] = 0;
$res['msg'] = 'Succeed';
$res['msgCode'] = 0;
echo json_encode($res);
mysqli_close($conn);
?>
