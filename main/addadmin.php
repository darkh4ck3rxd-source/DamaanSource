<?php
session_start();
if($_SESSION['unohs'] == null){
    header("location:index.php?msg=unauthorized");
    exit();
}
?>
<?php
include ("conn.php");
    
if(isset($_POST['serial']) && isset($_POST['maxusers'])){
    $serial = mysqli_real_escape_string($conn, $_POST['serial']);
    $chkserial = mysqli_query($conn,"SELECT * FROM `nirvahaka_shonu` WHERE `nirvahaka_hesaru`='$serial'");
    
    if(mysqli_num_rows($chkserial)==0){
        $maxusers = mysqli_real_escape_string($conn, $_POST['maxusers']);
        $dashboard = isset($_POST['dashboard']) ? 1 : 0;
        $wingomanager = isset($_POST['wingomanager']) ? 1 : 0;
        $k3manager = isset($_POST['k3manager']) ? 1 : 0;
        $d5manager = isset($_POST['d5manager']) ? 1 : 0;
        $finance = isset($_POST['finance']) ? 1 : 0;
        $managegame = isset($_POST['managegame']) ? 1 : 0;
        $status = 1;
        
        // Use prepared statement to prevent SQL injection
        $sql_q = "INSERT INTO nirvahaka_shonu 
                 (hesaru, nirvahaka_hesaru, guptapada, sthiti, dashboard, wingomanager, k3manager, d5manager, finance, managegame) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql_q);
        $hashed_password = md5($maxusers);
        mysqli_stmt_bind_param($stmt, "sssiiiiiii", 
            $serial, $serial, $hashed_password, $status, 
            $dashboard, $wingomanager, $k3manager, $d5manager, $finance, $managegame);
        
        if(mysqli_stmt_execute($stmt)){
            echo '<script type="text/JavaScript"> alert("Admin Added Successfully"); </script>';
        } else {
            // Show actual error for debugging
            echo '<script type="text/JavaScript"> alert("Admin Add Failed: '.mysqli_error($conn).'"); </script>';
        }
        mysqli_stmt_close($stmt);
    }
    else{
        echo '<script type="text/JavaScript"> alert("Duplicate Username"); </script>';
    }
}    
?>