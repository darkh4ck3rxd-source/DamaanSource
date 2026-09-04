<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['unohs'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['manage_user_csrf'] ?? '');
if ($sessionToken === '' || $csrfToken === '' || !hash_equals($sessionToken, $csrfToken)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Invalid request token']);
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$mobile = trim((string)($_POST['mobile'] ?? ''));

if (!$userId || !preg_match('/^[0-9]{7,15}$/', $mobile)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Enter a valid mobile number with 7 to 15 digits']);
    exit;
}

require_once __DIR__ . '/conn.php';

$duplicate = $conn->prepare('SELECT id FROM shonu_subjects WHERE mobile = ? AND id <> ? LIMIT 1');
if (!$duplicate) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not prepare validation query']);
    exit;
}
$duplicate->bind_param('si', $mobile, $userId);
$duplicate->execute();
$duplicate->store_result();
if ($duplicate->num_rows > 0) {
    $duplicate->close();
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'That mobile number is already assigned to another user']);
    exit;
}
$duplicate->close();

$update = $conn->prepare('UPDATE shonu_subjects SET mobile = ? WHERE id = ? LIMIT 1');
if (!$update) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not prepare update query']);
    exit;
}
$update->bind_param('si', $mobile, $userId);
if (!$update->execute()) {
    $update->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not update mobile number']);
    exit;
}
$changed = $update->affected_rows > 0;
$update->close();

if (!$changed) {
    echo json_encode(['ok' => true, 'message' => 'Mobile number is already up to date']);
    exit;
}

error_log(sprintf(
    'Admin %s updated mobile number for user ID %d',
    (string)($_SESSION['nirvahaka_hesaru'] ?? $_SESSION['unohs']),
    $userId
));

echo json_encode(['ok' => true, 'message' => 'Mobile number updated successfully']);
