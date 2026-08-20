<?php
header('Content-type: text/plain; charset=utf-8');
include("../serive/samparka.php");

// Existing code to process amount
if(isset($_GET['amount'])){
    $ramt = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['amount']));
    $payTypeID = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
} else{
    $ramt = 0;
}
if ($payTypeID == 1023) {
    $payName = 'SG-pay';
} elseif ($payTypeID == 1124) {
    $payName = 'TB-pay';
} elseif ($payTypeID == 1030) {
    $payName = 'LG-pay';
} elseif ($payTypeID == 1029) {
    $payName = 'FAST-UPIPay';
} elseif ($payTypeID == 1021) {
    $payName = 'YaYa-APPpay';
} elseif ($payTypeID == 1010) {
    $payName = 'FAST-UPIpay';
} elseif ($payTypeID == 1012) {
    $payName = 'Super-ORpay';
} elseif ($payTypeID == 1013) {
    $payName = 'YaYa-ORpay';
} elseif ($payTypeID == 1014) {
    $payName = 'UPI x QR';
} elseif ($payTypeID == 1015) {
    $payName = 'SunPay';
} elseif ($payTypeID == 2123) {
    $payName = 'UPAY-USDT';
} elseif ($payTypeID == 2190) {
    $payName = 'UU-USDT';
} elseif ($payTypeID == 2191) {
    $payName = '7Day-PayTM';
} elseif ($payTypeID == 2192) {
    $payName = 'UPI-PayTM';
}


$dot_pos = strpos($ramt, '.');
if ($dot_pos === false) {
    $ramt = $ramt . '.00';
} else {
    $after_dot = substr($ramt, $dot_pos + 1);
    $after_dot_length = strlen($after_dot);
    if ($after_dot_length > 2) {
        $after_dot = substr($after_dot, 0, 2);
        $ramt = substr($ramt, 0, $dot_pos + 1) . $after_dot;
    } elseif ($after_dot_length < 2) {
        $zeros_to_add = 2 - $after_dot_length;
        $ramt = $ramt . str_repeat('0', $zeros_to_add);
    }
}

$date = date("Ymd");
$time = time();
$serial = $date . $time . rand(100000, 999900);

$tyid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['tyid']));
$uid = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['uid']));
$sign = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['sign']));
$urlInfo = htmlspecialchars(mysqli_real_escape_string($conn, $_GET['urlInfo']));

// Insert into thevani for all users
$createdate = date("Y-m-d H:i:s");
$isDemo = $conn->query("SELECT 1 FROM demo WHERE balakedara = '$uid'")->num_rows > 0;
$sthiti = $isDemo ? '1' : '0'; // 1 for demo (success), 0 for pending payment

$insertQuery = "
    INSERT INTO `thevani` (`balakedara`, `motta`, `dharavahi`, `mula`, `ullekha`, `duravani`, `ekikrtapavati`, `dinankavannuracisi`, `madari`, `pavatiaidi`, `sthiti`) 
    VALUES ('$uid', '$ramt', '$serial', '$payName', 'N/A', 'N/A', 'N/A', '$createdate', '1005', '2', '$sthiti')
";
$conn->query($insertQuery);

if ($isDemo) {
    // Update balance for demo users immediately
  

// Proceed to payment gateway for non-demo users
$res = [
    'code' => 405,
    'message' => 'Illegal access!',
];

if (isset($_GET['tyid'], $_GET['amount'], $_GET['uid'], $_GET['sign'], $_GET['urlInfo'])) {
    $orderid = $serial;
    $amount = $ramt;
    $notify_url = "https://damaansource-production.up.railway.app/pay/rswebhook.php";
    $redirect_url = "https://damaansource-production.up.railway.app/#/main";
    $merchantId = "INR222544";
    $key = "rspay_token_1745297728330";

    $data = [
        "merchantId" => $merchantId,
        "merchantOrderId" => $orderid,
        "amount" => $amount,
        "type" => 2,
        "paymentCurrency" => "INR",
        "notifyUrl" => $notify_url,
        "userName" => "NONE",
        "ext" => "Test",
        "redirectUrl" => $redirect_url,
    ];

    ksort($data);
    $queryString = urldecode(http_build_query($data));
    $data['sign'] = hash('sha256', $queryString . "&key=" . $key);

    $apiUrl = "https://api.rs-pay.cc/apii/in/createOrder";
    $jsonData = json_encode($data);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch);
    } else {
        $responseData = json_decode($response, true);
        if ($responseData && $responseData['status'] == "200") {
            header('Location: ' . $responseData['data']['payUrl']);
            exit;
        } else {
            echo "Error: Unable to process payment.";
            var_dump($response);
        }
    }

    curl_close($ch);
} else {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode($res);
}
?>