<?php
require_once __DIR__ . '/conn.php';

$getperiodQuery = mysqli_query($conn, "SELECT atadaaidi FROM `gelluonduhogu_zehn` ORDER BY kramasankhye DESC LIMIT 1");
$getperiodRow = $getperiodQuery ? (mysqli_fetch_assoc($getperiodQuery) ?: []) : [];
$periodid = (string)($getperiodRow['atadaaidi'] ?? '');

if ($periodid === '') {
    echo '0';
    exit;
}

$checkResult = mysqli_query($conn, "SELECT 1 FROM `gellaluhogiondu_phalitansa_zehn` WHERE `kalaparichaya` = '" . mysqli_real_escape_string($conn, $periodid) . "' LIMIT 1");
$checkResultRows = $checkResult ? mysqli_num_rows($checkResult) : 0;

if ($checkResultRows > 0) {
    echo '0';
    exit;
}

$periodEscaped = mysqli_real_escape_string($conn, $periodid);
$selectData = mysqli_query($conn, "SELECT 1 FROM `bajikattuttate_zehn` WHERE `kalaparichaya` = '$periodEscaped' LIMIT 1");
if (!$selectData || mysqli_num_rows($selectData) === 0) {
    echo '0';
    exit;
}

$totalQuery = mysqli_query($conn, "SELECT (COALESCE(SUM(ketebida), 0) - (COALESCE(SUM(ketebida), 0) / 100 * 2)) AS totalamount FROM `bajikattuttate_zehn` WHERE `kalaparichaya` = '$periodEscaped'");
$totalRow = $totalQuery ? (mysqli_fetch_assoc($totalQuery) ?: []) : [];
echo (string)($totalRow['totalamount'] ?? '0');
?>
