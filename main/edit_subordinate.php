<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';

$parentId = (int)($_REQUEST['parent'] ?? 0);
$userId = (int)($_REQUEST['user'] ?? 0);
$notice = '';
$error = '';

$parentStmt = $conn->prepare('SELECT id, mobile, owncode FROM shonu_subjects WHERE id = ? LIMIT 1');
$parentStmt->bind_param('i', $parentId);
$parentStmt->execute();
$parent = $parentStmt->get_result()->fetch_assoc();
$parentStmt->close();
if (!$parent) {
    http_response_code(404);
    exit('Parent user not found');
}

function load_direct_subordinate(mysqli $conn, int $userId, string $parentCode): ?array {
    $stmt = $conn->prepare('SELECT id, mobile, code, owncode, codechorkamukala, status, createdate FROM shonu_subjects WHERE id = ? AND code = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $parentCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

$user = load_direct_subordinate($conn, $userId, (string)$parent['owncode']);
if (!$user) {
    http_response_code(403);
    exit('This user is not a direct subordinate of the selected parent.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $nickname = trim((string)($_POST['nickname'] ?? ''));
    $newPassword = (string)($_POST['password'] ?? '');
    $status = ((int)($_POST['status'] ?? 0) === 1) ? 1 : 0;

    if (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $error = 'Mobile number must contain 10 to 15 digits.';
    } elseif ($nickname === '' || mb_strlen($nickname) > 32) {
        $error = 'Nickname is required and must be at most 32 characters.';
    } else {
        $dup = $conn->prepare('SELECT id FROM shonu_subjects WHERE mobile = ? AND id <> ? LIMIT 1');
        $dup->bind_param('si', $mobile, $userId);
        $dup->execute();
        $duplicate = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($duplicate) {
            $error = 'That mobile number is already used by another account.';
        } else {
            if ($newPassword !== '') {
                if (strlen($newPassword) < 6 || strlen($newPassword) > 64) {
                    $error = 'Password must be between 6 and 64 characters.';
                } else {
                    $hash = md5($newPassword);
                    $stmt = $conn->prepare('UPDATE shonu_subjects SET mobile = ?, codechorkamukala = ?, password = ?, pwd = ?, status = ? WHERE id = ? AND code = ?');
                    $stmt->bind_param('ssssiss', $mobile, $nickname, $hash, $newPassword, $status, $userId, $parent['owncode']);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare('UPDATE shonu_subjects SET mobile = ?, codechorkamukala = ?, status = ? WHERE id = ? AND code = ?');
                $stmt->bind_param('sssis', $mobile, $nickname, $status, $userId, $parent['owncode']);
                $stmt->execute();
                $stmt->close();
            }
            if ($error === '') {
                header('Location: edit_subordinate.php?parent=' . $parentId . '&user=' . $userId . '&msg=updated');
                exit;
            }
        }
    }
    $user = load_direct_subordinate($conn, $userId, (string)$parent['owncode']);
}
if (($_GET['msg'] ?? '') === 'updated') {
    $notice = 'Subordinate data updated successfully.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit subordinate</title>
<style>body{margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#172033}.top{background:#111b4b;color:#fff;padding:18px 24px;display:flex;justify-content:space-between}.top a{color:#fff;text-decoration:none;margin-left:15px}.wrap{max-width:720px;margin:28px auto;padding:0 16px}.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px #17203318;padding:22px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field{display:flex;flex-direction:column;gap:7px}.full{grid-column:1/-1}label{font-weight:600;font-size:13px}input,select{padding:11px;border:1px solid #cfd5df;border-radius:7px;font-size:14px}.muted{color:#667085;font-size:13px}.notice,.error{padding:11px;border-radius:7px;margin-bottom:14px}.notice{background:#e8f7ee;color:#147a3d}.error{background:#fff0f0;color:#a61b1b}.buttons{display:flex;gap:9px;margin-top:18px}button,a.button{border:0;border-radius:7px;background:#2563eb;color:#fff;padding:11px 16px;text-decoration:none;cursor:pointer}.button.secondary{background:#64748b}@media(max-width:600px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}</style></head>
<body><header class="top"><strong>Edit subordinate</strong><nav><a href="user-details.php?user=<?= (int)$parentId ?>">Back to parent</a><a href="dashboard.php">Dashboard</a></nav></header>
<main class="wrap"><div class="card"><h2>Edit registered subordinate</h2><p class="muted">Parent: <?= htmlspecialchars((string)$parent['mobile']) ?> · Invite code: <?= htmlspecialchars((string)$parent['owncode']) ?></p>
<?php if ($notice): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?><?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="parent" value="<?= (int)$parentId ?>"><input type="hidden" name="user" value="<?= (int)$userId ?>"><div class="grid">
<div class="field"><label>Mobile</label><input required name="mobile" value="<?= htmlspecialchars((string)$user['mobile']) ?>"></div>
<div class="field"><label>Status</label><select name="status"><option value="1" <?= (int)$user['status'] === 1 ? 'selected' : '' ?>>Active</option><option value="0" <?= (int)$user['status'] === 0 ? 'selected' : '' ?>>Disabled</option></select></div>
<div class="field full"><label>Nickname</label><input required maxlength="32" name="nickname" value="<?= htmlspecialchars((string)$user['codechorkamukala']) ?>"></div>
<div class="field full"><label>New password (leave blank to keep current)</label><input type="password" minlength="6" maxlength="64" name="password" placeholder="Optional"></div>
<div class="field full"><label>Referral code</label><input disabled value="<?= htmlspecialchars((string)$user['code']) ?>"><small class="muted">Referral ownership is locked to the selected parent for safety.</small></div>
</div><div class="buttons"><button type="submit">Save changes</button><a class="button secondary" href="user-details.php?user=<?= (int)$parentId ?>">Cancel</a></div></form></div></main></body></html>
