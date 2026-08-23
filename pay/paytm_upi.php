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
$statusMessage = '';
$orderId = '';
$paytmUrl = '';
$upiUrl = '';
$submitted = false;
$expiresAt = 0;
$isExpired = false;
$paymentConfirmed = $_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['payment_done'] ?? '') === '1';

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

$minimum = 100.0;
$maximum = 50000.0;
$payeeVpa = trim(paytm_setting($conn, 'paytm_upi_id'));
if ($payeeVpa === '') {
    $payeeVpa = trim(paytm_setting($conn, 'wake_upi_id'));
}
$payeeName = trim(paytm_setting($conn, 'paytm_upi_name', 'Jalwa')) ?: 'Jalwa';

if ($error === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim((string)($_POST['order_id'] ?? ''));
    $utr = trim((string)($_POST['utr'] ?? ''));
    if (!$paymentConfirmed || $orderId === '') {
        $error = 'Please confirm Payment Done before submitting the UTR.';
    } elseif (!preg_match('/^JALWA[0-9]{12,20}$/', $orderId)) {
        $error = 'The payment order is invalid. Please start a new deposit.';
    } else {
        $orderStmt = $conn->prepare("SELECT shonu, motta, dinankavannuracisi FROM thevani WHERE dharavahi = ? AND balakedara = ? AND mula = 'PAYTM_UPI' AND sthiti = '0' LIMIT 1");
        $order = null;
        if ($orderStmt) {
            $orderStmt->bind_param('si', $orderId, $uid);
            $orderStmt->execute();
            $order = $orderStmt->get_result()->fetch_assoc() ?: null;
            $orderStmt->close();
        }
        if ($order) {
            $orderTime = strtotime((string)($order['dinankavannuracisi'] ?? ''));
            $expiresAt = $orderTime > 0 ? $orderTime + 180 : 0;
            if ($expiresAt > 0 && time() >= $expiresAt) {
                $isExpired = true;
                $error = 'This payment page has expired after 3 minutes. Please start a new deposit.';
            }
        }
        if (!$order) {
            $error = 'This Paytm order was not found or has already been processed. Please start a new deposit.';
        } elseif (!$isExpired) {
            $amount = (float)$order['motta'];
            if (strlen($utr) < 6 || strlen($utr) > 80) {
                $error = 'Please enter a valid payment reference/UTR.';
            } else {
                $dupStmt = $conn->prepare('SELECT shonu FROM thevani WHERE ullekha = ? AND shonu <> ? LIMIT 1');
                $duplicate = false;
                if ($dupStmt) {
                    $orderRowId = (int)$order['shonu'];
                    $dupStmt->bind_param('si', $utr, $orderRowId);
                    $dupStmt->execute();
                    $duplicate = (bool)$dupStmt->get_result()->fetch_assoc();
                    $dupStmt->close();
                }
                if ($duplicate) {
                    $error = 'This payment reference has already been submitted.';
                } else {
                    $update = $conn->prepare("UPDATE thevani SET ullekha = ? WHERE shonu = ? AND balakedara = ? AND mula = 'PAYTM_UPI' AND sthiti = '0'");
                    if ($update) {
                        $orderRowId = (int)$order['shonu'];
                        $update->bind_param('sii', $utr, $orderRowId, $uid);
                        if ($update->execute() && $update->affected_rows >= 0) {
                            $submitted = true;
                            $statusMessage = 'UTR submitted successfully. Your deposit will be credited after admin verification.';
                        } else {
                            $error = 'Could not submit the UTR. Please try again.';
                        }
                        $update->close();
                    } else {
                        $error = 'Could not submit the UTR. Please try again.';
                    }
                }
            }
        }
    }
}

if ($error === '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($amount < $minimum || $amount > $maximum) {
        $error = 'Deposit amount must be between ' . number_format($minimum, 2) . ' and ' . number_format($maximum, 2) . ' INR.';
    } elseif ($payeeVpa === '') {
        $error = 'Paytm UPI ID is not configured yet. Please contact support.';
    }
    if ($error === '') {
        $orderId = 'JALWA' . date('ymdHis') . random_int(100000, 999999);
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = time() + 180;
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
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Paytm UPI</title>
  <style>
    :root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Arial,sans-serif;background:#070533;color:#f5f7ff}.wrap{max-width:520px;margin:0 auto;padding:28px 18px}.card{background:#071c54;border-radius:18px;padding:24px;margin:16px 0;box-shadow:0 12px 30px rgba(0,0,0,.22)}h1{font-size:25px;margin:0 0 10px}.muted{color:#aeb8de;line-height:1.5}.amount{font-size:34px;font-weight:800;color:#6ff5df;margin:18px 0}.order{background:#04032a;border-radius:10px;padding:14px;word-break:break-all;color:#dbe6ff}.label{color:#9fa9d0;font-size:13px;margin:14px 0 6px}.button{display:block;width:100%;text-align:center;text-decoration:none;border:0;border-radius:12px;padding:15px;font-size:17px;font-weight:700;background:#19d9c1;color:#03113c;margin-top:14px;cursor:pointer}.button:disabled{opacity:.65;cursor:not-allowed}.timer{margin:12px 0;padding:12px;border-radius:10px;text-align:center;background:#2b3d73;color:#fff}.timer strong{color:#ffe36e;font-size:20px}.timer.expired{background:#632b3e;color:#ffd8df}.timer.expired strong{color:#fff}.secondary{background:#314275;color:#fff}.confirm{background:#f5b942;color:#211500}.utr-section{margin-top:22px;padding-top:20px;border-top:1px solid #314275}.utr-section[hidden]{display:none}.error{background:#632b3e;color:#ffd8df;border-radius:10px;padding:14px;line-height:1.45}.success{background:#155a4d;color:#d6fff3;border-radius:10px;padding:14px;line-height:1.45}.back{color:#6ff5df;text-decoration:none;font-size:15px}input{width:100%;border:0;border-radius:10px;padding:14px;font-size:17px;background:#04032a;color:#fff;outline:0}
  </style>
</head>
<body>
  <main class="wrap">
    <a class="back" href="javascript:history.back()">← Back to Deposit</a>
    <?php if ($error !== '' && (!($paymentConfirmed && $orderId !== '') || $isExpired)): ?><div class="card"><h1>Paytm UPI</h1><div class="error"><?= paytm_page_escape($error) ?></div></div>
    <?php elseif ($submitted): ?><div class="card"><h1>UTR Submitted</h1><div class="success"><?= paytm_page_escape($statusMessage) ?></div><div class="amount">₹<?= paytm_page_escape(number_format($amount, 2)) ?></div><div class="label">Order ID</div><div class="order"><?= paytm_page_escape($orderId) ?></div></div>
    <?php elseif ($paymentConfirmed): ?><div class="card">
      <h1>Submit UTR</h1>
      <div id="expiryTimer" class="timer<?= $isExpired ? ' expired' : '' ?>" data-expires="<?= (int)$expiresAt ?>"><?= $isExpired ? 'Payment page expired' : 'Payment page expires in' ?> <strong><?= $isExpired ? '00:00' : '03:00' ?></strong></div>
      <?php if ($error !== ''): ?><div class="error"><?= paytm_page_escape($error) ?></div><?php endif; ?>
      <p class="muted">Enter the UTR or transaction reference shown after completing the Paytm payment.</p>
      <div class="amount">₹<?= paytm_page_escape(number_format($amount, 2)) ?></div>
      <div class="label">Order ID / Remark</div><div class="order"><?= paytm_page_escape($orderId) ?></div>
      <form method="post">
        <input type="hidden" name="payment_done" value="1"><input type="hidden" name="order_id" value="<?= paytm_page_escape($orderId) ?>">
        <div class="label">UTR / transaction hash</div><input type="text" name="utr" minlength="6" maxlength="80" placeholder="Enter payment reference" required>
        <button class="button" type="submit">Submit UTR</button>
      </form>
    </div>
    <?php else: ?><div class="card">
      <h1>Opening Paytm</h1>
      <div id="expiryTimer" class="timer" data-expires="<?= (int)$expiresAt ?>">Payment page expires in <strong>03:00</strong></div>
      <p class="muted">Your Paytm payment is ready. The amount is fixed and the order ID is already added in the payment remark.</p>
      <div class="amount">₹<?= paytm_page_escape(number_format($amount, 2)) ?></div>
      <div class="label">Order ID / Remark</div><div class="order"><?= paytm_page_escape($orderId) ?></div>
      <a id="paytmButton" class="button" href="<?= paytm_page_escape($paytmUrl) ?>">Open Paytm</a>
      <a class="button secondary" href="<?= paytm_page_escape($upiUrl) ?>">Open another UPI app</a>
      <button id="paymentDone" class="button confirm" type="button">Payment Done?</button>
      <div id="utrSection" class="utr-section" hidden>
        <h2>Submit UTR</h2><p class="muted">Confirm your payment, then enter the UTR/reference for verification.</p>
        <form method="post"><input type="hidden" name="payment_done" value="1"><input type="hidden" name="order_id" value="<?= paytm_page_escape($orderId) ?>">
          <div class="label">UTR / transaction hash</div><input type="text" name="utr" minlength="6" maxlength="80" placeholder="Enter payment reference" required>
          <button class="button" type="submit">Submit UTR</button>
        </form>
      </div>
    </div>
    <script>
      (function () {
        const paymentDone = document.getElementById('paymentDone');
        const utrSection = document.getElementById('utrSection');
        paymentDone.addEventListener('click', function () {
          utrSection.hidden = false;
          paymentDone.textContent = 'Payment Done ✓';
          paymentDone.disabled = true;
          utrSection.querySelector('input[name="utr"]').focus();
        });
        const timer = document.getElementById('expiryTimer');
        const expiresAt = Number(timer.dataset.expires || 0) * 1000;
        const updateTimer = function () {
          const remaining = Math.max(0, expiresAt - Date.now());
          const totalSeconds = Math.floor(remaining / 1000);
          const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
          const seconds = String(totalSeconds % 60).padStart(2, '0');
          timer.querySelector('strong').textContent = minutes + ':' + seconds;
          if (remaining <= 0) {
            timer.classList.add('expired');
            timer.childNodes[0].textContent = 'Payment page expired ';
            document.querySelectorAll('form button').forEach(function (button) { button.disabled = true; });
            if (paymentDone) paymentDone.disabled = true;
            clearInterval(timerInterval);
          }
        };
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
        // Keep external-app navigation behind the user’s direct tap on the Open Paytm button. This prevents a blocked Paytm security launch from leaving an empty tab or losing the pending order page.
      }());
    </script><?php endif; ?>
  </main>
</body>
</html>
