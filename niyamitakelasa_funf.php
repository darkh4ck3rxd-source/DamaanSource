<?php
date_default_timezone_set('Asia/Kolkata');
include("serive/samparka.php");
include("nayakaphalitansa_mulaka_unohs_funf.php");

$currentDate = date('Ymd');
$timeInSeconds = time() % 86400;
$sequenceNumber = intval($timeInSeconds / 300);
$uniqueSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

$bartamankalakrama = $currentDate . "10003" . $uniqueSequence;
$bartamankalakrama = $bartamankalakrama + 1;

$prathama = $bartamankalakrama;
$sesa = $currentDate . "10003" . sprintf("%04d", ceil(86400 / 300));

$tarika = date('Y-m-d H:i:s');

$dekhakalakrama = mysqli_query($conn, "select atadaaidi from `gelluonduhogu_funf` order by kramasankhye desc limit 1");
$kaladhadi = mysqli_num_rows($dekhakalakrama);
$kalakramadhadi = mysqli_fetch_array($dekhakalakrama);

if ($kaladhadi == null) {
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_funf` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $bartamankalakrama . "','" . $tarika . "')");
} else if ($prathama > $kalakramadhadi['atadaaidi']) {
    $katiba = mysqli_query($conn, "TRUNCATE TABLE `gelluonduhogu_funf`");
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_funf` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $bartamankalakrama . "','" . $tarika . "')");
} else {
    $parabartikrama = $bartamankalakrama;
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_funf` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $parabartikrama . "','" . $tarika . "')");
}
$safa_shonu = mysqli_query($conn, "UPDATE hastacalita_phalitansa_funf SET sthiti='0'");

?>