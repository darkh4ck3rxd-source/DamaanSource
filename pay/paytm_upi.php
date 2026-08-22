<?php
require_once __DIR__ . '/../serive/samparka.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function paytm_page_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function paytm_setting(mysqli $conn, string $key, string $default = ''): string {
    $stmt = $conn->prepare('SELECT setting_value FROM jalwa_payment_settings WHERE setting_key = ? LIMIT 1');
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return isset($row['setting_value']) ? (string)$row['setting_value'] : $default;
}

$conn->query("CREATE TABLE IF NOT EXISTS jalwa_payment_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$uid = (int)($_GET['uid'] ?? 0);
$amount = (float)($_GET['amount'] ?? 0);
$clientSign = strtoupper(trim((string)($_GET['sign'] ?? '')));
$error = '';
$orderId = '';
$paytmUrl = '';
$upiUrl = '';

$user = null;
if ($uid > 0) {
    $userStmt = $conn->prepare('SELECT id, mobile, codechorkamukala, createdate FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
    if ($userStmt) {
        $userStmt->bind_param('i', $uid);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
    }
}
if (!$user) {
    $error = 'Your user session is invalid. Please return to the app and try again.';
} else {
    $avatar = '/assets/png/jalwa-avatar-01.png';
    $avatarStmt = $conn->prepare('SELECT avatar_data FROM jalwa_user_profiles WHERE user_id = ? LIMIT 1');
    if ($avatarStmt) {
        $avatarStmt->bind_param('i', $uid);
        $avatarStmt->execute();
        $avatarRow = $avatarStmt->get_result()->fetch_assoc();
        if (!empty($avatarRow['avatar_data'])) {
            $avatar = (string)$avatarRow['avatar_data'];
        }
        $avatarStmt->close();
    }
    $nickname = (string)($user['codechorkamukala'] ?? ('Member' . $uid));
    $createdate = (string)($user['createdate'] ?? '');
    $expectedSign = strtoupper(hash('sha256', '{"userId":' . $uid . ',"userPhoto":"' . $avatar . '","userName":91' . $user['mobile'] . ',"nickName":"' . $nickname . '","createdate":"' . $createdate . '"}'));
    if ($clientSign === '' || !hash_equals($expectedSign, $clientSign)) {
        $error = 'The payment link is invalid or expired. Please return to Deposit and try again.';
    }
}

$minimum = max(1, (float)paytm_setting($conn, 'paytm_min_amount', '200'));
$maximum = 50000.0;
$payeeVpa = trim(paytm_setting($conn, 'paytm_upi_id'));
if ($payeeVpa === '') {
    $payeeVpa = trim(paytm_setting($conn, 'wake_upi_id'));
}
$payeeName = trim(paytm_setting($conn, 'paytm_upi_name', 'Jalwa')) ?: 'Jalwa';

if ($error === '' && ($amount < $minimum || $amount > $maximum)) {
    $error = 'Deposit amount must be between ' . number_format($minimum, 2) . ' and ' . number_format($maximum, 2) . ' INR.';
}
if ($error === '' && $payeeVpa === '') {
    $error = 'Paytm UPI ID is not configured yet. Please contact support.';
}

if ($error === '') {
    $orderId = 'JALWA' . date('ymdHis') . random_int(100000, 999999);
    $createdAt = date('Y-m-d H:i:s');
    $amountText = number_format($amount, 2, '.', '');
    $payName = 'PAYTM_UPI';
    $method = 'UPI';
    $insert = $conn->prepare("INSERT INTO thevani (payid, balakedara, motta, dharavahi, mula, ullekha, duravani, ekikrtapavati, dinankavannuracisi, madari, pavatiaidi, sthiti) VALUES ('1', ?, ?, ?, ?, ?, ?, ?, ?, '1005', '2', '0')");
    if (!$insert) {
        $error = 'Could not create the Paytm order. Please try again.';
    } else {
        $insert->bind_param('idssssss', $uid, $amount, $orderId, $payName, $orderId, $user['mobile'], $method, $createdAt);
        if (!$insert->execute()) {
            $error = 'Could not create the Paytm order. Please try again.';
        }
        $insert->close();
    }
    if ($error === '') {
        $remark = 'Order ID ' . $orderId;
        $paytmUrl = 'paytmmp://pay?pa=' . rawurlencode($payeeVpa) . '&pn=' . rawurlencode($payeeName) . '&am=' . rawurlencode($amountText) . '&cu=INR&tn=' . rawurlencode($remark) . '&tr=' . rawurlencode($orderId);
        $upiUrl = 'upi://pay?pa=' . rawurlencode($payeeVpa) . '&pn=' . rawurlencode($payeeName) . '&am=' . rawurlencode($amountText) . '&cu=INR&tn=' . rawurlencode($remark) . '&tr=' . rawurlencode($orderId);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Paytm UPI</title>
  <style>
    :root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,sans-serif;background:#070533;color:#f5f7ff}.wrap{max-width:520px;margin:0 auto;padding:28px 18px}.card{background:#071c54;border-radius:18px;padding:24px;margin:16px 0;box-shadow:0 12px 30px rgba(0,0,0,.22)}h1{font-size:25px;margin:0 0 10px}.muted{color:#aeb8de;line-height:1.5}.amount{font-size:34px;font-weight:800;color:#6ff5df;margin:18px 0}.order{background:#04032a;border-radius:10px;padding:14px;word-break:break-all;color:#dbe6ff}.label{color:#9fa9d0;font-size:13px;margin:14px 0 6px}.button{display:block;text-align:center;text-decoration:none;border:0;border-radius:12px;padding:15px;font-size:17px;font-weight:700;background:#19d9c1;color:#03113c;margin-top:14px}.secondary{background:#314275;color:#fff}.error{background:#632b3e;color:#ffd8df;border-radius:10px;padding:14px;line-height:1.45}.back{color:#6ff5df;text-decoration:none;font-size:15px}
  </style>
</head>
<body>
  <main class="wrap">
    <a class="back" href="javascript:history.back()">← Back to Deposit</a>
    <?php if ($error !== ''): ?><div class="card"><h1>Paytm UPI</h1><div class="error"><?= paytm_page_escape($error) ?></div></div><?php else: ?>
    <div class="card">
      <h1>Opening Paytm</h1>
      <p class="muted">Your Paytm payment is ready. The amount is fixed and the order ID is already added in the payment remark.</p>
      <div class="amount">₹<?= paytm_page_escape(number_format($amount, 2)) ?></div>
      <div class="label">Order ID / Remark</div><div class="order"><?= paytm_page_escape($orderId) ?></div>
      <a id="paytmButton" class="button" href="<?= paytm_page_escape($paytmUrl) ?>">Open Paytm</a>
      <a class="button secondary" href="<?= paytm_page_escape($upiUrl) ?>">Open another UPI app</a>
    </div>
    <script>
      setTimeout(function () { window.location.href = <?= json_encode($paytmUrl, JSON_UNESCAPED_SLASHES) ?>; }, 350);
    </script>
    <?php endif; ?>
  </main>
</body>
</html>
