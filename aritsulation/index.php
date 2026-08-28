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

// New, isolated Rupayex deposit channel. Existing gateway channels continue below unchanged.
if (isset($_GET['gateway']) && $_GET['gateway'] === 'rupayex') {
    require_once __DIR__ . '/../main/rupayex_payout.php';
    if (!rupayex_deposit_enabled()) {
        http_response_code(503);
        echo 'Rupayex deposit is not configured.';
        exit;
    }
    $numericAmount = (float)$ramt;
    $numericUid = (int)$uid;
    if ($numericUid < 1 || $numericAmount < 1 || $numericAmount > 100000) {
        http_response_code(400);
        echo 'Invalid deposit request.';
        exit;
    }
    $createdate = date('Y-m-d H:i:s');
    $orderId = 'RX' . date('YmdHis') . random_int(1000, 9999);
    $mobile = '';
    $mobileStmt = $conn->prepare('SELECT mobile FROM shonu_subjects WHERE id = ? LIMIT 1');
    if ($mobileStmt) {
        $mobileStmt->bind_param('i', $numericUid);
        $mobileStmt->execute();
        $mobile = (string)(($mobileStmt->get_result()->fetch_assoc() ?: [])['mobile'] ?? '');
        $mobileStmt->close();
    }
    $insert = $conn->prepare("INSERT INTO thevani (balakedara, motta, dharavahi, mula, ullekha, duravani, ekikrtapavati, dinankavannuracisi, madari, pavatiaidi, sthiti) VALUES (?, ?, ?, 'Expert UPI QR', 'Rupayex', ?, 'N/A', ?, '1006', '2', '0')");
    if (!$insert) { http_response_code(500); echo 'Could not create local deposit order.'; exit; }
    $insert->bind_param('idsss', $numericUid, $numericAmount, $orderId, $mobile, $createdate);
    if (!$insert->execute()) { $insert->close(); http_response_code(500); echo 'Could not create local deposit order.'; exit; }
    $insert->close();
    try {
        rupayex_ensure_deposit_schema($conn);
        $provider = rupayex_create_order($orderId, $numericAmount, $mobile, 'Expert UPI QR deposit');
        $providerBody = $provider['body'];
        $providerData = is_array($providerBody['data'] ?? null) ? $providerBody['data'] : $providerBody;
        $paymentUrl = rupayex_find_payment_url($providerBody);
        $providerOrderId = trim((string)($providerData['order_id'] ?? $providerData['orderId'] ?? $orderId));
        $providerStatus = rupayex_normalize_status((string)($providerData['payment_status'] ?? $providerData['status'] ?? 'PENDING'));
        $rawProvider = json_encode($providerBody, JSON_UNESCAPED_SLASHES);
        $audit = $conn->prepare('INSERT INTO rupayex_orders (local_order_id, provider_order_id, user_id, amount, provider_status, provider_response, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE provider_order_id = VALUES(provider_order_id), provider_status = VALUES(provider_status), provider_response = VALUES(provider_response), updated_at = NOW()');
        if (!$audit) { throw new RuntimeException('Could not save payment order audit.'); }
        $audit->bind_param('ssidss', $orderId, $providerOrderId, $numericUid, $numericAmount, $providerStatus, $rawProvider);
        if (!$audit->execute()) { throw new RuntimeException('Could not save payment order audit.'); }
        $audit->close();
        if ($paymentUrl === '') { throw new RuntimeException('Rupayex did not return a payment URL.'); }
        header('Location: ' . $paymentUrl, true, 302);
        exit;
    } catch (Throwable $exception) {
        $fail = $conn->prepare("UPDATE thevani SET sthiti = '2' WHERE dharavahi = ? AND sthiti = '0'");
        if ($fail) { $fail->bind_param('s', $orderId); $fail->execute(); $fail->close(); }
        error_log('Rupayex create-order error: ' . $exception->getMessage());
        http_response_code(502);
        echo 'Unable to create payment order.';
        exit;
    }
}

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
    $updateQuery = "
        UPDATE `shonu_kaichila`
        SET `motta` = `motta` + $ramt
        WHERE `balakedara` = '$uid'
    ";
    $conn->query($updateQuery);
    header('Location: https://www.jalwagames.site/#/main');
    exit;
}

// Proceed to payment gateway for non-demo users
$res = [
    'code' => 405,
    'message' => 'Illegal access!',
];

if (isset($_GET['tyid'], $_GET['amount'], $_GET['uid'], $_GET['sign'], $_GET['urlInfo'])) {
    $orderid = $serial;
    $amount = $ramt;
    $notify_url = "https://www.jalwagames.site/pay/rswebhook.php";
    $redirect_url = "https://www.jalwagames.site/#/main";
    $merchantId = "INR222570";
    $key = "rspay_token_1747393410286";

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