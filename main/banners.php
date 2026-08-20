<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/conn.php';

$notice = '';
$error = '';
$create = "CREATE TABLE IF NOT EXISTS jalwa_banners (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL DEFAULT '',
    image_url VARCHAR(500) NOT NULL,
    target_url VARCHAR(500) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jalwa_banners_status_order (status, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($create)) {
    $error = 'Unable to prepare banner storage: ' . $conn->error;
}

function valid_banner_url(string $url): bool {
    return preg_match('/^(https?:\/\/|\/)/i', $url) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM jalwa_banners WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: banners.php?msg=deleted');
        exit;
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        $targetUrl = trim((string)($_POST['target_url'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = ((int)($_POST['status'] ?? 0) === 1) ? 1 : 0;

        if ($imageUrl === '' || !valid_banner_url($imageUrl)) {
            $error = 'Image URL must be a full http(s) URL or a local path beginning with /.'.
                ' Example: /assets/png/Banner_20240131164516hwsn.jpg';
        } elseif ($id > 0) {
            $stmt = $conn->prepare('UPDATE jalwa_banners SET title = ?, image_url = ?, target_url = ?, sort_order = ?, status = ? WHERE id = ?');
            $stmt->bind_param('sssiii', $title, $imageUrl, $targetUrl, $sortOrder, $status, $id);
            $stmt->execute();
            $stmt->close();
            header('Location: banners.php?msg=updated');
            exit;
        } else {
            $stmt = $conn->prepare('INSERT INTO jalwa_banners (title, image_url, target_url, sort_order, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssii', $title, $imageUrl, $targetUrl, $sortOrder, $status);
            $stmt->execute();
            $stmt->close();
            header('Location: banners.php?msg=created');
            exit;
        }
    }
}

if (isset($_GET['msg'])) {
    $messages = [
        'created' => 'Banner added successfully.',
        'updated' => 'Banner updated successfully.',
        'deleted' => 'Banner deleted successfully.'
    ];
    $notice = $messages[$_GET['msg']] ?? '';
}

$edit = null;
if (!empty($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT id, title, image_url, target_url, sort_order, status FROM jalwa_banners WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$banners = [];
$result = $conn->query('SELECT id, title, image_url, target_url, sort_order, status, updated_at FROM jalwa_banners ORDER BY sort_order ASC, id ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $banners[] = $row;
    }
}

$form = $edit ?: ['id' => 0, 'title' => '', 'image_url' => '/assets/png/Banner_20240131164516hwsn.jpg', 'target_url' => '', 'sort_order' => 0, 'status' => 1];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Banner Manager</title>
<style>
:root{--navy:#111b4b;--blue:#2563eb;--bg:#f4f7fb;--muted:#667085;--danger:#c62828}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Arial,Helvetica,sans-serif;color:#172033}.top{background:var(--navy);color:#fff;padding:18px 24px;display:flex;justify-content:space-between;align-items:center}.top a{color:#fff;text-decoration:none;margin-left:16px}.wrap{max-width:1180px;margin:24px auto;padding:0 16px}.card{background:#fff;border-radius:12px;box-shadow:0 2px 12px #17203314;padding:20px;margin-bottom:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:flex;flex-direction:column;gap:6px}.field.full{grid-column:1/-1}label{font-weight:600;font-size:13px}input,select{border:1px solid #d0d5dd;border-radius:7px;padding:11px;font-size:14px}button,.button{border:0;border-radius:7px;padding:10px 15px;background:var(--blue);color:#fff;cursor:pointer;text-decoration:none;display:inline-block;font-size:14px}.button.secondary{background:#64748b}.button.danger{background:var(--danger)}.actions{display:flex;gap:8px;align-items:center;margin-top:14px}.notice{background:#e8f7ee;color:#147a3d;padding:11px;border-radius:7px;margin-bottom:14px}.error{background:#fff0f0;color:#a61b1b;padding:11px;border-radius:7px;margin-bottom:14px}.preview{width:220px;height:88px;object-fit:cover;border-radius:8px;background:#eef2f6;border:1px solid #d0d5dd}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:11px 8px;border-bottom:1px solid #eaecf0;vertical-align:middle}th{font-size:12px;text-transform:uppercase;color:var(--muted)}td{font-size:13px}.thumb{width:150px;height:58px;object-fit:cover;border-radius:6px}.pill{padding:4px 8px;border-radius:20px;font-size:12px}.on{background:#dcfce7;color:#166534}.off{background:#f1f5f9;color:#475569}@media(max-width:720px){.grid{grid-template-columns:1fr}.field.full{grid-column:auto}table{display:block;overflow-x:auto;white-space:nowrap}}
</style>
</head>
<body>
<header class="top"><strong>Jalwa Admin · Banner Manager</strong><nav><a href="dashboard.php">Dashboard</a><a href="manage_user.php">Users</a><a href="../">Website</a></nav></header>
<main class="wrap">
<?php if ($notice !== ''): ?><div class="notice"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<section class="card">
<h2><?= $form['id'] ? 'Edit banner' : 'Add banner' ?></h2>
<form method="post">
<input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
<div class="grid">
<div class="field"><label>Title</label><input name="title" maxlength="150" value="<?= htmlspecialchars($form['title']) ?>" placeholder="Homepage banner"></div>
<div class="field"><label>Sort order</label><input type="number" name="sort_order" value="<?= (int)$form['sort_order'] ?>"></div>
<div class="field full"><label>Image URL or local path</label><input required name="image_url" value="<?= htmlspecialchars($form['image_url']) ?>" placeholder="/assets/png/banner.jpg or https://..."><small>Use a local asset path or an HTTPS image URL. Railway deployment storage is not permanent, so URL/local repository assets are recommended.</small></div>
<div class="field full"><label>Click target URL (optional)</label><input name="target_url" value="<?= htmlspecialchars($form['target_url']) ?>" placeholder="https://example.com or /#/wallet/Recharge"></div>
<div class="field"><label>Status</label><select name="status"><option value="1" <?= (int)$form['status'] === 1 ? 'selected' : '' ?>>Enabled</option><option value="0" <?= (int)$form['status'] === 0 ? 'selected' : '' ?>>Disabled</option></select></div>
<div class="field"><label>Preview</label><img class="preview" src="<?= htmlspecialchars($form['image_url']) ?>" alt="Banner preview" onerror="this.style.opacity=.35"></div>
</div>
<div class="actions"><button type="submit"><?= $form['id'] ? 'Save changes' : 'Add banner' ?></button><?php if ($form['id']): ?><a class="button secondary" href="banners.php">Cancel</a><?php endif; ?></div>
</form>
</section>
<section class="card"><h2>Homepage banners</h2><p style="color:var(--muted)">Auto-swipe is disabled on the homepage. Users can swipe manually; only enabled banners appear.</p>
<table><thead><tr><th>Preview</th><th>Title</th><th>Order</th><th>Status</th><th>Target</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($banners as $banner): ?><tr><td><img class="thumb" src="<?= htmlspecialchars($banner['image_url']) ?>" alt=""></td><td><?= htmlspecialchars($banner['title']) ?></td><td><?= (int)$banner['sort_order'] ?></td><td><span class="pill <?= (int)$banner['status'] ? 'on' : 'off' ?>"><?= (int)$banner['status'] ? 'Enabled' : 'Disabled' ?></span></td><td><?= htmlspecialchars($banner['target_url']) ?></td><td><a class="button" href="banners.php?edit=<?= (int)$banner['id'] ?>">Edit</a> <form style="display:inline" method="post" onsubmit="return confirm('Delete this banner?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$banner['id'] ?>"><button class="button danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$banners): ?><tr><td colspan="6">No custom banners yet. The homepage is using its local fallback banner.</td></tr><?php endif; ?>
</tbody></table></section>
</main></body></html>
