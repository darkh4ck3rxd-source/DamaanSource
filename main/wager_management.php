<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/../wager_functions.php';
ensure_wager_adjustments_table($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Wager Management</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .wager-card { background:#fff; border-radius:8px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .wager-card h5 { margin-bottom:18px; font-weight:600; }
    .wager-help { color:#6c757d; font-size:.9rem; margin:0 0 18px; }
    .wager-user { display:none; border:1px solid #d9e2ef; border-radius:6px; padding:14px 16px; background:#f8fbff; margin-top:18px; }
    .wager-user strong { color:#1f3b64; }
    .wager-user .current { color:#007bff; font-weight:700; }
    .wager-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
    .wager-actions button { min-width:145px; }
    .status-message { min-height:24px; margin-top:14px; font-weight:600; }
    .status-message.success { color:#16803c; }
    .status-message.error { color:#c62828; }
    .table td, .table th { vertical-align:middle; white-space:nowrap; }
    .badge-add { background:#d9f5e3; color:#17763a; padding:5px 8px; border-radius:4px; }
    .badge-remove { background:#ffe1e1; color:#a51d1d; padding:5px 8px; border-radius:4px; }
    .badge-waive { background:#fff0c2; color:#805b00; padding:5px 8px; border-radius:4px; }
    @media (max-width: 576px) { .wager-card { padding:16px; } .wager-actions button { width:100%; } }
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="dashboard.php"><img src="images/logo.png" alt="logo"></a>
        <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="images/logo-mini.png" alt="logo"></a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize"><span class="icon-menu"></span></button>
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item dropdown d-flex mr-4">
            <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown"><i class="icon-cog"></i></a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Settings</p>
              <a class="dropdown-item preview-item" href="logout.php"><i class="icon-inbox"></i> Logout</a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas"><span class="icon-menu"></span></button>
      </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="user-profile">
          <div class="user-image"><img src="images/faces/face28.png" alt="admin"></div>
          <div class="user-name">Rᴜᴅʀᴀɴsʜ</div>
          <div class="user-designation">Admin</div>
        </div>
        <?php include __DIR__ . '/compass.php'; ?>
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row"><div class="col-sm-12 mb-4 mb-xl-0"><h4 class="font-weight-bold text-dark">Wager Management</h4></div></div>
          <div class="row">
            <div class="col-md-7 grid-margin stretch-card">
              <div class="wager-card w-100">
                <h5>Manage required betting amount</h5>
                <p class="wager-help">Enter a user UID and look up the account. Manual Add/Remove changes only the manual adjustment. The separate Waive button can clear the current deposit-derived requirement for that UID; it does not change wallet balance or deposit records.</p>
                <form id="lookup-form" autocomplete="off">
                  <div class="form-group">
                    <label for="uid">User UID</label>
                    <div class="input-group">
                      <input id="uid" name="uid" class="form-control" inputmode="numeric" pattern="[0-9]+" placeholder="e.g. 2257955" required>
                      <div class="input-group-append"><button class="btn btn-primary" type="submit">Lookup</button></div>
                    </div>
                  </div>
                </form>
                <div id="user-card" class="wager-user">
                  <div><strong>UID:</strong> <span id="user-uid"></span></div>
                  <div><strong>Mobile:</strong> <span id="user-mobile"></span></div>
                  <div><strong>Current required wager:</strong> <span class="current" id="user-current">₹0.00</span></div>
                  <div><strong>Normal remaining wager:</strong> <span id="user-normal">₹0.00</span></div>
                  <div><strong>Manual adjustment:</strong> <span id="user-manual">₹0.00</span></div>
                  <div><strong>Deposit wager waived:</strong> <span id="user-waived">₹0.00</span></div>
                  <div class="text-muted small mt-2">Completed deposits: ₹<span id="user-deposits">0.00</span> · Investments: ₹<span id="user-investments">0.00</span> · Total bets: ₹<span id="user-bets">0.00</span></div>
                </div>
                <form id="adjust-form" autocomplete="off" style="display:none;">
                  <input type="hidden" id="adjust-uid" name="uid">
                  <div class="form-group mt-3">
                    <label for="amount">Amount</label>
                    <input id="amount" name="amount" class="form-control" inputmode="decimal" placeholder="e.g. 500" required>
                  </div>
                  <div class="form-group">
                    <label for="note">Note (optional)</label>
                    <input id="note" name="note" class="form-control" maxlength="255" placeholder="Reason for this adjustment">
                  </div>
                  <div class="wager-actions">
                    <button type="button" id="add-btn" class="btn btn-success"><i class="fa fa-plus"></i> Add wager</button>
                    <button type="button" id="remove-btn" class="btn btn-danger"><i class="fa fa-minus"></i> Remove wager</button>
                    <button type="button" id="waive-btn" class="btn btn-warning"><i class="fa fa-check"></i> Waive deposit wager</button>
                  </div>
                </form>
                <div id="status-message" class="status-message" role="status"></div>
              </div>
            </div>
            <div class="col-md-5 grid-margin stretch-card">
              <div class="wager-card w-100">
                <h5>How it works</h5>
                <p class="wager-help">Add increases the user’s required betting amount. Remove decreases only the manual amount previously added by an administrator.</p>
                <p class="wager-help">Waive deposit wager records the amount that is being waived and lowers the normal deposit-derived requirement for this UID. It does not alter deposits, bets, or wallet balance.</p>
                <p class="wager-help mb-0">Every manual adjustment and deposit-wager waiver is recorded with UID, admin session, time, and remark.</p>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="wager-card w-100">
                <h5>Recent wager adjustments</h5>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="history-table">
                    <thead><tr><th>Date</th><th>UID</th><th>Mobile</th><th>Operation</th><th>Amount</th><th>Note</th><th>Admin</th></tr></thead>
                    <tbody><tr><td colspan="7" class="text-center">Loading...</td></tr></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12 grid-margin stretch-card">
              <div class="wager-card w-100">
                <h5>Deposit-wager waiver history</h5>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="waiver-history-table">
                    <thead><tr><th>Date</th><th>UID</th><th>Mobile</th><th>Waived amount</th><th>Note</th><th>Admin</th></tr></thead>
                    <tbody><tr><td colspan="6" class="text-center">Loading...</td></tr></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <footer class="footer"><div class="d-sm-flex justify-content-center justify-content-sm-between"><span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © Rᴜᴅʀᴀɴsʜ 2025</span></div></footer>
      </div>
    </div>
  </div>
  <script src="vendors/base/vendor.bundle.base.js"></script>
  <script src="js/off-canvas.js"></script>
  <script src="js/hoverable-collapse.js"></script>
  <script src="js/template.js"></script>
  <script>
    (function () {
      const $ = (id) => document.getElementById(id);
      const status = (message, isError) => {
        $('status-message').textContent = message || '';
        $('status-message').className = 'status-message ' + (isError ? 'error' : 'success');
      };
      const request = async (data) => {
        const response = await fetch('wager_action.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
          body: new URLSearchParams(data)
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.message || 'Request failed');
        return payload;
      };
      const showUser = (user) => {
        $('user-card').style.display = 'block';
        $('adjust-form').style.display = 'block';
        $('adjust-uid').value = user.id;
        $('user-uid').textContent = user.id;
        $('user-mobile').textContent = user.mobile || '-';
        $('user-current').textContent = '₹' + user.currentRequiredWager;
        $('user-normal').textContent = '₹' + user.normalRequiredWager;
        $('user-manual').textContent = '₹' + user.currentManualAdjustment;
        $('user-waived').textContent = '₹' + (user.waivedDepositWager || '0.00');
        $('user-deposits').textContent = user.completedDeposits;
        $('user-investments').textContent = user.investments;
        $('user-bets').textContent = user.totalBets;
      };
      const lookup = async (uid, quiet) => {
        if (!/^\d+$/.test(uid) || Number(uid) < 1) throw new Error('Enter a valid UID');
        const payload = await request({action: 'lookup', uid: uid});
        showUser(payload.user);
        if (!quiet) status('User found. Current required wager: ₹' + payload.user.currentRequiredWager, false);
        return payload.user;
      };
      const loadWaiverHistory = async () => {
        const payload = await request({action: 'waiver_history'});
        const body = $('waiver-history-table').querySelector('tbody');
        if (!payload.rows.length) { body.innerHTML = '<tr><td colspan="6" class="text-center">No deposit-wager waivers yet</td></tr>'; return; }
        body.innerHTML = payload.rows.map((row) => '<tr><td>' + row.created_at + '</td><td>' + row.userid + '</td><td>' + (row.mobile || '-') + '</td><td>₹' + Number(row.amount).toFixed(2) + '</td><td>' + (row.note || '-') + '</td><td>' + (row.admin_session || '-') + '</td></tr>').join('');
      };
      const loadHistory = async () => {
        const payload = await request({action: 'history'});
        const body = $('history-table').querySelector('tbody');
        if (!payload.rows.length) { body.innerHTML = '<tr><td colspan="7" class="text-center">No adjustments yet</td></tr>'; return; }
        body.innerHTML = payload.rows.map((row) => {
          const amount = (Number(row.delta) >= 0 ? '+' : '') + '₹' + Number(row.delta).toFixed(2);
          const badge = row.operation === 'add' ? 'badge-add' : 'badge-remove';
          return '<tr><td>' + row.created_at + '</td><td>' + row.userid + '</td><td>' + (row.mobile || '-') + '</td><td><span class="' + badge + '">' + row.operation + '</span></td><td>' + amount + '</td><td>' + (row.note || '-') + '</td><td>' + (row.admin_session || '-') + '</td></tr>';
        }).join('');
      };
      $('lookup-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        try { status('Looking up UID...', false); await lookup($('uid').value.trim()); }
        catch (error) { $('user-card').style.display = 'none'; $('adjust-form').style.display = 'none'; status(error.message, true); }
      });
      const adjust = async (operation) => {
        const uid = $('adjust-uid').value;
        const amount = $('amount').value.trim();
        if (!uid) throw new Error('Look up a UID first');
        const payload = await request({action: 'adjust', uid: uid, amount: amount, operation: operation, note: $('note').value.trim()});
        status(payload.message + '. Current required wager: ₹' + Number(payload.requiredWager || payload.current).toFixed(2), false);
        $('amount').value = '';
        $('note').value = '';
        await lookup(uid, true);
        await loadHistory();
      };
      $('add-btn').addEventListener('click', async () => { try { await adjust('add'); } catch (error) { status(error.message, true); } });
      $('remove-btn').addEventListener('click', async () => { if (!window.confirm('Remove this manual wager amount from the selected UID?')) return; try { await adjust('remove'); } catch (error) { status(error.message, true); } });
      $('waive-btn').addEventListener('click', async () => {
        const uid = $('adjust-uid').value;
        if (!uid) { status('Look up a UID first', true); return; }
        if (!window.confirm('Waive this UID’s current deposit-derived wager? This changes withdrawal eligibility but not wallet balance or deposits.')) return;
        try {
          const payload = await request({action: 'waive_deposit', uid: uid, note: $('note').value.trim()});
          status(payload.message + '. Current required wager: ₹' + Number(payload.requiredWager || 0).toFixed(2), false);
          $('note').value = '';
          await lookup(uid, true);
          await loadWaiverHistory();
        } catch (error) { status(error.message, true); }
      });
      loadHistory().catch((error) => status(error.message, true));
      loadWaiverHistory().catch((error) => status(error.message, true));
    }());
  </script>
</body>
</html>
