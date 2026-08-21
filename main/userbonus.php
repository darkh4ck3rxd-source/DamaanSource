<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}

require_once __DIR__ . '/conn.php';

$bonusTypes = [
    3   => 'Red envelope',
    8   => 'Agent red envelope recharge',
    10  => 'Recharge gift',
    13  => 'Bonus recharge',
    14  => 'First full gift',
    20  => 'Invite bonus',
    25  => 'Card binding gift',
    107 => 'Weekly Awards',
    124 => 'Agent Bonus',
    118 => 'Daily Awards',
    117 => 'New members get bonuses by playing games',
    115 => 'Return Awards',
];

function addBonus(int $userId, int $type, float $amount, string $remark, mysqli $conn, array $bonusTypes): string
{
    if (!isset($bonusTypes[$type])) {
        return 'Invalid bonus type.';
    }
    if ($userId <= 0 || $amount <= 0) {
        return 'Please provide a valid user ID and bonus amount.';
    }

    $userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
    if (!$userStmt) {
        return 'Could not validate the user.';
    }
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userExists = (bool)$userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    if (!$userExists) {
        return 'Active user was not found for this ID.';
    }

    $walletStmt = $conn->prepare('UPDATE shonu_kaichila SET motta = CAST(motta AS DECIMAL(20,2)) + ? WHERE balakedara = ?');
    $ledgerStmt = $conn->prepare('INSERT INTO hodike_balakedara (userkani, serial, price, shonu, remark) VALUES (?, ?, ?, ?, ?)');
    if (!$walletStmt || !$ledgerStmt) {
        if ($walletStmt) {
            $walletStmt->close();
        }
        if ($ledgerStmt) {
            $ledgerStmt->close();
        }
        return 'Bonus service is temporarily unavailable.';
    }

    $date = date('Y-m-d H:i:s');
    $serial = 'ADMIN_BONUS_' . date('YmdHis') . '_' . random_int(100000, 999999);
    $ledgerRemark = $bonusTypes[$type] . ($remark !== '' ? ' - ' . $remark : '');
    $amountText = number_format($amount, 2, '.', '');

    try {
        $conn->begin_transaction();

        $walletStmt->bind_param('di', $amount, $userId);
        if (!$walletStmt->execute() || $walletStmt->affected_rows !== 1) {
            throw new RuntimeException('wallet');
        }

        $ledgerStmt->bind_param('issss', $userId, $serial, $amountText, $date, $ledgerRemark);
        if (!$ledgerStmt->execute()) {
            throw new RuntimeException('ledger');
        }

        $conn->commit();
        $walletStmt->close();
        $ledgerStmt->close();
        return 'Bonus of ' . $amountText . ' successfully credited to user ' . $userId . '.';
    } catch (Throwable $e) {
        $conn->rollback();
        $walletStmt->close();
        $ledgerStmt->close();
        return 'Could not assign the bonus. No balance was changed.';
    }
}

$resultMessage = '';
$resultClass = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $type = (int)($_POST['type'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $remark = trim((string)($_POST['remark'] ?? ''));
    $resultMessage = addBonus($userId, $type, $amount, $remark, $conn, $bonusTypes);
    if (str_starts_with($resultMessage, 'Could not') || str_starts_with($resultMessage, 'Invalid') || str_starts_with($resultMessage, 'Please') || str_starts_with($resultMessage, 'Active')) {
        $resultClass = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Bonus</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f9f9f9; margin:0; padding:0; }
        .container { max-width:600px; margin:50px auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,.1); }
        h1 { text-align:center; color:#333; }
        label { display:block; margin-bottom:8px; font-weight:bold; color:#555; }
        input, select, textarea, button { width:100%; padding:10px; margin-bottom:20px; border:1px solid #ddd; border-radius:5px; font-size:16px; box-sizing:border-box; }
        button { background:#007bff; color:#fff; border:none; cursor:pointer; transition:background-color .3s; }
        button:hover { background:#0056b3; }
        .result { text-align:center; padding:10px; border-radius:5px; margin-bottom:20px; font-weight:bold; }
        .result.error { background:#f8d7da; color:#721c24; }
        .result.success { background:#d4edda; color:#155724; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Assign Bonus to User</h1>
        <?php if ($resultMessage !== ''): ?><div class="result <?= htmlspecialchars($resultClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resultMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="POST">
            <label for="user_id">User ID:</label>
            <input type="number" name="user_id" id="user_id" min="1" required>

            <label for="type">Bonus Type:</label>
            <select name="type" id="type" required>
                <?php foreach ($bonusTypes as $typeId => $typeName): ?>
                    <option value="<?= (int)$typeId ?>"><?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="amount">Bonus Amount:</label>
            <input type="number" step="0.01" name="amount" id="amount" min="0.01" required>

            <label for="remark">Remark:</label>
            <textarea name="remark" id="remark" rows="4" placeholder="Add a remark (optional)"></textarea>

            <button type="submit">Assign Bonus</button>
        </form>
    </div>
</body>
</html>
