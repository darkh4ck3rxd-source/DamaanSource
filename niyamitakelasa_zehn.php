<?php
date_default_timezone_set('Asia/Kolkata');
include("serive/samparka.php");
include("nayakaphalitansa_mulaka_unohs_zehn.php");

$currentDate = date('Ymd');
$timeInSeconds = time() % 86400;
$sequenceNumber = intval($timeInSeconds / 30);
$uniqueSequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

$bartamankalakrama = $currentDate . "10005" . $uniqueSequence;
$bartamankalakrama = $bartamankalakrama + 1;

$prathama = $bartamankalakrama;
$sesa = $currentDate . "10005" . sprintf("%04d", ceil(86400 / 30));

$tarika = date('Y-m-d H:i:s');

$dekhakalakrama = mysqli_query($conn, "select atadaaidi from `gelluonduhogu_zehn` order by kramasankhye desc limit 1");
$kaladhadi = mysqli_num_rows($dekhakalakrama);
$kalakramadhadi = mysqli_fetch_array($dekhakalakrama);

if ($kaladhadi == null) {
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_zehn` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $bartamankalakrama . "','" . $tarika . "')");
} else if ($prathama > $kalakramadhadi['atadaaidi']) {
    $katiba = mysqli_query($conn, "TRUNCATE TABLE `gelluonduhogu_zehn`");
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_zehn` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $bartamankalakrama . "','" . $tarika . "')");
} else {
    $parabartikrama = $bartamankalakrama;
    $tathya = mysqli_query($conn, "INSERT INTO `gelluonduhogu_zehn` (`atadaaidi`,`dinankavannuracisi`) VALUES ('" . $parabartikrama . "','" . $tarika . "')");
}
$safa_shonu = mysqli_query($conn, "UPDATE hastacalita_phalitansa_zehn SET sthiti='0'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Real-Time Clock & Countdown</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 40px;
            text-align: center;
        }
        .clock, .countdown {
            font-size: 2rem;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Real-Time Data Updater</h1>
    <div class="clock">Current Time: <span id="current-time"></span></div>
    <div class="countdown">Next Update In: <span id="countdown-timer"></span> seconds</div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById("current-time").innerText = now.toLocaleTimeString();
        }

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const secondsPastMidnight = now % 86400;
            const secondsToNextInterval = 30 - (secondsPastMidnight % 30);
            document.getElementById("countdown-timer").innerText = secondsToNextInterval;
        }

        function startRealTimeUpdates() {
            updateClock();
            updateCountdown();
            setInterval(() => {
                updateClock();
                updateCountdown();
            }, 1000);
        }

        startRealTimeUpdates();
    </script>
</body>
</html>
