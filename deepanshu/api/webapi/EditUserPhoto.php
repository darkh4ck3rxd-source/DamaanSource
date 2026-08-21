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

function photo_response(array $payload, int $http = 200): void {
    http_response_code($http);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    photo_response(['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0]);
}
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$userPhoto = trim((string)($body['userPhoto'] ?? $body['avatarData'] ?? ''));
if ($userPhoto === '') {
    photo_response(['code' => 7, 'msg' => 'Param is Invalid', 'msgCode' => 6]);
}

$authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$parts = preg_split('/\s+/', $authorization);
$token = (string)($parts[1] ?? ($parts[0] ?? ''));
$jwt = json_decode($token === '' ? '{}' : is_jwt_valid($token), true);
if (!is_array($jwt) || ($jwt['status'] ?? '') !== 'Success') {
    photo_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$stmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE akshinak = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    photo_response(['code' => 4, 'msg' => 'No operation permission', 'msgCode' => 2], 401);
}

$storedPhoto = '';
if (str_starts_with($userPhoto, 'data:image/')) {
    if (strlen($userPhoto) > 2100000 || !preg_match('/^data:image\/(png|jpe?g|webp);base64,([A-Za-z0-9+\/=_-]+)$/i', $userPhoto, $matches)) {
        photo_response(['code' => 7, 'msg' => 'Invalid image', 'msgCode' => 6]);
    }
    $binary = base64_decode($matches[2], true);
    if ($binary === false || @getimagesizefromstring($binary) === false) {
        photo_response(['code' => 7, 'msg' => 'Invalid image', 'msgCode' => 6]);
    }
    $mime = @getimagesizefromstring($binary)['mime'] ?? 'image/jpeg';
    $storedPhoto = 'data:' . $mime . ';base64,' . base64_encode($binary);
} else {
    $allowed = [
        '1' => '/assets/png/jalwa-avatar-01.png',
        'avatar1' => '/assets/png/jalwa-avatar-01.png',
        'avatar-1' => '/assets/png/jalwa-avatar-01.png',
        '2' => '/assets/png/jalwa-avatar-02.png',
        '3' => '/assets/png/jalwa-avatar-03.png',
        '4' => '/assets/png/jalwa-avatar-04.png',
        '5' => '/assets/png/jalwa-avatar-05.png',
        '6' => '/assets/png/jalwa-avatar-06.png',
        '7' => '/assets/png/jalwa-avatar-07.png',
        '8' => '/assets/png/jalwa-avatar-08.png',
        '9' => '/assets/png/jalwa-avatar-09.png',
        '10' => '/assets/png/jalwa-avatar-10.png',
        '11' => '/assets/png/jalwa-avatar-11.png',
        '12' => '/assets/png/jalwa-avatar-12.png',
        '13' => '/assets/png/jalwa-avatar-13.png',
        '14' => '/assets/png/jalwa-avatar-14.png',
        '15' => '/assets/png/jalwa-avatar-15.png',
        '16' => '/assets/png/jalwa-avatar-16.png',
        '17' => '/assets/png/jalwa-avatar-17.png'
    ];
    $storedPhoto = $allowed[$userPhoto] ?? (str_starts_with($userPhoto, '/') ? $userPhoto : '');
    if ($storedPhoto === '') {
        photo_response(['code' => 7, 'msg' => 'Invalid image', 'msgCode' => 6]);
    }
}

$create = "CREATE TABLE IF NOT EXISTS jalwa_user_profiles (
    user_id INT UNSIGNED NOT NULL PRIMARY KEY,
    avatar_data MEDIUMTEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($create)) {
    photo_response(['code' => 9, 'msg' => 'Database error', 'msgCode' => 9], 500);
}
$save = $conn->prepare('INSERT INTO jalwa_user_profiles (user_id, avatar_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE avatar_data = VALUES(avatar_data)');
$save->bind_param('is', $user['id'], $storedPhoto);
$ok = $save->execute();
$save->close();
photo_response($ok ? ['code' => 0, 'msg' => 'Succeed', 'msgCode' => 0] : ['code' => 9, 'msg' => 'Unable to save image', 'msgCode' => 9], $ok ? 200 : 500);
