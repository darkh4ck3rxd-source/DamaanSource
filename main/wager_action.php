<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['unohs'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/../wager_functions.php';

function wager_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ensure_wager_adjustments_table($conn) || !ensure_wager_waivers_tables($conn)) {
    wager_json(['ok' => false, 'message' => 'Unable to prepare wager storage'], 500);
}

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));
$uid = filter_var($_POST['uid'] ?? $_GET['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($action === 'lookup') {
    if (!$uid) {
        wager_json(['ok' => false, 'message' => 'Enter a valid UID'], 422);
    }

    $user = find_wager_user($conn, (int) $uid);
    if (!$user) {
        wager_json(['ok' => false, 'message' => 'UID not found'], 404);
    }

    $summary = get_wager_summary($conn, (int) $uid);
    $user['currentManualAdjustment'] = number_format($summary['manualAdjustment'], 2, '.', '');
    $user['normalRequiredWager'] = number_format($summary['normalRequired'], 2, '.', '');
    $user['waivedDepositWager'] = number_format($summary['waivedAmount'], 2, '.', '');
    $user['waivedNormalRequiredWager'] = number_format($summary['waivedNormalRequired'], 2, '.', '');
    $user['currentRequiredWager'] = number_format($summary['required'], 2, '.', '');
    $user['completedDeposits'] = number_format($summary['completedDeposits'], 2, '.', '');
    $user['investments'] = number_format($summary['investments'], 2, '.', '');
    $user['totalBets'] = number_format($summary['totalBets'], 2, '.', '');
    wager_json(['ok' => true, 'user' => $user]);
}

if ($action === 'adjust') {
    if (!$uid) {
        wager_json(['ok' => false, 'message' => 'Enter a valid UID'], 422);
    }

    $amountRaw = trim((string) ($_POST['amount'] ?? ''));
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amountRaw)) {
        wager_json(['ok' => false, 'message' => 'Enter a valid amount with up to 2 decimals'], 422);
    }
    $amount = (float) $amountRaw;
    $operation = strtolower(trim((string) ($_POST['operation'] ?? '')));
    $note = trim((string) ($_POST['note'] ?? ''));

    $result = add_wager_adjustment(
        $conn,
        (int) $uid,
        $amount,
        $operation,
        (string) $_SESSION['unohs'],
        $note
    );
    if ($result['ok']) {
        $summary = get_wager_summary($conn, (int) $uid);
        $result['requiredWager'] = number_format($summary['required'], 2, '.', '');
        $result['manualAdjustment'] = number_format($summary['manualAdjustment'], 2, '.', '');
    }
    wager_json($result, $result['ok'] ? 200 : 422);
}

if ($action === 'waive_deposit') {
    if (!$uid) {
        wager_json(['ok' => false, 'message' => 'Enter a valid UID'], 422);
    }
    $note = trim(substr((string) ($_POST['note'] ?? ''), 0, 255));
    $result = waive_current_deposit_wager($conn, (int) $uid, (string) $_SESSION['unohs'], $note);
    wager_json($result, $result['ok'] ? 200 : 422);
}

if ($action === 'waiver_history') {
    $sql = "SELECT wh.id, wh.userid, wh.amount, wh.note, wh.admin_session, wh.created_at,
                   ss.mobile
            FROM wager_waiver_history wh
            LEFT JOIN shonu_subjects ss ON ss.id = wh.userid";
    $params = [];
    $types = '';
    if ($uid) {
        $sql .= ' WHERE wh.userid = ?';
        $params[] = (int) $uid;
        $types = 'i';
    }
    $sql .= ' ORDER BY wh.id DESC LIMIT 50';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        wager_json(['ok' => false, 'message' => 'Unable to load waiver history'], 500);
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $row['amount'] = number_format((float) $row['amount'], 2, '.', '');
        $rows[] = $row;
    }
    $stmt->close();
    wager_json(['ok' => true, 'rows' => $rows]);
}

if ($action === 'history') {
    $sql = "SELECT wa.id, wa.userid, wa.delta, wa.operation, wa.note, wa.admin_session, wa.created_at,
                   ss.mobile
            FROM wager_adjustments wa
            LEFT JOIN shonu_subjects ss ON ss.id = wa.userid";
    $params = [];
    $types = '';
    if ($uid) {
        $sql .= ' WHERE wa.userid = ?';
        $params[] = (int) $uid;
        $types = 'i';
    }
    $sql .= ' ORDER BY wa.id DESC LIMIT 50';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        wager_json(['ok' => false, 'message' => 'Unable to load wager history'], 500);
    }
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $row['delta'] = number_format((float) $row['delta'], 2, '.', '');
        $rows[] = $row;
    }
    $stmt->close();
    wager_json(['ok' => true, 'rows' => $rows]);
}

wager_json(['ok' => false, 'message' => 'Invalid action'], 400);
?>
