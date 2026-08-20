<?php
	$conn = mysqli_connect('localhost', 'winningb_paisa', 'winningb_paisa', 'winningb_paisa');
	
	if (!$conn) {
		echo "Error: " . mysqli_connect_error();
		exit();
	}
	
	date_default_timezone_set("Asia/Kolkata"); 
?>