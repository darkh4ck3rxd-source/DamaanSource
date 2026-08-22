<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';

$conn->query("CREATE TABLE IF NOT EXISTS jalwa_payment_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$defaults = [
    'wake_upi_qr' => '',
    'wake_upi_id' => '',
    'wake_min_amount' => '200',
    'paytm_upi_id' => '',
    'paytm_upi_name' => 'Jalwa',
    'phonepe_upi_id' => '',
    'phonepe_upi_name' => 'Jalwa',
    'usdt_qr' => '',
    'usdt_address' => '',
    'usdt_network' => 'TRC20',
    'usdt_min_amount' => '10',
];

function save_payment_setting(mysqli $conn, string $key, string $value): void {
    $stmt = $conn->prepare('INSERT INTO jalwa_payment_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    if ($stmt) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}

function uploaded_qr_data_uri(string $field): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 2 * 1024 * 1024) {
        return null;
    }
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
        return null;
    }
    $contents = file_get_contents($_FILES[$field]['tmp_name']);
    return 'data:' . $info['mime'] . ';base64,' . base64_encode($contents);
}

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $textFields = ['wake_upi_id', 'wake_min_amount', 'paytm_upi_id', 'paytm_upi_name', 'phonepe_upi_id', 'phonepe_upi_name', 'usdt_address', 'usdt_network', 'usdt_min_amount'];
    foreach ($textFields as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        if (in_array($field, ['wake_min_amount', 'usdt_min_amount'], true)) {
            $value = (string)max(1, (float)$value);
        }
        save_payment_setting($conn, $field, $value);
    }
    foreach (['wake_upi_qr', 'usdt_qr'] as $field) {
        $qr = uploaded_qr_data_uri($field);
        $urlField = $field . '_url';
        if ($qr !== null) {
            save_payment_setting($conn, $field, $qr);
        } elseif (!empty($_POST[$urlField]) && filter_var($_POST[$urlField], FILTER_VALIDATE_URL)) {
            save_payment_setting($conn, $field, trim((string)$_POST[$urlField]));
        }
    }
    $message = 'Payment QR settings saved successfully.';
}

$settings = $defaults;
$result = $conn->query('SELECT setting_key, setting_value FROM jalwa_payment_settings');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['setting_key'], $settings)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment QR Settings</title>
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .qr-card { background:#fff; border-radius:12px; padding:22px; margin-bottom:24px; box-shadow:0 4px 18px rgba(0,0,0,.07); }
    .qr-card h4 { margin-bottom:8px; }
    .qr-preview { max-width:220px; max-height:220px; border:1px solid #dee2e6; border-radius:8px; padding:8px; background:#fff; }
    .help { color:#6c757d; font-size:13px; margin-top:6px; }
    .form-control { margin-bottom:12px; }
  </style>
</head>
<body>
<div class="container-scroller">
  <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center"><a class="navbar-brand brand-logo" href="dashboard.php"><img src="images/logo.png" alt="logo"></a></div>
    <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end"><a class="nav-link" href="logout.php">Logout</a></div>
  </nav>
  <div class="container-fluid page-body-wrapper">
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <div class="user-profile"><div class="user-image"><img src="images/faces/face28.png" alt="Admin"></div><div class="user-name">Admin</div><div class="user-designation">Payment Settings</div></div>
      <?php include __DIR__ . '/compass.php'; ?>
    </nav>
    <div class="main-panel"><div class="content-wrapper">
      <h3 class="font-weight-bold text-dark mb-4">Payment QR Settings</h3>
      <?php if ($message !== ''): ?><div class="alert alert-<?= h($messageType) ?>"><?= h($message) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="qr-card">
          <h4>Wake UP-APP — UPI</h4>
          <p class="help">This QR is shown when the user selects Wake UP-APP. Upload a QR image or enter a hosted image URL.</p>
          <?php if (str_starts_with($settings['wake_upi_qr'], 'data:image/')): ?><img class="qr-preview mb-3" src="<?= h($settings['wake_upi_qr']) ?>" alt="Wake UPI QR"><br><?php endif; ?>
          <label>Upload Wake UPI QR</label><input class="form-control" type="file" name="wake_upi_qr" accept="image/png,image/jpeg,image/webp,image/gif">
          <label>Or hosted Wake UPI QR URL</label><input class="form-control" type="url" name="wake_upi_qr_url" value="<?= h(str_starts_with($settings['wake_upi_qr'], 'data:') ? '' : $settings['wake_upi_qr']) ?>" placeholder="https://.../wake-upi-qr.png">
          <label>Wake UPI ID</label><input class="form-control" type="text" name="wake_upi_id" value="<?= h($settings['wake_upi_id']) ?>" placeholder="example@upi">
          <label>Minimum Wake UPI amount</label><input class="form-control" type="number" min="1" name="wake_min_amount" value="<?= h($settings['wake_min_amount']) ?>">
        </div>
        <div class="qr-card">
          <h4>Paytm UPI Intent</h4>
          <p class="help">The UPI-PayTM deposit option opens Paytm directly with the selected amount. The order ID is sent as the UPI remark/reference.</p>
          <label>Paytm UPI ID / VPA</label><input class="form-control" type="text" name="paytm_upi_id" value="<?= h($settings['paytm_upi_id']) ?>" placeholder="example@ptyes">
          <label>Payee name shown in Paytm</label><input class="form-control" type="text" name="paytm_upi_name" value="<?= h($settings['paytm_upi_name']) ?>" placeholder="Jalwa">
        </div>
        <div class="qr-card">
          <h4>PhonePe UPI Intent</h4>
          <p class="help">UPI_PHONEPE opens PhonePe with the selected amount and numeric order ID in the remark.</p>
          <label>PhonePe UPI ID / VPA</label><input class="form-control" type="text" name="phonepe_upi_id" value="<?= h($settings['phonepe_upi_id']) ?>" placeholder="example@ybl">
          <label>Payee name shown in PhonePe</label><input class="form-control" type="text" name="phonepe_upi_name" value="<?= h($settings['phonepe_upi_name']) ?>" placeholder="Jalwa">
        </div>
        <div class="qr-card">
          <h4>USDT</h4>
          <p class="help">This QR and wallet address are shown when the user selects USDT.</p>
          <?php if (str_starts_with($settings['usdt_qr'], 'data:image/')): ?><img class="qr-preview mb-3" src="<?= h($settings['usdt_qr']) ?>" alt="USDT QR"><br><?php endif; ?>
          <label>Upload USDT QR</label><input class="form-control" type="file" name="usdt_qr" accept="image/png,image/jpeg,image/webp,image/gif">
          <label>Or hosted USDT QR URL</label><input class="form-control" type="url" name="usdt_qr_url" value="<?= h(str_starts_with($settings['usdt_qr'], 'data:') ? '' : $settings['usdt_qr']) ?>" placeholder="https://.../usdt-qr.png">
          <label>USDT wallet address</label><input class="form-control" type="text" name="usdt_address" value="<?= h($settings['usdt_address']) ?>" placeholder="TRC20 wallet address">
          <label>Network</label><input class="form-control" type="text" name="usdt_network" value="<?= h($settings['usdt_network']) ?>" placeholder="TRC20">
          <label>Minimum USDT amount</label><input class="form-control" type="number" min="1" name="usdt_min_amount" value="<?= h($settings['usdt_min_amount']) ?>">
        </div>
        <button class="btn btn-primary" type="submit">Save Payment Settings</button>
      </form>
    </div></div>
  </div>
</div>
<script src="vendors/base/vendor.bundle.base.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/template.js"></script>
</body>
</html>
