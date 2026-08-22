<?php
require_once __DIR__ . '/../serive/samparka.php';

header('Content-Type: text/html; charset=utf-8');

function qr_page_escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function qr_page_setting(mysqli $conn, string $key, string $default = ''): string {
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

$method = strtolower((string)($_GET['method'] ?? 'upi')) === 'usdt' ? 'usdt' : 'upi';
$uid = (int)($_GET['uid'] ?? 0);
$clientSign = strtoupper(trim((string)($_GET['sign'] ?? '')));
$amount = (float)($_GET['amount'] ?? 0);
$statusMessage = '';
$statusClass = 'info';
$pageExpired = false;
$timerStart = 0;
$timerValid = false;
$timerDuration = 5 * 60;

$user = null;
if ($uid > 0) {
    $userStmt = $conn->prepare('SELECT id, mobile FROM shonu_subjects WHERE id = ? AND status = 1 LIMIT 1');
    if ($userStmt) {
        $userStmt->bind_param('i', $uid);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
    }
}
if (!$user) {
    http_response_code(403);
    $statusMessage = 'Your user session is invalid. Please return to the app and try again.';
    $statusClass = 'error';
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
    $profileStmt = $conn->prepare('SELECT codechorkamukala, createdate FROM shonu_subjects WHERE id = ? LIMIT 1');
    $profileStmt->bind_param('i', $uid);
    $profileStmt->execute();
    $profile = $profileStmt->get_result()->fetch_assoc() ?: [];
    $profileStmt->close();
    $nickname = (string)($profile['codechorkamukala'] ?? ('Member' . $uid));
    $createdate = (string)($profile['createdate'] ?? '');
    $expectedSign = strtoupper(hash('sha256', '{"userId":' . $uid . ',"userPhoto":"' . $avatar . '","userName":91' . $user['mobile'] . ',"nickName":"' . $nickname . '","createdate":"' . $createdate . '"}'));
    if ($clientSign === '' || !hash_equals($expectedSign, $clientSign)) {
        http_response_code(403);
        $statusMessage = 'The deposit link has expired. Please return to the app and start again.';
        $statusClass = 'error';
        $user = null;
    }
}

$minimum = $method === 'usdt' ? max(1, (float)qr_page_setting($conn, 'usdt_min_amount', '10')) : max(1, (float)qr_page_setting($conn, 'wake_min_amount', '200'));
$qr = $method === 'usdt' ? qr_page_setting($conn, 'usdt_qr') : qr_page_setting($conn, 'wake_upi_qr');
$account = $method === 'usdt' ? qr_page_setting($conn, 'usdt_address') : qr_page_setting($conn, 'wake_upi_id');
$network = qr_page_setting($conn, 'usdt_network', 'TRC20');
if ($qr === '') {
    $legacyTable = $method === 'usdt' ? 'images_usdt' : 'images';
    $legacyDir = $method === 'usdt' ? '/images_usdt/' : '/images/';
    $legacyResult = $conn->query("SELECT filename FROM {$legacyTable} WHERE status = '1' ORDER BY id DESC LIMIT 1");
    if ($legacyResult && ($legacyRow = $legacyResult->fetch_assoc()) && !empty($legacyRow['filename'])) {
        $qr = $legacyDir . rawurlencode((string)$legacyRow['filename']);
    }
}
$title = $method === 'usdt' ? 'USDT Deposit' : 'Wake UP-APP UPI Deposit';
$unit = $method === 'usdt' ? 'USDT' : 'INR';
$maximum = $method === 'usdt' ? 1000000 : 50000;

// A signed timer cookie prevents refresh/tampering from extending the five-minute window.
$timerCookieName = 'jalwa_qr_timer_' . substr(hash('sha256', $uid . '|' . $method . '|' . number_format($amount, 2, '.', '')), 0, 20);
$timerSecret = getenv('JWT_SECRET') ?: 'bdgshonuuncensored';
$timerCookie = (string)($_COOKIE[$timerCookieName] ?? '');
$timerParts = explode('|', $timerCookie);
if (count($timerParts) === 3 && ctype_digit($timerParts[0]) && preg_match('/^[a-f0-9]{32}$/', $timerParts[1])) {
    $candidateStart = (int)$timerParts[0];
    $candidateNonce = $timerParts[1];
    $candidateSig = $timerParts[2];
    $candidateData = $uid . '|' . $method . '|' . number_format($amount, 2, '.', '') . '|' . $candidateStart . '|' . $candidateNonce;
    $expectedTimerSig = hash_hmac('sha256', $candidateData, $timerSecret);
    if (hash_equals($expectedTimerSig, $candidateSig) && $candidateStart <= time() && time() - $candidateStart < $timerDuration) {
        $timerStart = $candidateStart;
        $timerValid = true;
    }
}
if ($user && $_SERVER['REQUEST_METHOD'] === 'GET' && !$timerValid) {
    $timerStart = time();
    $timerNonce = bin2hex(random_bytes(16));
    $timerData = $uid . '|' . $method . '|' . number_format($amount, 2, '.', '') . '|' . $timerStart . '|' . $timerNonce;
    $timerSignature = hash_hmac('sha256', $timerData, $timerSecret);
    setcookie($timerCookieName, $timerStart . '|' . $timerNonce . '|' . $timerSignature, [
        'expires' => $timerStart + $timerDuration,
        'path' => '/pay/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $timerValid = true;
}
$expiresAt = $timerStart + $timerDuration;
if ($user && time() >= $expiresAt) {
    $pageExpired = true;
    $statusMessage = 'This payment page expired after 5 minutes. Please return to Deposit and start again.';
    $statusClass = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $uid > 0) {
    // Amount is intentionally taken from the signed page URL, not from the editable request body.
    $amount = (float)($_GET['amount'] ?? $amount);
    $utr = trim((string)($_POST['utr'] ?? ''));
    if (!$timerValid || time() >= $expiresAt) {
        $pageExpired = true;
        $statusMessage = 'This payment page expired after 5 minutes. Please return to Deposit and start again.';
        $statusClass = 'error';
    } elseif ($amount < $minimum) {
        $statusMessage = 'Minimum deposit is ' . number_format($minimum, 2) . ' ' . $unit . '.';
        $statusClass = 'error';
    } elseif ($amount > $maximum) {
        $statusMessage = 'Maximum deposit is ' . number_format($maximum, 2) . ' ' . $unit . '.';
        $statusClass = 'error';
    } elseif (strlen($utr) < 6 || strlen($utr) > 80) {
        $statusMessage = 'Please enter a valid payment reference/UTR.';
        $statusClass = 'error';
    } else {
        $dupStmt = $conn->prepare('SELECT shonu FROM thevani WHERE ullekha = ? LIMIT 1');
        $duplicate = false;
        if ($dupStmt) {
            $dupStmt->bind_param('s', $utr);
            $dupStmt->execute();
            $duplicate = (bool)$dupStmt->get_result()->fetch_assoc();
            $dupStmt->close();
        }
        if ($duplicate) {
            $statusMessage = 'This payment reference has already been submitted.';
            $statusClass = 'error';
        } else {
            $serial = date('YmdHis') . random_int(100000, 999999);
            $payName = $method === 'usdt' ? 'USDT QR' : 'Wake UPI QR';
            $createdAt = date('Y-m-d H:i:s');
            $insert = $conn->prepare("INSERT INTO thevani (payid, balakedara, motta, dharavahi, mula, ullekha, duravani, ekikrtapavati, dinankavannuracisi, madari, pavatiaidi, sthiti) VALUES ('1', ?, ?, ?, ?, ?, ?, ?, ?, '1005', '2', '0')");
            if ($insert) {
                $insert->bind_param('idssssss', $uid, $amount, $serial, $payName, $utr, $user['mobile'], $method, $createdAt);
                if ($insert->execute()) {
                    $statusMessage = 'Deposit request submitted. It will be credited after admin verification.';
                    $statusClass = 'success';
                } else {
                    $statusMessage = 'Could not submit the deposit request. Please try again.';
                    $statusClass = 'error';
                }
                $insert->close();
            } else {
                $statusMessage = 'Deposit service is temporarily unavailable.';
                $statusClass = 'error';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?= qr_page_escape($title) ?></title>
  <style>
    :root { color-scheme: dark; }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; font-family:Arial,sans-serif; background:#070533; color:#f5f7ff; }
    .wrap { max-width:520px; margin:0 auto; padding:24px 18px 40px; }
    .card { background:#071c54; border-radius:18px; padding:22px; margin:16px 0; box-shadow:0 12px 30px rgba(0,0,0,.22); }
    h1 { font-size:24px; margin:0 0 8px; }
    h2 { font-size:18px; margin:0 0 16px; }
    .muted { color:#aeb8de; line-height:1.45; }
    .qr { display:block; width:min(300px,100%); aspect-ratio:1; object-fit:contain; background:#fff; padding:10px; border-radius:14px; margin:20px auto; }
    .empty { border:1px dashed #7180b2; padding:18px; border-radius:12px; color:#ffd77e; }
    .value { display:flex; justify-content:space-between; gap:12px; align-items:center; background:#04032a; border-radius:10px; padding:13px; word-break:break-all; }
    .label { color:#9fa9d0; font-size:13px; margin:14px 0 6px; }
    input { width:100%; border:0; border-radius:10px; padding:14px; font-size:17px; background:#04032a; color:#fff; outline:0; }
    input.amount-locked { color:#c7d2fe; border:1px solid #314275; cursor:not-allowed; opacity:.9; }
    button { width:100%; margin-top:18px; border:0; border-radius:12px; padding:15px; font-size:17px; font-weight:700; background:#19d9c1; color:#03113c; }
    button:disabled { background:#68739a; color:#d7dcf0; cursor:not-allowed; }
    .notice { border-radius:10px; padding:13px; line-height:1.4; margin-bottom:16px; }
    .notice.info { background:#173267; color:#dbe6ff; } .notice.error { background:#632b3e; color:#ffd8df; } .notice.success { background:#155a4d; color:#d6fff3; }
    .timer { display:flex; justify-content:space-between; align-items:center; gap:12px; background:#321f53; border:1px solid #8d62c5; color:#f4eaff; border-radius:10px; padding:13px 15px; font-weight:700; margin:14px 0; }
    .timer strong { color:#7fffea; font-size:20px; letter-spacing:1px; }
    .timer.expired { background:#632b3e; border-color:#b65b71; color:#ffd8df; }
    .timer.expired strong { color:#ffd8df; }
    .back { color:#6ff5df; text-decoration:none; font-size:15px; }
    .locked-note { color:#9fa9d0; font-size:12px; margin-top:6px; }
  </style>
</head>
<body>
  <main class="wrap">
    <a class="back" href="javascript:history.back()">← Back to Deposit</a>
    <div class="card">
      <h1><?= qr_page_escape($title) ?></h1>
      <p class="muted">Scan the QR, complete the payment, then submit the payment reference below. Your wallet will be updated after verification.</p>
      <?php if ($statusMessage !== ''): ?><div class="notice <?= qr_page_escape($statusClass) ?>"><?= qr_page_escape($statusMessage) ?></div><?php endif; ?>
      <?php if ($timerValid && $user): ?><div id="expiryTimer" class="timer" data-expires="<?= (int)$expiresAt ?>"><span>Payment page expires in</span><strong>05:00</strong></div><?php endif; ?>
      <?php if ($qr !== ''): ?><img class="qr" src="<?= qr_page_escape($qr) ?>" alt="<?= qr_page_escape($title) ?> QR code"><?php else: ?><div class="empty">Admin has not configured this QR yet. Please contact support.</div><?php endif; ?>
      <?php if ($account !== ''): ?><div class="label"><?= $method === 'usdt' ? 'Wallet address (' . qr_page_escape($network) . ')' : 'UPI ID' ?></div><div class="value"><?= qr_page_escape($account) ?></div><?php endif; ?>
    </div>
    <?php if ($user): ?><div class="card">
      <h2>Submit payment reference</h2>
      <div class="label">Amount (locked, minimum <?= qr_page_escape(number_format($minimum, 2)) ?> <?= qr_page_escape($unit) ?>)</div>
      <form id="depositForm" method="post">
        <input class="amount-locked" type="number" name="amount" min="<?= qr_page_escape((string)$minimum) ?>" max="<?= qr_page_escape((string)$maximum) ?>" step="0.01" value="<?= qr_page_escape(number_format($amount, 2, '.', '')) ?>" readonly aria-readonly="true" tabindex="-1" required>
        <div class="locked-note">This amount is fixed by the deposit request and cannot be edited.</div>
        <div class="label">UTR / transaction hash</div>
        <input type="text" name="utr" minlength="6" maxlength="80" placeholder="Enter payment reference" required>
        <button id="submitDeposit" type="submit" <?= $pageExpired ? 'disabled' : '' ?>>Submit Deposit Request</button>
      </form>
    </div><?php endif; ?>
  </main>
  <?php if ($timerValid && $user): ?><script>
    (function () {
      const timer = document.getElementById('expiryTimer');
      const form = document.getElementById('depositForm');
      const button = document.getElementById('submitDeposit');
      const counter = timer ? timer.querySelector('strong') : null;
      const expiresAt = Number(timer && timer.dataset.expires);
      let expired = false;
      function updateTimer() {
        const seconds = expiresAt - Math.floor(Date.now() / 1000);
        if (seconds <= 0) {
          expired = true;
          timer.classList.add('expired');
          timer.querySelector('span').textContent = 'Payment page expired';
          counter.textContent = '00:00';
          button.disabled = true;
          return;
        }
        const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
        const remainder = String(seconds % 60).padStart(2, '0');
        counter.textContent = minutes + ':' + remainder;
      }
      updateTimer();
      const interval = setInterval(function () {
        updateTimer();
        if (expired) clearInterval(interval);
      }, 1000);
      form.addEventListener('submit', function (event) {
        if (expired) {
          event.preventDefault();
          window.alert('This payment page has expired. Please start a new deposit.');
        }
      });
    }());
  </script><?php endif; ?>
</body>
</html>
