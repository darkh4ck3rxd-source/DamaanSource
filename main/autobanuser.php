<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}

require_once __DIR__ . '/conn.php';

$conn->query("CREATE TABLE IF NOT EXISTS jalwa_security_settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(32) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function save_security_setting(mysqli $conn, string $key, string $value): void {
    $stmt = $conn->prepare('INSERT INTO jalwa_security_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    if ($stmt) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}

$enabled = '1';
$settingResult = $conn->query("SELECT setting_value FROM jalwa_security_settings WHERE setting_key = 'duplicate_ip_checker' LIMIT 1");
if ($settingResult && ($settingRow = $settingResult->fetch_assoc())) {
    $enabled = ((string)$settingRow['setting_value'] === '1') ? '1' : '0';
}

$message = '';
$messageType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_duplicate_ip'])) {
        $enabled = ($_POST['duplicate_ip_checker'] ?? '') === '1' ? '1' : '0';
        save_security_setting($conn, 'duplicate_ip_checker', $enabled);
        $message = $enabled === '1'
            ? 'Duplicate IP checker ON hai. Same IP se new registration par matching accounts suspend honge aur registration block hogi.'
            : 'Duplicate IP checker OFF hai. Registration ke time duplicate-IP auto-ban nahi chalega.';
    }

    if (isset($_POST['run_duplicate_scan'])) {
        if ($enabled !== '1') {
            $message = 'Manual duplicate scan nahi chala kyunki checker OFF hai.';
            $messageType = 'warning';
        } else {
            $bannedUsers = 0;
            $duplicateQuery = "SELECT ishonup FROM shonu_subjects WHERE ishonup IS NOT NULL AND ishonup <> '' AND ishonup <> 'UNKNOWN' GROUP BY ishonup HAVING COUNT(*) > 1";
            $duplicateResult = $conn->query($duplicateQuery);
            if ($duplicateResult) {
                while ($duplicateRow = $duplicateResult->fetch_assoc()) {
                    $ip = (string)$duplicateRow['ishonup'];
                    $stmt = $conn->prepare('UPDATE shonu_subjects SET status = 0 WHERE ishonup = ? AND status <> 0');
                    if ($stmt) {
                        $stmt->bind_param('s', $ip);
                        $stmt->execute();
                        $bannedUsers += max(0, $stmt->affected_rows);
                        $stmt->close();
                    }
                }
            }
            $message = $bannedUsers > 0
                ? $bannedUsers . ' duplicate-IP account(s) suspend kiye gaye.'
                : 'Koi duplicate IP account nahi mila.';
        }
    }
}

$duplicates = [];
$duplicateQuery = "SELECT ishonup, COUNT(*) AS user_count FROM shonu_subjects WHERE ishonup IS NOT NULL AND ishonup <> '' AND ishonup <> 'UNKNOWN' GROUP BY ishonup HAVING COUNT(*) > 1 ORDER BY user_count DESC";
$duplicateResult = $conn->query($duplicateQuery);
if ($duplicateResult) {
    while ($row = $duplicateResult->fetch_assoc()) {
        $duplicates[] = $row;
    }
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicate IP Security</title>
    <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background:#f7f8fc; }
        .security-card { background:#fff; border-radius:12px; padding:24px; margin-bottom:20px; box-shadow:0 4px 18px rgba(0,0,0,.07); }
        .status-pill { display:inline-block; border-radius:20px; padding:6px 12px; font-weight:600; }
        .status-on { color:#116b3a; background:#d9f7e6; }
        .status-off { color:#7b1e1e; background:#ffe0e0; }
        .help { color:#6c757d; font-size:14px; line-height:1.55; }
        .table td, .table th { vertical-align:middle; }
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
            <div class="user-profile"><div class="user-image"><img src="images/faces/face28.png" alt="Admin"></div><div class="user-name">Admin</div><div class="user-designation">Duplicate IP Security</div></div>
            <?php include __DIR__ . '/compass.php'; ?>
        </nav>
        <div class="main-panel"><div class="content-wrapper">
            <h3 class="font-weight-bold text-dark mb-4">Duplicate IP Security</h3>
            <?php if ($message !== ''): ?><div class="alert alert-<?= h($messageType) ?>"><?= h($message) ?></div><?php endif; ?>

            <div class="security-card">
                <h4>Automatic duplicate-IP checker</h4>
                <p class="help">ON hone par registration ke waqt same IP ka existing account milega to matching accounts status 0 (suspended) honge aur new registration block hogi. OFF hone par registration ke time duplicate-IP check/auto-ban nahi chalega.</p>
                <p>Current status:
                    <span class="status-pill <?= $enabled === '1' ? 'status-on' : 'status-off' ?>"><?= $enabled === '1' ? 'ON' : 'OFF' ?></span>
                </p>
                <form method="post">
                    <div class="form-group">
                        <label for="duplicate_ip_checker">Checker mode</label>
                        <select class="form-control" id="duplicate_ip_checker" name="duplicate_ip_checker">
                            <option value="1" <?= $enabled === '1' ? 'selected' : '' ?>>ON — auto suspend and block duplicate IP registration</option>
                            <option value="0" <?= $enabled === '0' ? 'selected' : '' ?>>OFF — do not check or auto-ban duplicate IPs</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit" name="toggle_duplicate_ip">Save Duplicate IP Setting</button>
                </form>
            </div>

            <div class="security-card">
                <h4>Existing duplicate-IP accounts</h4>
                <p class="help">Manual scan sirf existing records ke liye hai. Ye tabhi chalega jab checker ON ho. Page open karne se accounts automatically suspend nahi honge.</p>
                <form method="post" class="mb-3">
                    <button class="btn btn-danger" type="submit" name="run_duplicate_scan" <?= $enabled !== '1' ? 'disabled' : '' ?>>Run Scan &amp; Suspend Duplicates</button>
                </form>
                <?php if (count($duplicates) > 0): ?>
                    <div class="table-responsive"><table class="table table-bordered">
                        <thead><tr><th>IP Address</th><th>Accounts</th></tr></thead>
                        <tbody>
                        <?php foreach ($duplicates as $duplicate): ?>
                            <tr><td><?= h((string)$duplicate['ishonup']) ?></td><td><?= h((string)$duplicate['user_count']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php else: ?>
                    <p class="text-muted mb-0">No duplicate IP groups found.</p>
                <?php endif; ?>
            </div>
        </div></div>
    </div>
</div>
<script src="vendors/base/vendor.bundle.base.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/template.js"></script>
</body>
</html>
