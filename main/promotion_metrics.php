<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';

$metricLabels = [
    'children_Lv_RebateAmount_Week' => ['This Week', 'amount'],
    'children_Lv_RebateAmount' => ['Total commission', 'amount'],
    'children_Lv_1_Count' => ['Direct subordinate', 'count'],
    'children_Lv_Count_X' => ['Total number of subordinates in team', 'count'],
];

$conn->query("CREATE TABLE IF NOT EXISTS agency_metric_overrides (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metric_key VARCHAR(100) NOT NULL,
    metric_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    admin_id INT NULL,
    admin_name VARCHAR(100) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY agency_user_metric (user_id, metric_key),
    KEY agency_user_lookup (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS agency_metric_override_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metric_key VARCHAR(100) NOT NULL,
    old_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    new_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    admin_id INT NULL,
    admin_name VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY agency_history_user (user_id),
    KEY agency_history_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$userId = (int)($_REQUEST['user_id'] ?? 0);
$notice = '';
$error = '';
$values = array_fill_keys(array_keys($metricLabels), 0.0);
$user = null;

if ($userId > 0) {
    $userStmt = $conn->prepare('SELECT id, mobile, codechorkamukala FROM shonu_subjects WHERE id = ? LIMIT 1');
    if ($userStmt) {
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
    }
    if (empty($user)) {
        $error = 'User UID was not found.';
    } else {
        $overrideStmt = $conn->prepare('SELECT metric_key, metric_value FROM agency_metric_overrides WHERE user_id = ?');
        if ($overrideStmt) {
            $overrideStmt->bind_param('i', $userId);
            $overrideStmt->execute();
            $overrideResult = $overrideStmt->get_result();
            while ($row = $overrideResult->fetch_assoc()) {
                if (array_key_exists($row['metric_key'], $values)) {
                    $values[$row['metric_key']] = (float)$row['metric_value'];
                }
            }
            $overrideStmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId > 0 && $error === '' && !empty($user)) {
    $adminId = (int)($_SESSION['unohs'] ?? 0);
    $adminName = (string)($_SESSION['nirvahaka_hesaru'] ?? 'admin');
    $newValues = [];
    foreach ($metricLabels as $key => $meta) {
        $raw = $_POST['metric'][$key] ?? 0;
        $number = is_numeric($raw) ? (float)$raw : 0.0;
        $newValues[$key] = max(0.0, round($number, 2));
    }

    $oldValues = $values;
    $upsert = null;
    $history = null;
    $conn->begin_transaction();
    try {
        $upsert = $conn->prepare('INSERT INTO agency_metric_overrides (user_id, metric_key, metric_value, admin_id, admin_name) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), admin_id = VALUES(admin_id), admin_name = VALUES(admin_name)');
        $history = $conn->prepare('INSERT INTO agency_metric_override_history (user_id, metric_key, old_value, new_value, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$upsert || !$history) {
            throw new RuntimeException('prepare');
        }
        foreach ($newValues as $key => $number) {
            $oldNumber = (float)($oldValues[$key] ?? 0);
            $upsert->bind_param('isdis', $userId, $key, $number, $adminId, $adminName);
            if (!$upsert->execute()) {
                throw new RuntimeException('upsert');
            }
            if (abs($oldNumber - $number) > 0.00001) {
                $history->bind_param('isddis', $userId, $key, $oldNumber, $number, $adminId, $adminName);
                if (!$history->execute()) {
                    throw new RuntimeException('history');
                }
            }
        }
        $conn->commit();
        $values = $newValues;
        $notice = 'Promotion Data saved successfully for UID ' . $userId . '.';
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = 'Promotion Data could not be saved. No changes were applied.';
    }
    if ($upsert) {
        $upsert->close();
    }
    if ($history) {
        $history->close();
    }
}

$historyRows = [];
if ($userId > 0 && !empty($user) && $error === '') {
    $historyStmt = $conn->prepare('SELECT metric_key, old_value, new_value, admin_name, created_at FROM agency_metric_override_history WHERE user_id = ? ORDER BY id DESC LIMIT 100');
    if ($historyStmt) {
        $historyStmt->bind_param('i', $userId);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        while ($row = $historyResult->fetch_assoc()) {
            if (array_key_exists($row['metric_key'], $metricLabels)) {
                $historyRows[] = $row;
            }
        }
        $historyStmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Promotion Data Editor</title>
<style>
body{margin:0;background:#f3f6fb;color:#172033;font-family:Arial,sans-serif}.top{background:#111b4b;color:#fff;padding:18px 24px;display:flex;justify-content:space-between;align-items:center}.top a{color:#fff;text-decoration:none;margin-left:15px}.wrap{max-width:900px;margin:28px auto;padding:0 16px}.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px #17203318;padding:22px;margin-bottom:20px}.lookup{display:flex;gap:10px;align-items:end}.lookup label{display:flex;flex-direction:column;gap:7px;font-weight:600}.lookup input{min-width:240px}.button,button{border:0;border-radius:7px;background:#2563eb;color:#fff;padding:11px 17px;text-decoration:none;cursor:pointer;font-size:14px}.notice,.error{padding:12px;border-radius:7px;margin:12px 0}.notice{background:#e8f7ee;color:#147a3d}.error{background:#fff0f0;color:#a61b1b}.user{background:#eef4ff;border-radius:8px;padding:12px;margin:14px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:6px}.field label{font-weight:600;font-size:14px}.field small{color:#667085}.field input{padding:11px;border:1px solid #cfd5df;border-radius:7px;font-size:15px;box-sizing:border-box}.save{margin-top:18px;width:100%;font-size:16px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:9px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{background:#f8fafc}@media(max-width:650px){.lookup{display:block}.lookup label{margin-bottom:10px}.lookup input{width:100%;box-sizing:border-box}.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="top"><strong>Promotion Data Editor</strong><nav><a href="dashboard.php">Dashboard</a><a href="compass.php">Admin menu</a></nav></header>
<main class="wrap">
<div class="card"><h2>Manage Promotion Data numbers</h2><p>Enter a user UID to edit the four numbers shown in the Promotion page’s Promotion Data card. Values are admin-only overrides and every change is recorded below.</p>
<form class="lookup" method="get"><label>User UID<input type="number" name="user_id" min="1" value="<?= $userId ?: '' ?>" required></label><button type="submit">Load user</button></form>
<?php if ($notice): ?><div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (!empty($user)): ?><div class="user"><strong>UID <?= (int)$user['id'] ?></strong> · <?= htmlspecialchars((string)$user['mobile'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)($user['codechorkamukala'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
<form method="post"><input type="hidden" name="user_id" value="<?= $userId ?>"><div class="grid">
<?php foreach ($metricLabels as $key => [$label, $kind]): ?><div class="field"><label for="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label><input id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" type="number" min="0" step="<?= $kind === 'count' ? '1' : '0.01' ?>" name="metric[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars(number_format((float)$values[$key], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required><small><?= $kind === 'count' ? 'Whole number' : 'Amount' ?></small></div><?php endforeach; ?>
</div><button class="save" type="submit">Save Promotion Data</button></form><?php endif; ?></div>
<?php if ($userId > 0 && !empty($user) && !$error): ?><div class="card"><h3>Promotion Data edit history</h3><div class="table-wrap"><table><thead><tr><th>Field</th><th>Old value</th><th>New value</th><th>Admin</th><th>Time</th></tr></thead><tbody><?php if (!$historyRows): ?><tr><td colspan="5">No manual changes recorded yet.</td></tr><?php else: foreach ($historyRows as $row): ?><tr><td><?= htmlspecialchars($metricLabels[$row['metric_key']][0] ?? $row['metric_key'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['old_value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['new_value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['admin_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div><?php endif; ?>
</main>
</body></html>

