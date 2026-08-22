		<?php
			ini_set('display_errors', '0');
			ini_set('display_startup_errors', '0');
			$adminSession = $_SESSION['unohs'] ?? '';
			$adminSessionEscaped = mysqli_real_escape_string($conn, $adminSession);
			$chkserial = mysqli_query($conn,"SELECT * FROM `nirvahaka_shonu` WHERE `unohs`='".$adminSessionEscaped."'");
			$salu = $chkserial ? (mysqli_fetch_assoc($chkserial) ?: []) : [];
			$dashboard = (int)($salu['dashboard'] ?? 0);
			$wingomanager = (int)($salu['wingomanager'] ?? 0);
			$k3manager = (int)($salu['k3manager'] ?? 0);
			$d5manager = (int)($salu['5dmanager'] ?? 0);
			$finance = (int)($salu['finance'] ?? 0);
			$managegame = (int)($salu['managegame'] ?? 0);
            $support = (int)($salu['support'] ?? 0);
		?>
		<ul class="nav">
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="icon-box menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
		  <?php
			if($wingomanager == 1){
		  ?>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
              <i class="icon-disc menu-icon"></i>
              <span class="menu-title">WinGo Manager</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
              <ul class="nav flex-column sub-menu">
                <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="wingo10min.php">WinGo 30Sec</a></li>
                <li class="nav-item"> <a class="nav-link" href="wingo1min.php">WinGo 1 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="wingo3min.php">WinGo 3 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="wingo5min.php">WinGo 5 Min</a></li>
              </ul>
            </div>
          </li>
		  <?php
			}
			if($k3manager == 1){
		  ?>
		  <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-1" aria-expanded="false" aria-controls="ui-basic-1">
              <i class="icon-disc menu-icon"></i>
              <span class="menu-title">K3 Manager</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic-1">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="k31min.php">K3 1 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="k33min.php">K3 3 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="k35min.php">K3 5 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="k310min.php">K3 10 Min</a></li>
              </ul>
            </div>
          </li>
		  <?php
			}
			if($d5manager == 1){
		  ?>
		  <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-2" aria-expanded="false" aria-controls="ui-basic-2">
              <i class="icon-disc menu-icon"></i>
              <span class="menu-title">5D Manager</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic-2">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="5d1min.php">5D 1 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="5d3min.php">5D 3 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="5d5min.php">5D 5 Min</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="5d10min.php">5D 10 Min</a></li>
              </ul>
            </div>
          </li>
		  <?php
			}
			if($finance == 1){
		  ?>
		  <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-3" aria-expanded="false" aria-controls="ui-basic-3">
              <i class="icon-book menu-icon"></i>
              <span class="menu-title">Finance</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic-3">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="addupi.php">Add Upi</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="usdtkids.php">USDT RATE </a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="addusdt.php">Add Usdt</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="addupiimg.php">Add Upi Image</a></li>
              </ul>			  
			  <ul class="nav flex-column sub-menu">
					<li class="nav-item"> <a class="nav-link" href="addimgusdt_2.php">Add Usdt Image</a></li>
			  </ul>
			  <ul class="nav flex-column sub-menu">
					<li class="nav-item"> <a class="nav-link" href="payment_settings.php">Payment QR Settings</a></li>
			  </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="deposit_update.php">Deposit Update</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="manage_withdraw.php">Withdraw Apply</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="withdraw_accept_list.php">Withdraw Sent</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="withdraw_reject_list.php">Withdraw Reject</a></li>
              <!--</ul>-->
              <!--<ul class="nav flex-column sub-menu">-->
              <!--  <li class="nav-item"> <a class="nav-link" href="update_with_pay.php">Update Gateway</a></li>-->
              <!--</ul>-->
            </div>
          </li>
          
          
          <?php
			}
			if($support == 1){
		  ?>
		  <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-5" aria-expanded="false" aria-controls="ui-basic- 5">
              <i class="icon-book menu-icon"></i>
              <span class="menu-title">support</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic-5">
              
            
              
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="deposite.php">Deposite Problem</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="withprob.php">Withdrawal problem</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="ifscm.php">IFSC Modification</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="bankm.php">Bank Modification</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="gamep.php">Game Problem</a></li>
              </ul>
            </div>
          </li>
          
          
          
		  <?php
			}
			if($managegame == 1){
		  ?>
		  <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic-4" aria-expanded="false" aria-controls="ui-basic-4">
              <i class="icon-head menu-icon"></i>
              <span class="menu-title">Manage Game</span>
              <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic-4">
                
                <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="userbonus.php">Bonus Manage</a></li>
              </ul>
              
                <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="adminpass.php">Admin Password</a></li>
                  </ul>
              
              <div class="collapse" id="ui-basic-4">
                <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="autobanuser.php">Check Same IP</a></li>
                  </ul>
                
                
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="abmain.php">Site Maintance</a></li>
              </ul>
                
              <!--   <ul class="nav flex-column sub-menu">-->
              <!--  <li class="nav-item"> <a class="nav-link" href="banbybet.php">Banned lligal Users</a></li>-->
              <!--</ul>-->
                
                 <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="bannedusers.php">See Banned Users</a></li>
              </ul>
                
			  <!--<ul class="nav flex-column sub-menu">-->
     <!--           <li class="nav-item"> <a class="nav-link" href="updatejet.php">Jet Max Value</a></li>-->
     <!--         </ul>-->
     <!--         <ul class="nav flex-column sub-menu">-->
     <!--           <li class="nav-item"> <a class="nav-link" href="manage_support.php">Users Query S2</a></li>-->
     <!--         </ul>-->
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="manage_user.php">Users</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="banners.php">Banner Manager</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="subordinate_metrics.php">Subordinate Editor</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="wager_management.php">Wager Management</a></li>
                <li class="nav-item"> <a class="nav-link" href="agency_metrics.php">Agency Data Editor</a></li>
              </ul>
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="manage_salary.php">Daily Salary</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="addgiftcode.php">Gift Code</a></li>
              </ul>
			  <!--<ul class="nav flex-column sub-menu">-->
     <!--           <li class="nav-item"> <a class="nav-link" href="addtelegram.php">Telegram</a></li>-->
     <!--         </ul>-->
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="addadmin.php">Add Admin</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="demouser.php">Demo User</a></li>
              </ul>
			  <ul class="nav flex-column sub-menu">
                <li class="nav-item"> <a class="nav-link" href="agentuser.php">Agent User</a></li>
              </ul>
            </div>
          </li>
		  <?php
			}
		  ?>
		 <li class="nav-item">
                    <a href="https://jalwa9.xyz" class="nav-link">
                        <i class="nav-icon fa fa-sign-out" aria-hidden="true"></i>
                        <p>Go To Website</p>
                    </a>
                </li>
        </ul>