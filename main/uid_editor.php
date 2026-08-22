<?php
session_start();
if (empty($_SESSION['unohs'])) {
    header('Location: index.php?msg=unauthorized');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>UID Editor</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .uid-card { background:#fff; border-radius:8px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .uid-card h5 { margin-bottom:18px; font-weight:600; }
    .uid-help { color:#6c757d; font-size:.9rem; margin:0 0 18px; }
    .uid-user { display:none; border:1px solid #d9e2ef; border-radius:6px; padding:14px 16px; background:#f8fbff; margin-top:18px; }
    .uid-user strong { color:#1f3b64; }
    .status-message { min-height:24px; margin-top:14px; font-weight:600; }
    .status-message.success { color:#16803c; }
    .status-message.error { color:#c62828; }
    .table td, .table th { vertical-align:middle; white-space:nowrap; }
    @media (max-width:576px) { .uid-card { padding:16px; } }
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
        <ul class="navbar-nav navbar-nav-right"><li class="nav-item dropdown d-flex mr-4"><a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown"><i class="icon-cog"></i></a><div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown"><p class="mb-0 font-weight-normal float-left dropdown-header">Settings</p><a class="dropdown-item preview-item" href="logout.php"><i class="icon-inbox"></i> Logout</a></div></li></ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas"><span class="icon-menu"></span></button>
      </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="user-profile"><div class="user-image"><img src="images/faces/face28.png" alt="admin"></div><div class="user-name">Rᴜᴅʀᴀɴsʜ</div><div class="user-designation">Admin</div></div>
        <?php include __DIR__ . '/compass.php'; ?>
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row"><div class="col-sm-12 mb-4 mb-xl-0"><h4 class="font-weight-bold text-dark">UID Editor</h4></div></div>
          <div class="row">
            <div class="col-md-7 grid-margin stretch-card"><div class="uid-card w-100">
              <h5>Edit user UID</h5>
              <p class="uid-help">Look up the current UID, enter a unique new UID, and confirm the change. All linked wallet, deposit, wager, withdrawal, profile, and referral records are migrated together. The affected user must log in again after the change.</p>
              <form id="lookup-form" autocomplete="off"><div class="form-group"><label for="uid">Current UID</label><div class="input-group"><input id="uid" class="form-control" inputmode="numeric" pattern="[0-9]+" placeholder="e.g. 2257955" required><div class="input-group-append"><button class="btn btn-primary" type="submit">Lookup</button></div></div></div></form>
              <div id="user-card" class="uid-user"><div><strong>Current UID:</strong> <span id="user-uid"></span></div><div><strong>Mobile:</strong> <span id="user-mobile"></span></div><div><strong>Referral code:</strong> <span id="user-code"></span></div><div><strong>Status:</strong> <span id="user-status"></span></div></div>
              <form id="update-form" autocomplete="off" style="display:none"><input type="hidden" id="old-uid"><div class="form-group mt-3"><label for="new-uid">New UID</label><input id="new-uid" class="form-control" inputmode="numeric" pattern="[0-9]+" placeholder="Enter a unique new UID" required></div><button class="btn btn-danger" type="submit">Update UID</button></form>
              <div id="status-message" class="status-message" role="status"></div>
            </div></div>
            <div class="col-md-5 grid-margin stretch-card"><div class="uid-card w-100"><h5>Important</h5><p class="uid-help">UID changes are logged with the old UID, new UID, mobile number, admin account, and time.</p><p class="uid-help">A new UID cannot be used if another account already has it. This editor never changes wallet balance or passwords.</p><p class="uid-help mb-0">Because active JWT sessions contain the old UID, the affected user is logged out and must sign in again.</p></div></div>
          </div>
          <div class="row"><div class="col-12 grid-margin stretch-card"><div class="uid-card w-100"><h5>UID change history</h5><div class="table-responsive"><table class="table table-bordered table-striped" id="history-table"><thead><tr><th>Date</th><th>Old UID</th><th>New UID</th><th>Mobile</th><th>Admin</th></tr></thead><tbody><tr><td colspan="5" class="text-center">Loading...</td></tr></tbody></table></div></div></div></div>
        </div>
        <footer class="footer"><div class="d-sm-flex justify-content-center justify-content-sm-between"><span class="text-muted d-block text-center text-sm-left d-inline-block">Copyright © Rᴜᴅʀᴀɴsʜ 2025</span></div></footer>
      </div>
    </div>
  </div>
  <script src="vendors/base/vendor.bundle.base.js"></script><script src="js/off-canvas.js"></script><script src="js/hoverable-collapse.js"></script><script src="js/template.js"></script>
  <script>
    (function () {
      const $ = (id) => document.getElementById(id);
      const status = (message, error) => { $('status-message').textContent = message || ''; $('status-message').className = 'status-message ' + (error ? 'error' : 'success'); };
      const request = async (data) => { const response = await fetch('uid_action.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body:new URLSearchParams(data)}); const payload = await response.json(); if (!response.ok || !payload.ok) throw new Error(payload.message || 'Request failed'); return payload; };
      const showUser = (user) => { $('user-card').style.display='block'; $('update-form').style.display='block'; $('old-uid').value=user.id; $('user-uid').textContent=user.id; $('user-mobile').textContent=user.mobile || '-'; $('user-code').textContent=user.code || '-'; $('user-status').textContent=String(user.status)==='1' ? 'Active' : 'Inactive'; };
      const loadHistory = async () => { const payload=await request({action:'history'}); const body=$('history-table').querySelector('tbody'); if(!payload.rows.length){body.innerHTML='<tr><td colspan="5" class="text-center">No UID changes yet</td></tr>';return;} body.innerHTML=payload.rows.map((row)=>'<tr><td>'+row.changed_at+'</td><td>'+row.old_uid+'</td><td>'+row.new_uid+'</td><td>'+((row.mobile||'').replace(/[<&>"']/g,'' )||'-')+'</td><td>'+((row.admin_session||'').replace(/[<&>"']/g,'')||'-')+'</td></tr>').join(''); };
      $('lookup-form').addEventListener('submit', async (event) => { event.preventDefault(); try { status('Looking up UID...',false); const payload=await request({action:'lookup',uid:$('uid').value.trim()}); showUser(payload.user); status('User found. Enter a unique new UID.',false); } catch(error) { $('user-card').style.display='none'; $('update-form').style.display='none'; status(error.message,true); } });
      $('update-form').addEventListener('submit', async (event) => { event.preventDefault(); const oldUid=$('old-uid').value; const newUid=$('new-uid').value.trim(); if(!/^\d+$/.test(newUid)){status('Enter a valid numeric new UID.',true);return;} if(!window.confirm('Change UID '+oldUid+' to '+newUid+'? All linked records will be migrated and the user must log in again.')) return; try { status('Updating UID and linked records...',false); const payload=await request({action:'update',old_uid:oldUid,new_uid:newUid}); status(payload.message,false); $('uid').value=payload.newUid; $('new-uid').value=''; const lookup=await request({action:'lookup',uid:payload.newUid}); showUser(lookup.user); await loadHistory(); } catch(error) { status(error.message,true); } });
      loadHistory().catch((error)=>status(error.message,true));
    }());
  </script>
</body>
</html>
