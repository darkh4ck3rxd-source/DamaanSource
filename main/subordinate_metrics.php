<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';
date_default_timezone_set('Asia/Kolkata');

$summaryFields = [
    'summary_recharge_count' => ['Deposit number', 'count'],
    'summary_recharge_amount' => ['Deposit amount', 'amount'],
    'summary_bet_count' => ['Number of bettors', 'count'],
    'summary_bet_amount' => ['Total bet', 'amount'],
    'summary_first_recharge_count' => ['People making first deposit', 'count'],
    'summary_first_recharge_amount' => ['First deposit amount', 'amount'],
];
$childFields = [
    'level' => ['Level', 'count'],
    'deposit_amount' => ['Deposit amount', 'amount'],
    'commission' => ['Commission', 'amount'],
];
$legacyMetricDate = '1000-01-01';

function subordinateColumnExists(mysqli $conn, string $table, string $column): bool
{
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && $result->num_rows > 0;
}

function subordinateIndexExists(mysqli $conn, string $table, string $index): bool
{
    $result = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
    return $result && $result->num_rows > 0;
}

$conn->query("CREATE TABLE IF NOT EXISTS subordinate_metric_overrides (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metric_date DATE NOT NULL DEFAULT '1000-01-01',
    metric_key VARCHAR(100) NOT NULL,
    metric_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    admin_id INT NULL,
    admin_name VARCHAR(100) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY subordinate_user_metric_date (user_id, metric_key, metric_date),
    KEY subordinate_user_lookup (user_id),
    KEY subordinate_date_lookup (metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS subordinate_metric_override_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metric_date DATE NOT NULL DEFAULT '1000-01-01',
    metric_key VARCHAR(100) NOT NULL,
    old_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    new_value DECIMAL(20,2) NOT NULL DEFAULT 0,
    admin_id INT NULL,
    admin_name VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY subordinate_history_user (user_id),
    KEY subordinate_history_date (metric_date),
    KEY subordinate_history_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migrate tables created by the earlier non-date editor without deleting existing values.
if (!subordinateColumnExists($conn, 'subordinate_metric_overrides', 'metric_date')) {
    $conn->query("ALTER TABLE subordinate_metric_overrides ADD COLUMN metric_date DATE NOT NULL DEFAULT '1000-01-01' AFTER user_id");
}
if (subordinateIndexExists($conn, 'subordinate_metric_overrides', 'subordinate_user_metric')) {
    $conn->query("ALTER TABLE subordinate_metric_overrides DROP INDEX subordinate_user_metric");
}
if (!subordinateIndexExists($conn, 'subordinate_metric_overrides', 'subordinate_user_metric_date')) {
    $conn->query("ALTER TABLE subordinate_metric_overrides ADD UNIQUE KEY subordinate_user_metric_date (user_id, metric_key, metric_date)");
}
if (!subordinateColumnExists($conn, 'subordinate_metric_override_history', 'metric_date')) {
    $conn->query("ALTER TABLE subordinate_metric_override_history ADD COLUMN metric_date DATE NOT NULL DEFAULT '1000-01-01' AFTER user_id");
}
if (!subordinateIndexExists($conn, 'subordinate_metric_overrides', 'subordinate_date_lookup')) {
    $conn->query("ALTER TABLE subordinate_metric_overrides ADD KEY subordinate_date_lookup (metric_date)");
}
if (!subordinateIndexExists($conn, 'subordinate_metric_override_history', 'subordinate_history_date')) {
    $conn->query("ALTER TABLE subordinate_metric_override_history ADD KEY subordinate_history_date (metric_date)");
}

function normalizeSubordinateDate($value, string $fallback): string
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $fallback;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        return $fallback;
    }
    return $value;
}

$parentId = (int)($_REQUEST['parent'] ?? $_REQUEST['user_id'] ?? 0);
$metricDate = normalizeSubordinateDate($_REQUEST['metric_date'] ?? date('Y-m-d'), date('Y-m-d'));
$notice = '';
$error = '';
$parent = null;
$children = [];
$summaryValues = array_fill_keys(array_keys($summaryFields), 0.0);

if ($parentId > 0) {
    $parentStmt = $conn->prepare('SELECT id, mobile, owncode, codechorkamukala FROM shonu_subjects WHERE id = ? LIMIT 1');
    if ($parentStmt) {
        $parentStmt->bind_param('i', $parentId);
        $parentStmt->execute();
        $parent = $parentStmt->get_result()->fetch_assoc() ?: null;
        $parentStmt->close();
    }
    if (!$parent) {
        $error = 'Parent UID was not found.';
    } else {
        $summaryStmt = $conn->prepare('SELECT metric_key, metric_value FROM subordinate_metric_overrides WHERE user_id = ? AND metric_date IN (?, ?) AND metric_key LIKE \'summary_%\' ORDER BY metric_date ASC');
        if ($summaryStmt) {
            $summaryStmt->bind_param('iss', $parentId, $legacyMetricDate, $metricDate);
            $summaryStmt->execute();
            $summaryResult = $summaryStmt->get_result();
            while ($row = $summaryResult->fetch_assoc()) {
                if (array_key_exists($row['metric_key'], $summaryValues)) {
                    $summaryValues[$row['metric_key']] = (float)$row['metric_value'];
                }
            }
            $summaryStmt->close();
        }

        $childrenStmt = $conn->prepare("SELECT s.id, s.mobile, s.code, s.code1, s.code2, s.code3, s.code4, s.code5, s.codechorkamukala, s.status, s.createdate,
            CASE
                WHEN s.code = ? THEN 1
                WHEN s.code1 = ? THEN 2
                WHEN s.code2 = ? THEN 3
                WHEN s.code3 = ? THEN 4
                WHEN s.code4 = ? THEN 5
                WHEN s.code5 = ? THEN 6
                ELSE 0
            END AS calculated_level,
            (SELECT COALESCE(SUM(t.motta), 0) FROM thevani t WHERE t.balakedara = s.id AND t.sthiti = '1' AND DATE(t.dinankavannuracisi) = ?) AS calculated_deposit,
            (SELECT COALESCE(SUM(v.ayoga), 0) FROM vyavahara v WHERE v.koduvavanu = s.id AND DATE(v.tiarikala) = ?) AS calculated_commission
            FROM shonu_subjects s
            WHERE s.id <> ? AND (s.code = ? OR s.code1 = ? OR s.code2 = ? OR s.code3 = ? OR s.code4 = ? OR s.code5 = ?)
            ORDER BY s.id DESC");
        if ($childrenStmt) {
            $owncode = (string)$parent['owncode'];
            $childrenStmt->bind_param('ssssssssissssss', $owncode, $owncode, $owncode, $owncode, $owncode, $owncode, $metricDate, $metricDate, $parentId, $owncode, $owncode, $owncode, $owncode, $owncode, $owncode);
            $childrenStmt->execute();
            $childrenResult = $childrenStmt->get_result();
            while ($child = $childrenResult->fetch_assoc()) {
                $child['overrides'] = array_fill_keys(array_keys($childFields), null);
                $overrideStmt = $conn->prepare('SELECT metric_key, metric_value FROM subordinate_metric_overrides WHERE user_id = ? AND metric_date IN (?, ?) AND metric_key IN (\'level\', \'deposit_amount\', \'commission\') ORDER BY metric_date ASC');
                if ($overrideStmt) {
                    $childId = (int)$child['id'];
                    $overrideStmt->bind_param('iss', $childId, $legacyMetricDate, $metricDate);
                    $overrideStmt->execute();
                    $overrideResult = $overrideStmt->get_result();
                    while ($override = $overrideResult->fetch_assoc()) {
                        if (array_key_exists($override['metric_key'], $child['overrides'])) {
                            $child['overrides'][$override['metric_key']] = (float)$override['metric_value'];
                        }
                    }
                    $overrideStmt->close();
                }
                $children[] = $child;
            }
            $childrenStmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $parent && $error === '') {
    $adminId = (int)($_SESSION['unohs'] ?? 0);
    $adminName = (string)($_SESSION['nirvahaka_hesaru'] ?? 'admin');
    $newSummary = [];
    foreach ($summaryFields as $key => $meta) {
        $raw = $_POST['summary'][$key] ?? 0;
        $newSummary[$key] = max(0.0, round(is_numeric($raw) ? (float)$raw : 0.0, 2));
    }
    $newChildren = [];
    foreach ($children as $child) {
        $childId = (int)$child['id'];
        $posted = $_POST['child'][$childId] ?? [];
        $newChildren[$childId] = [
            'level' => max(0, min(6, (int)($posted['level'] ?? ($child['overrides']['level'] ?? $child['calculated_level'])))),
            'deposit_amount' => max(0.0, round(is_numeric($posted['deposit_amount'] ?? null) ? (float)$posted['deposit_amount'] : (float)($child['overrides']['deposit_amount'] ?? $child['calculated_deposit']), 2)),
            'commission' => max(0.0, round(is_numeric($posted['commission'] ?? null) ? (float)$posted['commission'] : (float)($child['overrides']['commission'] ?? $child['calculated_commission']), 2)),
        ];
    }

    $conn->begin_transaction();
    $upsert = null;
    $history = null;
    try {
        $upsert = $conn->prepare('INSERT INTO subordinate_metric_overrides (user_id, metric_date, metric_key, metric_value, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), admin_id = VALUES(admin_id), admin_name = VALUES(admin_name)');
        $history = $conn->prepare('INSERT INTO subordinate_metric_override_history (user_id, metric_date, metric_key, old_value, new_value, admin_id, admin_name) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if (!$upsert || !$history) {
            throw new RuntimeException('prepare failed');
        }
        $saveMetric = function (int $targetId, string $key, float $old, float $new) use ($upsert, $history, $metricDate, $adminId, $adminName): void {
            $upsert->bind_param('issdis', $targetId, $metricDate, $key, $new, $adminId, $adminName);
            if (!$upsert->execute()) {
                throw new RuntimeException('upsert failed');
            }
            if (abs($old - $new) > 0.00001) {
                $history->bind_param('issddis', $targetId, $metricDate, $key, $old, $new, $adminId, $adminName);
                if (!$history->execute()) {
                    throw new RuntimeException('history failed');
                }
            }
        };
        foreach ($newSummary as $key => $value) {
            $saveMetric($parentId, $key, (float)($summaryValues[$key] ?? 0), $value);
        }
        foreach ($newChildren as $childId => $metrics) {
            $old = [];
            foreach ($children as $child) {
                if ((int)$child['id'] === (int)$childId) {
                    $old = $child['overrides'];
                    break;
                }
            }
            $saveMetric((int)$childId, 'level', (float)($old['level'] ?? 0), (float)$metrics['level']);
            $saveMetric((int)$childId, 'deposit_amount', (float)($old['deposit_amount'] ?? 0), (float)$metrics['deposit_amount']);
            $saveMetric((int)$childId, 'commission', (float)($old['commission'] ?? 0), (float)$metrics['commission']);
        }
        $conn->commit();
        $summaryValues = $newSummary;
        foreach ($children as &$child) {
            $id = (int)$child['id'];
            if (isset($newChildren[$id])) {
                $child['overrides'] = $newChildren[$id];
            }
        }
        unset($child);
        $notice = 'Subordinate Data saved for UID ' . $parentId . ' on ' . $metricDate . '.';
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = 'The data could not be saved. No changes were applied.';
    }
    if ($upsert) { $upsert->close(); }
    if ($history) { $history->close(); }
}

$historyRows = [];
if ($parent && $parentId > 0) {
    $historyStmt = $conn->prepare('SELECT user_id, metric_date, metric_key, old_value, new_value, admin_name, created_at FROM subordinate_metric_override_history WHERE user_id = ? AND metric_date IN (?, ?) ORDER BY id DESC LIMIT 150');
    if ($historyStmt) {
        $historyStmt->bind_param('iss', $parentId, $legacyMetricDate, $metricDate);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        while ($row = $historyResult->fetch_assoc()) {
            $historyRows[] = $row;
        }
        $historyStmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Subordinate Data Editor</title>
<style>
body{margin:0;background:#f3f6fb;color:#172033;font-family:Arial,sans-serif}.top{background:#111b4b;color:#fff;padding:18px 24px;display:flex;justify-content:space-between;align-items:center}.top a{color:#fff;text-decoration:none;margin-left:15px}.wrap{max-width:1180px;margin:28px auto;padding:0 16px}.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px #17203318;padding:22px;margin-bottom:20px}.lookup{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.lookup label{display:flex;flex-direction:column;gap:7px;font-weight:600}.lookup input{min-width:220px}.button,button{border:0;border-radius:7px;background:#2563eb;color:#fff;padding:11px 17px;text-decoration:none;cursor:pointer;font-size:14px}.secondary{background:#64748b}.notice,.error{padding:12px;border-radius:7px;margin:12px 0}.notice{background:#e8f7ee;color:#147a3d}.error{background:#fff0f0;color:#a61b1b}.user{background:#eef4ff;border-radius:8px;padding:12px;margin:14px 0}.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.field{display:flex;flex-direction:column;gap:6px}.field label{font-weight:600;font-size:14px}.field small{color:#667085}.field input{padding:10px;border:1px solid #cfd5df;border-radius:7px;font-size:14px;box-sizing:border-box}.save{margin-top:18px;width:100%;font-size:16px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:9px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{background:#f8fafc}td input{width:110px;padding:8px;border:1px solid #cfd5df;border-radius:6px}.hint{font-size:12px;color:#667085;margin-top:5px}.date-note{background:#fff7e6;border:1px solid #f0c36d;border-radius:7px;padding:10px;margin-top:12px;color:#7a4b00}.demo-box{background:#fff4e5;border:1px solid #f0ad4e;border-radius:8px;padding:14px;margin-top:18px}.demo-box h4{margin:0 0 8px;color:#8a4b08}.demo-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.demo-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:600}.demo-grid input{padding:8px;border:1px solid #d8b47a;border-radius:6px}.demo-actions{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}.demo-button{background:#d97706}.demo-reset{background:#64748b}.demo-status{margin-top:8px;font-size:12px;color:#8a4b08}.save:disabled{opacity:.55;cursor:not-allowed}@media(max-width:700px){.lookup{display:block}.lookup label{margin-bottom:10px}.lookup input{width:100%;box-sizing:border-box}.summary-grid{grid-template-columns:1fr}.table-wrap{margin:0 -10px}}
</style>
</head>
<body>
<header class="top"><strong>Subordinate Data Editor</strong><nav><a href="dashboard.php">Dashboard</a><a href="compass.php">Admin menu</a></nav></header>
<main class="wrap">
<div class="card"><h2>Edit user and subordinate numbers</h2><p>Load a parent UID and a date to edit that day’s six summary numbers and every direct/team subordinate’s level, deposit amount, and commission.</p>
<form class="lookup" method="get"><label>Parent UID<input type="number" name="parent" min="1" value="<?= $parentId ?: '' ?>" required></label><label>Metric date<input type="date" name="metric_date" value="<?= htmlspecialchars($metricDate, ENT_QUOTES, 'UTF-8') ?>" required></label><button type="submit">Load data</button></form>
<div class="date-note">Edits are saved separately for <strong><?= htmlspecialchars($metricDate, ENT_QUOTES, 'UTF-8') ?></strong>. Existing old overrides remain available as a global fallback and are not deleted.</div>
<?php if ($notice): ?><div class="notice"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($parent): ?><div class="user"><strong>UID <?= (int)$parent['id'] ?></strong> · <?= htmlspecialchars((string)$parent['mobile'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)($parent['codechorkamukala'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · Invite code <?= htmlspecialchars((string)$parent['owncode'], ENT_QUOTES, 'UTF-8') ?></div>
<form method="post"><input type="hidden" name="parent" value="<?= $parentId ?>"><input type="hidden" name="metric_date" value="<?= htmlspecialchars($metricDate, ENT_QUOTES, 'UTF-8') ?>"><h3>Summary values for <?= htmlspecialchars($metricDate, ENT_QUOTES, 'UTF-8') ?></h3><p class="hint">These values override the six number cards shown on the user’s Subordinate Data page for the selected date.</p><div class="summary-grid">
<?php foreach ($summaryFields as $key => [$label, $kind]): ?><div class="field"><label for="s_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label><input id="s_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" type="number" min="0" step="<?= $kind === 'count' ? '1' : '0.01' ?>" name="summary[<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars(number_format((float)$summaryValues[$key], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" required></div><?php endforeach; ?></div>
<h3>Direct and team subordinate values</h3><p class="hint">Calculated values below are for the selected date. Entering values creates date-specific admin overrides.</p><div class="table-wrap"><table><thead><tr><th>UID</th><th>Mobile</th><th>Calculated level</th><th>Level override</th><th>Calculated deposit</th><th>Deposit override</th><th>Calculated commission</th><th>Commission override</th></tr></thead><tbody>
<?php if (!$children): ?><tr><td colspan="8">No direct or team subordinates found for this UID.</td></tr><?php else: foreach ($children as $child): $childId=(int)$child['id']; $levelValue=$child['overrides']['level'] ?? $child['calculated_level']; $depositValue=$child['overrides']['deposit_amount'] ?? $child['calculated_deposit']; $commissionValue=$child['overrides']['commission'] ?? $child['calculated_commission']; ?><tr><td><?= $childId ?></td><td><?= htmlspecialchars((string)$child['mobile'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int)$child['calculated_level'] ?></td><td><input type="number" min="0" max="6" step="1" name="child[<?= $childId ?>][level]" value="<?= (int)$levelValue ?>"></td><td><?= number_format((float)$child['calculated_deposit'], 2) ?></td><td><input type="number" min="0" step="0.01" name="child[<?= $childId ?>][deposit_amount]" value="<?= number_format((float)$depositValue, 2, '.', '') ?>"></td><td><?= number_format((float)$child['calculated_commission'], 2) ?></td><td><input type="number" min="0" step="0.01" name="child[<?= $childId ?>][commission]" value="<?= number_format((float)$commissionValue, 2, '.', '') ?>"></td></tr><?php endforeach; endif; ?></tbody></table></div><div class="demo-box"><h4>Demo Preview — not saved</h4><p class="hint">From/To range bhar kar loaded UID rows ke Deposit aur Commission fields me sample values preview karo. Ye values database me save nahi hongi; preview active hone par Save button disabled rahega.</p><div class="demo-grid"><label>Deposit from<input id="demo_deposit_from" type="number" min="0" step="0.01" value="100"></label><label>Deposit to<input id="demo_deposit_to" type="number" min="0" step="0.01" value="1000"></label><label>Commission from<input id="demo_commission_from" type="number" min="0" step="0.01" value="10"></label><label>Commission to<input id="demo_commission_to" type="number" min="0" step="0.01" value="50"></label></div><div class="demo-actions"><button type="button" id="demo_fill" class="demo-button">Fill demo values</button><button type="button" id="demo_reset" class="demo-reset">Clear preview</button></div><div id="demo_status" class="demo-status" aria-live="polite"></div></div><button class="save" type="submit">Save <?= htmlspecialchars($metricDate, ENT_QUOTES, 'UTF-8') ?> data</button></form><?php endif; ?></div>
<?php if ($historyRows): ?><div class="card"><h3>Edit history</h3><div class="table-wrap"><table><thead><tr><th>UID</th><th>Date</th><th>Field</th><th>Old</th><th>New</th><th>Admin</th><th>Time</th></tr></thead><tbody><?php foreach ($historyRows as $row): ?><tr><td><?= (int)$row['user_id'] ?></td><td><?= htmlspecialchars((string)$row['metric_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($summaryFields[$row['metric_key']][0] ?? $childFields[$row['metric_key']][0] ?? $row['metric_key'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['old_value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['new_value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['admin_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
</main><script>(function(){var fill=document.getElementById('demo_fill'),reset=document.getElementById('demo_reset'),save=document.querySelector('button.save'),status=document.getElementById('demo_status');if(!fill||!reset||!save||!status)return;var original=[];var active=false;function num(id){var el=document.getElementById(id),value=parseFloat(el&&el.value);return Number.isFinite(value)?Math.max(0,value):0}function randomBetween(min,max){return min===max?min:min+Math.random()*(max-min)}function remember(){original=[];document.querySelectorAll('input[name^="child["][name$="][deposit_amount]"] , input[name^="child["][name$="][commission]"]').forEach(function(el){original.push([el,el.value])})}function setPreview(){var df=num('demo_deposit_from'),dt=num('demo_deposit_to'),cf=num('demo_commission_from'),ct=num('demo_commission_to');if(dt<df){var x=df;df=dt;dt=x}if(ct<cf){var y=cf;cf=ct;ct=y}remember();var count=0;document.querySelectorAll('input[name^="child["][name$="][deposit_amount]"]').forEach(function(el){el.value=randomBetween(df,dt).toFixed(2);count++});document.querySelectorAll('input[name^="child["][name$="][commission]"]').forEach(function(el){el.value=randomBetween(cf,ct).toFixed(2)});active=true;save.disabled=true;status.textContent=count+' UID rows filled for preview only. Save is disabled.'}fill.addEventListener('click',setPreview);reset.addEventListener('click',function(){original.forEach(function(pair){pair[0].value=pair[1]});original=[];active=false;save.disabled=false;status.textContent='Preview cleared. No demo values were saved.'});save.addEventListener('click',function(event){if(active){event.preventDefault();status.textContent='Demo Preview is active. Clear preview before saving real values.'}})})();</script></body></html>
