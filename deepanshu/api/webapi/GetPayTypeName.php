<?php 
	include "../../conn.php";
	include "../../functions2.php";
	
	header('Content-Type: application/json; charset=utf-8');
	header('Strict-Transport-Security: max-age=31536000');
	header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
	header('Access-Control-Allow-Credentials: true');
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
	header('Access-Control-Allow-Origin: ' . $origin);
	header('vary: Origin');
	
	date_default_timezone_set("Asia/Kolkata");
	$shnunc = date("Y-m-d H:i:s");
	$res = [
		'code' => 11,
		'msg' => 'Method not allowed',
		'msgCode' => 12,
		'serviceNowTime' => $shnunc,
	];
	$shonubody = file_get_contents("php://input");
	$shonupost = json_decode($shonubody, true);
	
	if ($_SERVER['REQUEST_METHOD'] != 'GET') {
		if (isset($shonupost['language']) && isset($shonupost['random']) && isset($shonupost['signature']) && isset($shonupost['timestamp'])) {
			$language = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['language']));
			$random = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['random']));
			$signature = htmlspecialchars(mysqli_real_escape_string($conn, $shonupost['signature']));
			$shonustr = '{"language":'.$language.',"random":"'.$random.'"}';
			$shonusign = strtoupper(md5($shonustr));
			if($shonusign == $signature){
				$bearer = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
				$author = $bearer[1];				
				$is_jwt_valid = is_jwt_valid($author);
				$data_auth = json_decode($is_jwt_valid, 1);
				if($data_auth['status'] === 'Success') {
					$sesquery = "SELECT akshinak
					  FROM shonu_subjects
					  WHERE akshinak = '$author'";
					$sesresult=$conn->query($sesquery);
					$sesnum = mysqli_num_rows($sesresult);
					if($sesnum == 1){
						$data['typelist'][0]['payID'] = 2;
						$data['typelist'][0]['payTypeID'] = 0;
						$data['typelist'][0]['payName'] = 'Expert UPI-QR';
						$data['typelist'][0]['paySysName'] = 'UPI_QR';
						$data['typelist'][0]['payNameUrl'] = '/assets/png/expert-upi-qr.png';
						$data['typelist'][0]['payNameUrl2'] = '/assets/png/expert-upi-qr.png';
$data['typelist'][0]['minPrice'] = 200;
							$data['typelist'][0]['maxPrice'] = 5000;
							$data['typelist'][0]['scope'] = '200|300|500|1000|2000|5000';
						$data['typelist'][0]['typeName'] = 'Expert UPI-QR';
						$data['typelist'][0]['typeNameCode'] = 0;
						$data['typelist'][0]['maxRechargeRifts'] = 0.00;
						$data['typelist'][0]['sort'] = 9; 
						  
						$data['typelist'][1]['payID'] = 1;
						$data['typelist'][1]['payTypeID'] = 0;
						$data['typelist'][1]['payName'] = 'UPI_PHONEPE';
						$data['typelist'][1]['paySysName'] = 'Online Pay';
						$data['typelist'][1]['payNameUrl'] = '/assets/svg/phonepe-upi.svg';
						$data['typelist'][1]['payNameUrl2'] = '/assets/svg/phonepe-upi.svg';
$data['typelist'][1]['minPrice'] = 100;
							$data['typelist'][1]['maxPrice'] = 50000;
							$data['typelist'][1]['scope'] = '100|200|500|1000|5000|10000|50000';
						$data['typelist'][1]['typeName'] = 'UPI_PHONEPE';
						$data['typelist'][1]['typeNameCode'] = 0;
						$data['typelist'][1]['maxRechargeRifts'] = 0.00;
						$data['typelist'][1]['sort'] = 9;
						
						
						$data['typelist'][3]['payID'] = 13;
						$data['typelist'][3]['payTypeID'] = 0;
						$data['typelist'][3]['payName'] = 'PAYTM_UPI';
						$data['typelist'][3]['paySysName'] = 'USDT';
						$data['typelist'][3]['payNameUrl'] = '/assets/png/paytm-upi.png';
						$data['typelist'][3]['payNameUrl2'] = '/assets/png/paytm-upi.png';
$data['typelist'][3]['minPrice'] = 100;
							$data['typelist'][3]['maxPrice'] = 50000;
							$data['typelist'][3]['scope'] = '100|200|500|1000|5000|10000|50000';
						$data['typelist'][3]['typeName'] = 'PAYTM_UPI';
						$data['typelist'][3]['typeNameCode'] = 9205;
						$data['typelist'][3]['maxRechargeRifts'] = 0.00;
						$data['typelist'][3]['sort'] = 5;


                        $data['typelist'][4]['payID'] = 11;
                        $data['typelist'][4]['payTypeID'] = 0;
						$data['typelist'][4]['payName'] = 'USDT';
						$data['typelist'][4]['paySysName'] = 'USDT';
						$data['typelist'][4]['payNameUrl'] = '/assets/png/usdt-deposit.png';
						$data['typelist'][4]['payNameUrl2'] = '/assets/png/usdt-deposit.png';
$data['typelist'][4]['minPrice'] = 10;
							$data['typelist'][4]['maxPrice'] = 50000;
							$data['typelist'][4]['scope'] = '10|20|50|200|500';
						$data['typelist'][4]['typeName'] = 'USDT';
						$data['typelist'][4]['typeNameCode'] = 9205;
						$data['typelist'][4]['maxRechargeRifts'] = 0.02;
						$data['typelist'][4]['sort'] = 5;

                        
						
						// Keep typelist JSON as a sequential array after channel removal.
						$data['typelist'] = array_values($data['typelist']);
						$res['data'] = $data;
						$res['code'] = 0;
						$res['msg'] = 'Succeed';
						$res['msgCode'] = 0;
						http_response_code(200);
						echo json_encode($res);					
					}
					else{
						$res['code'] = 4;
						$res['msg'] = 'No operation permission';
						$res['msgCode'] = 2;
						http_response_code(401);
						echo json_encode($res);
					}					
				}
				else{					
					$res['code'] = 4;
					$res['msg'] = 'No operation permission';
					$res['msgCode'] = 2;
					http_response_code(401);
					echo json_encode($res);					
				}
			}
			else{
				$res['code'] = 5;
				$res['msg'] = 'Wrong signature';
				$res['msgCode'] = 3;
				http_response_code(200);
				echo json_encode($res);				
			}
		}
		else{
			$res['code'] = 7;
			$res['msg'] = 'Param is Invalid';
			$res['msgCode'] = 6;
			http_response_code(200);
			echo json_encode($res);			
		}		
	} else {		
		http_response_code(405);
		echo json_encode($res);
	}
?>