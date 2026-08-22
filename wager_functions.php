<?php
/**
 * Shared UID-based manual wager adjustment and requirement helpers.
 *
 * The website and admin panel must use the same formula:
 * completed deposits + investments + manual adjustment - total bets.
 */
function ensure_wager_adjustments_table(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS wager_adjustments (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        userid INT NOT NULL,
        delta DECIMAL(18,2) NOT NULL,
        operation ENUM('add','remove') NOT NULL,
        note VARCHAR(255) NOT NULL DEFAULT '',
        admin_session VARCHAR(120) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wager_user_created (userid, created_at),
        CONSTRAINT chk_wager_delta_nonzero CHECK (delta <> 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return (bool) $conn->query($sql);
}

function get_wager_adjustment(mysqli $conn, int $userid): float
{
    if (!ensure_wager_adjustments_table($conn)) {
        return 0.0;
    }

    $stmt = $conn->prepare('SELECT COALESCE(SUM(delta), 0) AS adjustment FROM wager_adjustments WHERE userid = ?');
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return max(0.0, (float) ($row['adjustment'] ?? 0));
}

function wager_sum(mysqli $conn, string $table, string $userColumn, string $amountColumn, int $userid, string $extraWhere = ''): float
{
    // Table and column names come only from the fixed internal lists below.
    $sql = "SELECT COALESCE(SUM(`{$amountColumn}`), 0) AS total FROM `{$table}` WHERE `{$userColumn}` = ?" . $extraWhere;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('i', $userid);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0.0;
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float) ($row['total'] ?? 0);
}

function get_wager_summary(mysqli $conn, int $userid): array
{
    $completedDeposits = wager_sum($conn, 'thevani', 'balakedara', 'motta', $userid, " AND `sthiti` = '1'");
    $investments = wager_sum($conn, 'hodike_balakedara', 'userkani', 'price', $userid);

    $betTables = [
        'bajikattuttate',
        'bajikattuttate_trx',
        'bajikattuttate_trx3',
        'bajikattuttate_trx5',
        'bajikattuttate_trx10',
        'bajikattuttate_drei',
        'bajikattuttate_funf',
        'bajikattuttate_zehn',
        'bajikattuttate_kemuru',
        'bajikattuttate_kemuru_drei',
        'bajikattuttate_kemuru_funf',
        'bajikattuttate_kemuru_zehn',
        'bajikattuttate_aidudi',
        'bajikattuttate_aidudi_drei',
        'bajikattuttate_aidudi_funf',
        'bajikattuttate_aidudi_zehn',
    ];
    $totalBets = 0.0;
    foreach ($betTables as $table) {
        $totalBets += wager_sum($conn, $table, 'byabaharkarta', 'ketebida', $userid);
    }

    $manualAdjustment = get_wager_adjustment($conn, $userid);
    $normalRequired = max(0.0, $completedDeposits + $investments - $totalBets);
    $required = max(0.0, $completedDeposits + $investments + $manualAdjustment - $totalBets);

    return [
        'completedDeposits' => $completedDeposits,
        'investments' => $investments,
        'totalBets' => $totalBets,
        'manualAdjustment' => $manualAdjustment,
        'normalRequired' => $normalRequired,
        'required' => $required,
    ];
}

function find_wager_user(mysqli $conn, int $userid): ?array
{
    $stmt = $conn->prepare('SELECT id, mobile, owncode, code, createdate, status FROM shonu_subjects WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function add_wager_adjustment(mysqli $conn, int $userid, float $amount, string $operation, string $adminSession, string $note = ''): array
{
    if (!ensure_wager_adjustments_table($conn)) {
        return ['ok' => false, 'message' => 'Unable to prepare wager storage'];
    }

    if ($amount <= 0 || $amount > 1000000000) {
        return ['ok' => false, 'message' => 'Amount must be greater than 0 and no more than 1,000,000,000'];
    }

    if (!in_array($operation, ['add', 'remove'], true)) {
        return ['ok' => false, 'message' => 'Invalid wager operation'];
    }

    if (!find_wager_user($conn, $userid)) {
        return ['ok' => false, 'message' => 'UID not found'];
    }

    $current = get_wager_adjustment($conn, $userid);
    $delta = $operation === 'add' ? $amount : -$amount;
    $next = $current + $delta;
    if ($next < 0) {
        return ['ok' => false, 'message' => 'Remove amount cannot exceed the current manual wager adjustment', 'current' => $current];
    }

    $note = trim(substr($note, 0, 255));
    $adminSession = substr($adminSession, 0, 120);
    $stmt = $conn->prepare('INSERT INTO wager_adjustments (userid, delta, operation, note, admin_session) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        return ['ok' => false, 'message' => 'Unable to save wager adjustment'];
    }
    $stmt->bind_param('idsss', $userid, $delta, $operation, $note, $adminSession);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok
        ? ['ok' => true, 'message' => ucfirst($operation) . ' wager saved', 'current' => $next]
        : ['ok' => false, 'message' => 'Unable to save wager adjustment'];
}
?>
