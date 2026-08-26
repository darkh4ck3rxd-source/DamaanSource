<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/../wager_functions.php';

$creditTypeOptions = [
    0 => ['name' => 'Bet amount reduced', 'code' => '8000'], 1 => ['name' => 'Salary', 'code' => '8001'],
    2 => ['name' => 'Jackpot increase', 'code' => '8002'], 3 => ['name' => 'Red envelope', 'code' => '8003'],
    4 => ['name' => 'Recharge increase', 'code' => '8004'], 5 => ['name' => 'Withdrawal reduction', 'code' => '8005'],
    6 => ['name' => 'Cash back', 'code' => '8006'], 7 => ['name' => 'Daily check-in', 'code' => '8007'],
    8 => ['name' => 'Agent red envelope recharge', 'code' => '8008'], 9 => ['name' => 'Withdrawal rejected', 'code' => '8009'],
    10 => ['name' => 'Recharge gift', 'code' => '8010'], 11 => ['name' => 'Manual recharge', 'code' => '8011'],
    12 => ['name' => 'Sign up to send money', 'code' => '8012'], 13 => ['name' => 'Bonus recharge', 'code' => '8013'],
    14 => ['name' => 'First full gift', 'code' => '8014'], 15 => ['name' => 'First charge rebate', 'code' => '8015'],
    16 => ['name' => 'Investment and financial management', 'code' => '8016'], 17 => ['name' => 'Financial income', 'code' => '8017'],
    18 => ['name' => 'Financial principal', 'code' => '8018'], 19 => ['name' => 'Redemption principal', 'code' => '8019'],
    20 => ['name' => 'Invite bonus', 'code' => '8020'], 21 => ['name' => 'Game transfer in', 'code' => '8021'],
    22 => ['name' => 'Game transfer out', 'code' => '8022'], 24 => ['name' => 'Jackpot increase', 'code' => '8024'],
    25 => ['name' => 'Card binding gift', 'code' => '8025'], 26 => ['name' => 'Game money refund', 'code' => '8026'],
    27 => ['name' => 'Usdt recharge', 'code' => '8027'], 28 => ['name' => 'Betting rebate', 'code' => '8028'],
    29 => ['name' => 'Vip member upgrade package', 'code' => '8029'], 30 => ['name' => 'Monthly rewards for VIP members', 'code' => '8030'],
    31 => ['name' => 'Recharge Rewards for VIP Members', 'code' => '8031'], 100 => ['name' => 'Bonus deduction', 'code' => '8100'],
    101 => ['name' => 'Manual withdrawal', 'code' => '8101'], 102 => ['name' => 'One key wash code reverse water', 'code' => '8102'],
    103 => ['name' => 'Electronic Awards', 'code' => '8103'], 104 => ['name' => 'Bind Mobile Awards', 'code' => '8104'],
    105 => ['name' => 'XOSO Issue Canceled', 'code' => '8105'], 106 => ['name' => 'Bind Email Awards', 'code' => '8106'],
    107 => ['name' => 'Weekly Awards', 'code' => '8107'], 108 => ['name' => 'C2C Withdraw Awards', 'code' => '8108'],
    109 => ['name' => 'C2C Withdraw', 'code' => '8109'], 110 => ['name' => 'C2C Withdraw Back', 'code' => '8110'],
    111 => ['name' => 'C2C Recharge', 'code' => '8111'], 112 => ['name' => 'C2C Recharge Awards', 'code' => '8112'],
    113 => ['name' => 'Newbie gift pack', 'code' => '8113'], 114 => ['name' => 'Tournament Rewards', 'code' => '8114'],
    115 => ['name' => 'Return Awards', 'code' => '8115'], 116 => ['name' => 'New member first recharge reward', 'code' => '8116'],
    117 => ['name' => 'New members game bonus', 'code' => '8117'], 118 => ['name' => 'Daily Awards', 'code' => '8118'],
    119 => ['name' => 'Turntable Awards', 'code' => '8119'], 122 => ['name' => 'Partner Rewards', 'code' => '8122'],
    123 => ['name' => 'Issue Canceled', 'code' => '8123'], 124 => ['name' => 'Balance Credit', 'code' => '8124'],
];

$conn->query("CREATE TABLE IF NOT EXISTS admin_balance_credits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    wager_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
    note VARCHAR(255) NOT NULL DEFAULT '',
    admin_session VARCHAR(120) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_balance_credit_user_created (userid, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$conn->query("ALTER TABLE admin_balance_credits ADD COLUMN IF NOT EXISTS transaction_type INT NOT NULL DEFAULT 124 AFTER wager_amount");
$conn->query("ALTER TABLE admin_balance_credits ADD COLUMN IF NOT EXISTS transaction_type_code VARCHAR(10) NOT NULL DEFAULT '8124' AFTER transaction_type");

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = filter_var($_POST['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $wagerRaw = trim((string)($_POST['wager_amount'] ?? '0'));
    $transactionType = filter_var($_POST['transaction_type'] ?? 124, FILTER_VALIDATE_INT);
    $note = trim(substr((string)($_POST['note'] ?? ''), 0, 255));

    if (!$uid || !preg_match('/^\d+(?:\.\d{1,2})?$/', $amountRaw) || (float)$amountRaw <= 0) {
        $message = 'Enter a valid balance amount greater than 0.';
        $messageType = 'danger';
    } elseif (!isset($creditTypeOptions[$transactionType])) {
        $message = 'Select a valid transaction type.';
        $messageType = 'danger';
    } elseif (!preg_match('/^\d+(?:\.\d{1,2})?$/', $wagerRaw) || (float)$wagerRaw < 0) {
        $message = 'Enter a valid wager amount (0 or greater).';
        $messageType = 'danger';
    } else {
        $amount = (float)$amountRaw;
        $wagerAmount = (float)$wagerRaw;
        if ($amount > 1000000000 || $wagerAmount > 1000000000) {
            $message = 'Amount is above the permitted limit.';
            $messageType = 'danger';
        } else {
            $conn->begin_transaction();
            try {
                $userStmt = $conn->prepare('SELECT id FROM shonu_subjects WHERE id = ? LIMIT 1 FOR UPDATE');
                if (!$userStmt) {
                    throw new RuntimeException('Unable to verify UID.');
                }
                $userStmt->bind_param('i', $uid);
                $userStmt->execute();
                $user = $userStmt->get_result()->fetch_assoc();
                $userStmt->close();
                if (!$user) {
                    throw new RuntimeException('UID not found.');
                }

                $walletStmt = $conn->prepare('UPDATE shonu_kaichila SET motta = motta + ? WHERE balakedara = ?');
                if (!$walletStmt) {
                    throw new RuntimeException('Unable to prepare wallet update.');
                }
                $walletStmt->bind_param('di', $amount, $uid);
                if (!$walletStmt->execute() || $walletStmt->affected_rows < 1) {
                    $walletStmt->close();
                    throw new RuntimeException('Wallet record not found; no credit applied.');
                }
                $walletStmt->close();

                if ($wagerAmount > 0) {
                    $wagerResult = add_wager_adjustment($conn, (int)$uid, $wagerAmount, 'add', (string)$_SESSION['unohs'], 'Balance credit: ' . $note);
                    if (!$wagerResult['ok']) {
                        throw new RuntimeException($wagerResult['message']);
                    }
                }

                $auditStmt = $conn->prepare('INSERT INTO admin_balance_credits (userid, amount, wager_amount, transaction_type, transaction_type_code, note, admin_session) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if (!$auditStmt) {
                    throw new RuntimeException('Unable to save credit audit.');
                }
                $adminSession = substr((string)$_SESSION['unohs'], 0, 120);
                $transactionTypeCode = $creditTypeOptions[$transactionType]['code'];
                $auditStmt->bind_param('iddisss', $uid, $amount, $wagerAmount, $transactionType, $transactionTypeCode, $note, $adminSession);
                if (!$auditStmt->execute()) {
                    $auditStmt->close();
                    throw new RuntimeException('Unable to save credit audit.');
                }
                $auditStmt->close();
                $conn->commit();
                $message = 'Balance credit saved with audit history.';
            } catch (Throwable $error) {
                $conn->rollback();
                $message = $error->getMessage() ?: 'Credit failed; no changes were saved.';
                $messageType = 'danger';
            }
        }
    }
}

$history = [];
$historyResult = $conn->query('SELECT bc.created_at, bc.userid, bc.amount, bc.wager_amount, bc.transaction_type, bc.transaction_type_code, bc.note, bc.admin_session, ss.mobile FROM admin_balance_credits bc LEFT JOIN shonu_subjects ss ON ss.id = bc.userid ORDER BY bc.id DESC LIMIT 50');
if ($historyResult) {
    while ($row = $historyResult->fetch_assoc()) {
        $history[] = $row;
    }
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>UID Balance Credit</title>
<link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css"><link rel="stylesheet" href="vendors/base/vendor.bundle.base.css"><link rel="stylesheet" href="css/style.css">
<style>
.credit-card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06)}.help{color:#6c757d}.table td,.table th{vertical-align:middle;white-space:nowrap}@media(max-width:576px){.credit-card{padding:16px}.table-responsive{font-size:12px}}
</style>
</head>
<body>
<div class="container-scroller"><nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row"><div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center"><a class="navbar-brand brand-logo" href="dashboard.php"><img src="images/logo.png" alt="logo"></a><a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="images/logo-mini.png" alt="logo"></a></div><div class="navbar-menu-wrapper d-flex align-items-center justify-content-end"><button class="navbar-toggler align-self-center" type="button" data-toggle="minimize"><span class="icon-menu"></span></button><ul class="navbar-nav navbar-nav-right"><li class="nav-item dropdown d-flex mr-4"><a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown"><i class="icon-cog"></i></a><div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown"><p class="mb-0 font-weight-normal float-left dropdown-header">Settings</p><a class="dropdown-item preview-item" href="logout.php"><i class="icon-inbox"></i> Logout</a></div></li></ul><button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas"><span class="icon-menu"></span></button></div></nav>
<div class="container-fluid page-body-wrapper"><nav class="sidebar sidebar-offcanvas" id="sidebar"><div class="user-profile"><div class="user-image"><img src="images/faces/face28.png" alt="admin"></div><div class="user-name">Rᴜᴅʀᴀɴsʜ</div><div class="user-designation">Admin</div></div><?php include __DIR__ . '/compass.php'; ?></nav>
<div class="main-panel"><div class="content-wrapper"><div class="row"><div class="col-sm-12 mb-4"><h4 class="font-weight-bold text-dark">UID Balance Credit</h4></div></div><div class="row"><div class="col-md-7 grid-margin stretch-card"><div class="credit-card w-100"><h5>Credit user balance</h5><p class="help">UID, balance amount, optional wager requirement and remark enter karo. Wallet balance change aur wager adjustment ek transaction me save honge.</p><?php if ($message !== ''): ?><div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?><form method="post" autocomplete="off"><div class="form-group"><label for="uid">User UID</label><input id="uid" name="uid" class="form-control" inputmode="numeric" required placeholder="e.g. 2257955"></div><div class="form-group"><label for="amount">Balance amount</label><input id="amount" name="amount" class="form-control" inputmode="decimal" required placeholder="e.g. 500"></div><div class="form-group"><label for="wager_amount">Wager amount</label><input id="wager_amount" name="wager_amount" class="form-control" inputmode="decimal" value="0" placeholder="e.g. 500"></div><div class="form-group"><label for="transaction_type">Transaction type</label><select id="transaction_type" name="transaction_type" class="form-control" required><?php foreach ($creditTypeOptions as $typeId => $typeOption): ?><option value="<?= (int)$typeId ?>"<?= $typeId === 124 ? ' selected' : '' ?>><?= e($typeOption['name']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="note">Remark</label><input id="note" name="note" maxlength="255" class="form-control" placeholder="Reason for this credit" required></div><button type="submit" class="btn btn-success">Credit Balance</button></form></div></div><div class="col-md-5 grid-margin stretch-card"><div class="credit-card w-100"><h5>Important</h5><p class="help mb-0">Ye action wallet balance ko increase karta hai. Har credit ka UID, amount, wager, remark, admin session aur time history me save hota hai. Testing ke liye real UID par submit mat karo.</p></div></div></div><div class="row"><div class="col-12 grid-margin stretch-card"><div class="credit-card w-100"><h5>Recent balance credits</h5><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>UID</th><th>Mobile</th><th>Amount</th><th>Wager</th><th>Type</th><th>Remark</th><th>Admin</th></tr></thead><tbody><?php if (!$history): ?><tr><td colspan="8" class="text-center">No credits yet</td></tr><?php else: foreach ($history as $row): ?><tr><td><?= e((string)$row['created_at']) ?></td><td><?= e((string)$row['userid']) ?></td><td><?= e((string)($row['mobile'] ?? '-')) ?></td><td>₹<?= e(number_format((float)$row['amount'], 2)) ?></td><td>₹<?= e(number_format((float)$row['wager_amount'], 2)) ?></td><td><?= e((string)($creditTypeOptions[(int)$row['transaction_type']]['name'] ?? 'Balance Credit')) ?></td><td><?= e((string)$row['note']) ?></td><td><?= e((string)$row['admin_session']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div></div></div><footer class="footer"><div class="d-sm-flex justify-content-center justify-content-sm-between"><span class="text-muted d-block text-center d-sm-inline-block">Copyright © Rᴜᴅʀᴀɴsʜ 2025</span></div></footer></div></div></div>
<script src="vendors/base/vendor.bundle.base.js"></script><script src="js/off-canvas.js"></script><script src="js/hoverable-collapse.js"></script><script src="js/template.js"></script>
</body></html>
