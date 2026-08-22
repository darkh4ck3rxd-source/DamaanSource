<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function uid_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['unohs'])) {
    uid_json(['ok' => false, 'message' => 'Unauthorized'], 401);
}

require_once __DIR__ . '/conn.php';

function ensure_uid_audit_table(mysqli $conn): bool
{
    return (bool) $conn->query("CREATE TABLE IF NOT EXISTS uid_change_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        old_uid INT NOT NULL,
        new_uid INT NOT NULL,
        mobile VARCHAR(500) NOT NULL DEFAULT '',
        admin_session VARCHAR(120) NOT NULL DEFAULT '',
        changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_uid_change_old (old_uid),
        KEY idx_uid_change_new (new_uid),
        KEY idx_uid_change_date (changed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function uid_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $found = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $found;
}

function uid_references(): array
{
    return [
        ['agency_metric_override_history', 'user_id'],
        ['agency_metric_overrides', 'user_id'],
        ['amanatugolisu', 'byabaharkarta'],
        ['agent_red_envelope_recharge_table', 'userkani'],
        ['bajikattuttate', 'byabaharkarta'],
        ['bajikattuttate_aidudi', 'byabaharkarta'],
        ['bajikattuttate_aidudi_drei', 'byabaharkarta'],
        ['bajikattuttate_aidudi_funf', 'byabaharkarta'],
        ['bajikattuttate_aidudi_zehn', 'byabaharkarta'],
        ['bajikattuttate_drei', 'byabaharkarta'],
        ['bajikattuttate_funf', 'byabaharkarta'],
        ['bajikattuttate_kemuru', 'byabaharkarta'],
        ['bajikattuttate_kemuru_drei', 'byabaharkarta'],
        ['bajikattuttate_kemuru_funf', 'byabaharkarta'],
        ['bajikattuttate_kemuru_zehn', 'byabaharkarta'],
        ['bajikattuttate_trx', 'byabaharkarta'],
        ['bajikattuttate_trx10', 'byabaharkarta'],
        ['bajikattuttate_trx3', 'byabaharkarta'],
        ['bajikattuttate_trx5', 'byabaharkarta'],
        ['bajikattuttate_zehn', 'byabaharkarta'],
        ['banned_users', 'user_id'],
        ['commission', 'user_id'],
        ['dailysalary', 'userid'],
        ['demo', 'balakedara'],
        ['first_full_gift_table', 'userkani'],
        ['hintegedukolli', 'balakedara'],
        ['hodike_balakedara', 'userkani'],
        ['jalwa_user_profiles', 'user_id'],
        ['khate', 'byabaharkarta'],
        ['notification', 'user_id'],
        ['rebetrec', 'user_id'],
        ['shonu_kaichila', 'balakedara'],
        ['spinrec', 'user_id'],
        ['subordinate_metric_override_history', 'user_id'],
        ['subordinate_metric_overrides', 'user_id'],
        ['tb_agent', 'userid'],
        ['thevani', 'balakedara'],
        ['vip', 'userid'],
        ['viprec', 'user_id'],
        ['vyavahara', 'balakedara'],
        ['wager_adjustments', 'userid'],
        ['your_table', 'userid'],
    ];
}

function migrate_uid_references(mysqli $conn, int $oldUid, int $newUid): bool
{
    foreach (uid_references() as [$table, $column]) {
        if (!uid_table_exists($conn, $table)) {
            continue;
        }
        $sql = "UPDATE `{$table}` SET `{$column}` = ? WHERE `{$column}` = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $oldValue = (string) $oldUid;
        $newValue = (string) $newUid;
        $stmt->bind_param('ss', $newValue, $oldValue);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            return false;
        }
    }
    return true;
}

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));
$uid = filter_var($_POST['uid'] ?? $_GET['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);

if ($action === 'lookup') {
    if (!$uid) {
        uid_json(['ok' => false, 'message' => 'Enter a valid UID'], 422);
    }
    $stmt = $conn->prepare('SELECT id, mobile, owncode, code, status, createdate FROM shonu_subjects WHERE id = ? LIMIT 1');
    if (!$stmt) {
        uid_json(['ok' => false, 'message' => 'Unable to load user'], 500);
    }
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        uid_json(['ok' => false, 'message' => 'UID not found'], 404);
    }
    uid_json(['ok' => true, 'user' => $user]);
}

if ($action === 'update') {
    $oldUid = filter_var($_POST['old_uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    $newUid = filter_var($_POST['new_uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if (!$oldUid || !$newUid) {
        uid_json(['ok' => false, 'message' => 'Enter valid old and new UIDs'], 422);
    }
    if ($oldUid === $newUid) {
        uid_json(['ok' => false, 'message' => 'New UID must be different from the current UID'], 422);
    }
    if (!ensure_uid_audit_table($conn)) {
        uid_json(['ok' => false, 'message' => 'Unable to prepare UID audit storage'], 500);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, mobile, createdate FROM shonu_subjects WHERE id = ? LIMIT 1 FOR UPDATE');
        if (!$stmt) {
            throw new RuntimeException('Unable to lock current user');
        }
        $stmt->bind_param('i', $oldUid);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$current) {
            throw new RuntimeException('Current UID not found');
        }

        $stmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE id = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException('Unable to check new UID');
        }
        $stmt->bind_param('i', $newUid);
        $stmt->execute();
        $collision = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($collision) {
            throw new RuntimeException('New UID is already assigned to another user');
        }

        if (!migrate_uid_references($conn, $oldUid, $newUid)) {
            throw new RuntimeException('Unable to migrate linked user records');
        }

        $stmt = $conn->prepare('UPDATE shonu_subjects SET id = ?, akshinak = NULL, createdate = ? WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Unable to update UID');
        }
        $createdate = (string) ($current['createdate'] ?? '');
        $stmt->bind_param('isi', $newUid, $createdate, $oldUid);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Unable to update UID');
        }
        $stmt->close();

        $admin = substr((string) $_SESSION['unohs'], 0, 120);
        $mobile = (string) ($current['mobile'] ?? '');
        $stmt = $conn->prepare('INSERT INTO uid_change_history (old_uid, new_uid, mobile, admin_session) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            throw new RuntimeException('Unable to save UID audit record');
        }
        $stmt->bind_param('iiss', $oldUid, $newUid, $mobile, $admin);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Unable to save UID audit record');
        }
        $stmt->close();
        $conn->commit();
        uid_json(['ok' => true, 'message' => 'UID updated successfully. The user must log in again.', 'oldUid' => $oldUid, 'newUid' => $newUid]);
    } catch (Throwable $error) {
        $conn->rollback();
        uid_json(['ok' => false, 'message' => $error->getMessage()], 422);
    }
}

if ($action === 'history') {
    if (!ensure_uid_audit_table($conn)) {
        uid_json(['ok' => false, 'message' => 'Unable to load UID history'], 500);
    }
    $result = $conn->query('SELECT id, old_uid, new_uid, mobile, admin_session, changed_at FROM uid_change_history ORDER BY id DESC LIMIT 100');
    $rows = [];
    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }
    uid_json(['ok' => true, 'rows' => $rows]);
}

uid_json(['ok' => false, 'message' => 'Invalid action'], 400);
?>
