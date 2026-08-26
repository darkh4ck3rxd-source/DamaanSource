<?php
	session_start();
if (empty($_SESSION['unohs'])) {
			header("location:index.php?msg=unauthorized");
			exit;
		}
?>
<?php
	include ("conn.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/feather/feather.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css"/>
  <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="vendors/jquery-bar-rating/fontawesome-stars-o.css">
  <link rel="stylesheet" href="vendors/jquery-bar-rating/fontawesome-stars.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="plugins/datatables/dataTables.bootstrap.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.2.3/css/fixedHeader.dataTables.min.css">
  <link rel="shortcut icon" href="images/favicon.png" />
  <style>
	.cool-input {
        border: 2px solid #007bff;
        border-radius: 0.25rem;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    .cool-input:focus {
        border-color: #0056b3;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .cool-input::placeholder {
        color: #6c757d;
        opacity: 1;
    }
	.cool-button {
        padding: 0.5rem 1rem;
        font-size: 1rem;
        border-radius: 0.25rem;
        transition: all 0.3s ease;
    }
    .cool-button:hover {
        background-color: #0056b3;
        color: #fff;
    }
    .cool-button.btn-secondary:hover {
        background-color: #343a40;
        color: #fff;
    }
    .withdraw-action { display:inline-block; margin:2px; }
	#copied{
		visibility: hidden;
		z-index: 1;
		position: fixed;
		bottom: 50%;
		background-color: #333;
		color: #fff;
		border-radius: 6px;
		padding: 16px;
		max-width: 250px;
		font-size: 17px;
	}	   
	#copied.show {
		visibility: visible;
		-webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
		animation: fadein 0.5s, fadeout 0.5s 2.5s;
	}
  </style>
</head>
<body>
  <div class="container-scroller">
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="dashboard.php"><img src="images/logo.png" alt="logo"/></a>
        <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="images/logo-mini.png" alt="logo"/></a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
          <span class="icon-menu"></span>
        </button>       
        <ul class="navbar-nav navbar-nav-right">           
          <li class="nav-item dropdown d-flex mr-4 ">
            <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown">
              <i class="icon-cog"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
              <p class="mb-0 font-weight-normal float-left dropdown-header">Settings</p>              
              <a class="dropdown-item preview-item" href="logout.php">
                  <i class="icon-inbox"></i> Logout
              </a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
          <span class="icon-menu"></span>
        </button>
      </div>
    </nav>
    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <div class="user-profile">
          <div class="user-image">
            <img src="images/faces/face28.png">
          </div>
          <div class="user-name">
              Rᴜᴅʀᴀɴsʜ
          </div>
          <div class="user-designation">
              Admin
          </div>
        </div>
        <?php include 'compass.php';?>
      </nav>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12 mb-4 mb-xl-0">
              <h4 class="font-weight-bold text-dark">Manage Withdraw</h4>
            </div>
          </div> 		  		  		  			
		  <div class="row">
            <div class="col-sm-12">
				<form id="formID" name="formID" method="post" action="#" enctype="multipart/form-data">
					<table id="example1" class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Sr.No</th>
								<th>User Mobile</th>
								<th>Amount</th>
								<th>Payout Type</th>
                              	<th>Order ID</th>
								<th>Req. Date</th>
									<th>Action</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$Query=mysqli_query($conn,"select *,(select `mobile` from `shonu_subjects` where `id`=`hintegedukolli`.`balakedara`)as user 
							from `hintegedukolli` where `sthiti`='0' order by shonu desc");
							$i=0;
							$total=0; 
							while($row=mysqli_fetch_array($Query)){
								$i++;
								$total+=$row['motta'];
						?>  
								<tr>
									<td><?php echo $i; ?></td>
									<td><?php echo $row["user"]; ?></td>
									<td><?php echo number_format($row['motta'],2);?></td>
								<td><?php echo ((int)$row['madari'] === 2) ? 'UPI' : (((int)$row['madari'] === 3) ? 'USDT' : 'BANK CARD'); ?></td>
                                    <td><?php echo htmlspecialchars($row["dharavahi"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo date('d-m-Y', strtotime($row['dinankavannuracisi']));?></td>
									<td>
										<a class="btn btn-sm btn-primary withdraw-action" href="withdrawal-accept-reject.php?id=<?php echo encryptor('encrypt', $row['shonu']); ?>" title="Open full update"><i class="fa fa-eye"></i> Update</a>
										<button type="button" class="btn btn-sm btn-success withdraw-action" onclick="withdrawDecision(<?php echo (int)$row['shonu']; ?>, 'accept')"><i class="fa fa-check"></i> Approve</button>
										<button type="button" class="btn btn-sm btn-danger withdraw-action" onclick="withdrawDecision(<?php echo (int)$row['shonu']; ?>, 'reject')"><i class="fa fa-times"></i> Reject</button>
									</td>
								</tr>
						<?php 
							}
						?>
					   
						</tbody>
					</table>
				</form>			  			  
            </div>			
          </div>		  
		</div>
		<footer class="footer">
			<div class="d-sm-flex justify-content-center justify-content-sm-between">
				<span class="text-muted d-block text-center text-sm-left d-sm-inline-block">Copyright © Rᴜᴅʀᴀɴsʜ 2025</span>
			</div>
		</footer>
      </div>     
    </div>
  </div>  
  <script src="vendors/base/vendor.bundle.base.js"></script>
  <script src="js/off-canvas.js"></script>
  <script src="js/hoverable-collapse.js"></script>
  <script src="js/template.js"></script>
  <script src="vendors/chart.js/Chart.min.js"></script>
  <script src="vendors/jquery-bar-rating/jquery.barrating.min.js"></script>
  <script src="js/dashboard.js"></script>
  <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
	  <script>
		function withdrawDecision(id, type) {
			var actionLabel = type === 'accept' ? 'approve' : 'reject';
			if (!window.confirm('Are you sure you want to ' + actionLabel + ' this withdrawal?')) return;
			var remark = window.prompt('Enter remark (optional):', '') || '';
			fetch('manage_withdrawAction.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
				body: new URLSearchParams({id: String(id), type: type, remark: remark})
			}).then(function (response) {
				return response.text().then(function (text) { return {ok: response.ok, text: text.trim()}; });
			}).then(function (result) {
				if (result.ok && (result.text === '1' || result.text === '2')) {
					window.location.reload();
					return;
				}
				window.alert(result.text || 'Unable to update withdrawal');
			}).catch(function () { window.alert('Unable to update withdrawal'); });
		}
		$(function () {
		$('#example1').DataTable({
		  "paging": true,
		  "lengthChange": false,
		  "searching": true,
		  "ordering": false,
		  "info": true,
		  "autoWidth": true,
		  "pageLength": 100
		});
	});
  </script>
</body>

</html>