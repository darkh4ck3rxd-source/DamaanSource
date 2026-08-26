<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/../wager_functions.php';

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

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = filter_var($_POST['uid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $wagerRaw = trim((string)($_POST['wager_amount'] ?? '0'));
    $note = trim(substr((string)($_POST['note'] ?? ''), 0, 255));

    if (!$uid || !preg_match('/^\d+(?:\.\d{1,2})?$/', $amountRaw) || (float)$amountRaw <= 0) {
        $message = 'Enter a valid balance amount greater than 0.';
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

                $auditStmt = $conn->prepare('INSERT INTO admin_balance_credits (userid, amount, wager_amount, note, admin_session) VALUES (?, ?, ?, ?, ?)');
                if (!$auditStmt) {
                    throw new RuntimeException('Unable to save credit audit.');
                }
                $adminSession = substr((string)$_SESSION['unohs'], 0, 120);
                $auditStmt->bind_param('iddss', $uid, $amount, $wagerAmount, $note, $adminSession);
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
$historyResult = $conn->query('SELECT bc.created_at, bc.userid, bc.amount, bc.wager_amount, bc.note, bc.admin_session, ss.mobile FROM admin_balance_credits bc LEFT JOIN shonu_subjects ss ON ss.id = bc.userid ORDER BY bc.id DESC LIMIT 50');
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
<div class="main-panel"><div class="content-wrapper"><div class="row"><div class="col-sm-12 mb-4"><h4 class="font-weight-bold text-dark">UID Balance Credit</h4></div></div><div class="row"><div class="col-md-7 grid-margin stretch-card"><div class="credit-card w-100"><h5>Credit user balance</h5><p class="help">UID, balance amount, optional wager requirement and remark enter karo. Wallet balance change aur wager adjustment ek transaction me save honge.</p><?php if ($message !== ''): ?><div class="alert alert-<?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?><form method="post" autocomplete="off"><div class="form-group"><label for="uid">User UID</label><input id="uid" name="uid" class="form-control" inputmode="numeric" required placeholder="e.g. 2257955"></div><div class="form-group"><label for="amount">Balance amount</label><input id="amount" name="amount" class="form-control" inputmode="decimal" required placeholder="e.g. 500"></div><div class="form-group"><label for="wager_amount">Wager amount</label><input id="wager_amount" name="wager_amount" class="form-control" inputmode="decimal" value="0" placeholder="e.g. 500"></div><div class="form-group"><label for="note">Remark</label><input id="note" name="note" maxlength="255" class="form-control" placeholder="Reason for this credit" required></div><button type="submit" class="btn btn-success">Credit Balance</button></form></div></div><div class="col-md-5 grid-margin stretch-card"><div class="credit-card w-100"><h5>Important</h5><p class="help mb-0">Ye action wallet balance ko increase karta hai. Har credit ka UID, amount, wager, remark, admin session aur time history me save hota hai. Testing ke liye real UID par submit mat karo.</p></div></div></div><div class="row"><div class="col-12 grid-margin stretch-card"><div class="credit-card w-100"><h5>Recent balance credits</h5><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>UID</th><th>Mobile</th><th>Amount</th><th>Wager</th><th>Remark</th><th>Admin</th></tr></thead><tbody><?php if (!$history): ?><tr><td colspan="7" class="text-center">No credits yet</td></tr><?php else: foreach ($history as $row): ?><tr><td><?= e((string)$row['created_at']) ?></td><td><?= e((string)$row['userid']) ?></td><td><?= e((string)($row['mobile'] ?? '-')) ?></td><td>₹<?= e(number_format((float)$row['amount'], 2)) ?></td><td>₹<?= e(number_format((float)$row['wager_amount'], 2)) ?></td><td><?= e((string)$row['note']) ?></td><td><?= e((string)$row['admin_session']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div></div></div><footer class="footer"><div class="d-sm-flex justify-content-center justify-content-sm-between"><span class="text-muted d-block text-center d-sm-inline-block">Copyright © Rᴜᴅʀᴀɴsʜ 2025</span></div></footer></div></div></div>
<script src="vendors/base/vendor.bundle.base.js"></script><script src="js/off-canvas.js"></script><script src="js/hoverable-collapse.js"></script><script src="js/template.js"></script>
</body></html>
