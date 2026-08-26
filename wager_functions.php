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

function ensure_wager_waivers_tables(mysqli $conn): bool
{
    $currentSql = "CREATE TABLE IF NOT EXISTS wager_waivers (
        userid INT NOT NULL,
        waived_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        updated_by VARCHAR(120) NOT NULL DEFAULT '',
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (userid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $historySql = "CREATE TABLE IF NOT EXISTS wager_waiver_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        userid INT NOT NULL,
        amount DECIMAL(18,2) NOT NULL,
        note VARCHAR(255) NOT NULL DEFAULT '',
        admin_session VARCHAR(120) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wager_waiver_user_created (userid, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return (bool) $conn->query($currentSql) && (bool) $conn->query($historySql);
}

function ensure_wager_clear_tables(mysqli $conn): bool
{
    $currentSql = "CREATE TABLE IF NOT EXISTS wager_clear_state (
        userid INT NOT NULL,
        normal_cleared_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        manual_cleared_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        updated_by VARCHAR(120) NOT NULL DEFAULT '',
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (userid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $historySql = "CREATE TABLE IF NOT EXISTS wager_clear_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        userid INT NOT NULL,
        normal_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        manual_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
        note VARCHAR(255) NOT NULL DEFAULT '',
        admin_session VARCHAR(120) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wager_clear_user_created (userid, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    return (bool) $conn->query($currentSql) && (bool) $conn->query($historySql);
}

function get_wager_waived_amount(mysqli $conn, int $userid): float
{
    if (!ensure_wager_waivers_tables($conn)) {
        return 0.0;
    }
    $stmt = $conn->prepare('SELECT waived_amount FROM wager_waivers WHERE userid = ? LIMIT 1');
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return max(0.0, (float) ($row['waived_amount'] ?? 0));
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

function get_wager_clear_state(mysqli $conn, int $userid): array
{
    if (!ensure_wager_clear_tables($conn)) {
        return ['normalClearedAmount' => 0.0, 'manualClearedAmount' => 0.0];
    }
    $stmt = $conn->prepare('SELECT normal_cleared_amount, manual_cleared_amount FROM wager_clear_state WHERE userid = ? LIMIT 1');
    if (!$stmt) {
        return ['normalClearedAmount' => 0.0, 'manualClearedAmount' => 0.0];
    }
    $stmt->bind_param('i', $userid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'normalClearedAmount' => max(0.0, (float) ($row['normal_cleared_amount'] ?? 0)),
        'manualClearedAmount' => max(0.0, (float) ($row['manual_cleared_amount'] ?? 0)),
    ];
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
    $waivedAmount = get_wager_waived_amount($conn, $userid);
    $clearState = get_wager_clear_state($conn, $userid);
    $normalRequired = max(0.0, $completedDeposits + $investments - $totalBets);
    $waivedNormalRequired = max(0.0, $normalRequired - $waivedAmount);
    $remainingNormalRequired = max(0.0, $waivedNormalRequired - $clearState['normalClearedAmount']);
    $remainingManualAdjustment = max(0.0, $manualAdjustment - $clearState['manualClearedAmount']);
    $required = max(0.0, $remainingNormalRequired + $remainingManualAdjustment);

    return [
        'completedDeposits' => $completedDeposits,
        'investments' => $investments,
        'totalBets' => $totalBets,
        'manualAdjustment' => $manualAdjustment,
        'waivedAmount' => $waivedAmount,
        'normalRequired' => $normalRequired,
        'waivedNormalRequired' => $waivedNormalRequired,
        'remainingNormalRequired' => $remainingNormalRequired,
        'remainingManualAdjustment' => $remainingManualAdjustment,
        'normalClearedAmount' => $clearState['normalClearedAmount'],
        'manualClearedAmount' => $clearState['manualClearedAmount'],
        'required' => $required,
    ];
}

function waive_current_deposit_wager(mysqli $conn, int $userid, string $adminSession, string $note = ''): array
{
    if (!ensure_wager_waivers_tables($conn)) {
        return ['ok' => false, 'message' => 'Unable to prepare wager waiver storage'];
    }
    if (!find_wager_user($conn, $userid)) {
        return ['ok' => false, 'message' => 'UID not found'];
    }

    $summary = get_wager_summary($conn, $userid);
    $amount = max(0.0, (float) $summary['normalRequired'] - (float) $summary['waivedAmount'] - (float) $summary['normalClearedAmount']);
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'No unwaived deposit wager remains for this UID'];
    }

    $nextWaived = (float) $summary['waivedAmount'] + $amount;
    $note = trim(substr($note, 0, 255));
    $adminSession = substr($adminSession, 0, 120);
    $conn->begin_transaction();
    try {
        $currentStmt = $conn->prepare('INSERT INTO wager_waivers (userid, waived_amount, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE waived_amount = VALUES(waived_amount), updated_by = VALUES(updated_by)');
        if (!$currentStmt) {
            throw new RuntimeException('Unable to save wager waiver');
        }
        $currentStmt->bind_param('ids', $userid, $nextWaived, $adminSession);
        if (!$currentStmt->execute()) {
            $currentStmt->close();
            throw new RuntimeException('Unable to save wager waiver');
        }
        $currentStmt->close();

        $historyStmt = $conn->prepare('INSERT INTO wager_waiver_history (userid, amount, note, admin_session) VALUES (?, ?, ?, ?)');
        if (!$historyStmt) {
            throw new RuntimeException('Unable to save wager waiver history');
        }
        $historyStmt->bind_param('idss', $userid, $amount, $note, $adminSession);
        if (!$historyStmt->execute()) {
            $historyStmt->close();
            throw new RuntimeException('Unable to save wager waiver history');
        }
        $historyStmt->close();
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        return ['ok' => false, 'message' => $error->getMessage() ?: 'Unable to save wager waiver'];
    }

    $updatedSummary = get_wager_summary($conn, $userid);
    return [
        'ok' => true,
        'message' => 'Deposit-derived wager waived',
        'waivedAmount' => number_format($updatedSummary['waivedAmount'], 2, '.', ''),
        'requiredWager' => number_format($updatedSummary['required'], 2, '.', ''),
    ];
}

function clear_all_wager(mysqli $conn, int $userid, string $adminSession, string $note = ''): array
{
    if (!ensure_wager_clear_tables($conn)) {
        return ['ok' => false, 'message' => 'Unable to prepare clear-all wager storage'];
    }
    if (!find_wager_user($conn, $userid)) {
        return ['ok' => false, 'message' => 'UID not found'];
    }

    $summary = get_wager_summary($conn, $userid);
    $normalAmount = max(0.0, (float) $summary['remainingNormalRequired']);
    $manualAmount = max(0.0, (float) $summary['remainingManualAdjustment']);
    $totalAmount = $normalAmount + $manualAmount;
    if ($totalAmount <= 0) {
        return ['ok' => false, 'message' => 'No remaining wager exists for this UID'];
    }

    $state = get_wager_clear_state($conn, $userid);
    $nextNormal = $state['normalClearedAmount'] + $normalAmount;
    $nextManual = $state['manualClearedAmount'] + $manualAmount;
    $note = trim(substr($note, 0, 255));
    $adminSession = substr($adminSession, 0, 120);

    $conn->begin_transaction();
    try {
        $currentStmt = $conn->prepare('INSERT INTO wager_clear_state (userid, normal_cleared_amount, manual_cleared_amount, updated_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE normal_cleared_amount = VALUES(normal_cleared_amount), manual_cleared_amount = VALUES(manual_cleared_amount), updated_by = VALUES(updated_by)');
        if (!$currentStmt) {
            throw new RuntimeException('Unable to save clear-all wager state');
        }
        $currentStmt->bind_param('idds', $userid, $nextNormal, $nextManual, $adminSession);
        if (!$currentStmt->execute()) {
            $currentStmt->close();
            throw new RuntimeException('Unable to save clear-all wager state');
        }
        $currentStmt->close();

        $historyStmt = $conn->prepare('INSERT INTO wager_clear_history (userid, normal_amount, manual_amount, total_amount, note, admin_session) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$historyStmt) {
            throw new RuntimeException('Unable to save clear-all wager history');
        }
        $historyStmt->bind_param('idddss', $userid, $normalAmount, $manualAmount, $totalAmount, $note, $adminSession);
        if (!$historyStmt->execute()) {
            $historyStmt->close();
            throw new RuntimeException('Unable to save clear-all wager history');
        }
        $historyStmt->close();
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        return ['ok' => false, 'message' => $error->getMessage() ?: 'Unable to clear all wager'];
    }

    $updatedSummary = get_wager_summary($conn, $userid);
    return [
        'ok' => true,
        'message' => 'All remaining wager cleared',
        'clearedAmount' => number_format($totalAmount, 2, '.', ''),
        'requiredWager' => number_format($updatedSummary['required'], 2, '.', ''),
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
