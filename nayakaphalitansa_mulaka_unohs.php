<?php

// **1. Fetch API Flag from DB (sametrend table) - Removed/Bypassed**
// $useAPI = true; // Default to true
// $checkQuery = "SELECT id FROM sametrend LIMIT 1";
// $checkResult = mysqli_query($conn, $checkQuery);
// if ($checkRow = mysqli_fetch_assoc($checkResult)) {
//     $useAPI = ($checkRow['id'] == 1);  // API is used only if sametrend.id == 1
// }

// **2. Check for Manual Value First - Removed/Bypassed**
// $manualNumber = null; // Initialize manual number
// $query = "SELECT manual_number FROM gelluonduhogu_zehn_zehn_1 WHERE id = 1";
// $result = mysqli_query($conn, $query);
// if ($row = mysqli_fetch_assoc($result)) {
//     $manualNumber = !empty($row['manual_number']) ? $row['manual_number'] : null; // Get manual number, or null if empty
// }

// Initialize $defaultNumber
$defaultNumber = null;

// **3. Use CSRNG Lite API to fetch random number (0-9)**
$url = "https://csrng.net/csrng/csrng.php?min=0&max=9";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Removed Content-Type header as it's a GET request and not sending a JSON body
// curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $response) {
    $responseData = json_decode($response, true);
    // CSRNG Lite returns an array, with the random number in the 'random' key of the first element
    if (isset($responseData[0]['random']) && is_numeric($responseData[0]['random'])) {
        $defaultNumber = (int)$responseData[0]['random'];
    } else {
        // Fallback to local rand if API response is not as expected
        $defaultNumber = rand(0, 9);
    }
} else {
    // Fallback to local rand if API call fails
    $defaultNumber = rand(0, 9);
}

// **4. hasted override - Removed/Bypassed**
// $hastedsankhye = null;
// $pachare = mysqli_query($conn,"SELECT sankhye FROM `hastacalita_phalitansa` WHERE sthiti = '1' LIMIT 1");
// $achiki = mysqli_num_rows($pachare);
// if($achiki == 1){
//     $thaka = mysqli_fetch_array($pachare);
//     $hastedsankhye =$thaka['sankhye'];
// }

// The previous logic for manualNumber and hastedsankhye has been completely removed to prioritize the API/random number.
// The code now directly attempts to fetch from CSRNG Lite, with a local rand() fallback.


//** rest of code after data fetch begins here using the determined $defaultNumber variable...

    $samasye = "SELECT atadaaidi
      FROM gelluonduhogu
      ORDER BY kramasankhye DESC LIMIT 1";
    $samasyephalitansa = $conn->query($samasye);
    $samasyesreni = mysqli_fetch_array($samasyephalitansa);
    
    if($samasyesreni['atadaaidi'] != null){
        $gadhipathuli = "SELECT ojana, ketebida
          FROM bajikattuttate
          WHERE kalaparichaya = ".$samasyesreni['atadaaidi']."
          ORDER BY parichaya DESC LIMIT 1";
        $gadhipathuliphala = $conn->query($gadhipathuli);
        $gadhipathulidhadi = mysqli_num_rows($gadhipathuliphala);
        
        if($gadhipathulidhadi >= 1){
            $sabutathya = "SELECT
                SUM(CASE WHEN ojana = 0 THEN ketebida ELSE 0 END) AS ojana_0_misana,
                SUM(CASE WHEN ojana = 1 THEN ketebida ELSE 0 END) AS ojana_1_misana,
                SUM(CASE WHEN ojana = 2 THEN ketebida ELSE 0 END) AS ojana_2_misana,
                SUM(CASE WHEN ojana = 3 THEN ketebida ELSE 0 END) AS ojana_3_misana,
                SUM(CASE WHEN ojana = 4 THEN ketebida ELSE 0 END) AS ojana_4_misana,
                SUM(CASE WHEN ojana = 5 THEN ketebida ELSE 0 END) AS ojana_5_misana,
                SUM(CASE WHEN ojana = 6 THEN ketebida ELSE 0 END) AS ojana_6_misana,
                SUM(CASE WHEN ojana = 7 THEN ketebida ELSE 0 END) AS ojana_7_misana,
                SUM(CASE WHEN ojana = 8 THEN ketebida ELSE 0 END) AS ojana_8_misana,
                SUM(CASE WHEN ojana = 9 THEN ketebida ELSE 0 END) AS ojana_9_misana,
                SUM(CASE WHEN ojana = 10 THEN ketebida ELSE 0 END) AS ojana_10_misana,
                SUM(CASE WHEN ojana = 11 THEN ketebida ELSE 0 END) AS ojana_11_misana,
                SUM(CASE WHEN ojana = 12 THEN ketebida ELSE 0 END) AS ojana_12_misana,
                SUM(CASE WHEN ojana = 13 THEN ketebida ELSE 0 END) AS ojana_13_misana,
                SUM(CASE WHEN ojana = 14 THEN ketebida ELSE 0 END) AS ojana_14_misana
                FROM bajikattuttate WHERE byabaharkarta NOT IN (SELECT balakedara FROM `demo` WHERE `sthiti`='1') AND kalaparichaya = ".$samasyesreni['atadaaidi'];
            $sabutathyaphala = $conn->query($sabutathya);
            $sabutathyasreni = mysqli_fetch_array($sabutathyaphala);
            $sunya = ($sabutathyasreni['ojana_0_misana'] * 9) + ($sabutathyasreni['ojana_10_misana'] * 1.5) + ($sabutathyasreni['ojana_12_misana'] * 4.5) + ($sabutathyasreni['ojana_14_misana'] * 2);
            $ondu = ($sabutathyasreni['ojana_1_misana'] * 9) + ($sabutathyasreni['ojana_11_misana'] * 2) + ($sabutathyasreni['ojana_14_misana'] * 2);
            $eradu = ($sabutathyasreni['ojana_2_misana'] * 9) + ($sabutathyasreni['ojana_10_misana'] * 2) + ($sabutathyasreni['ojana_14_misana'] * 2);
            $muru = ($sabutathyasreni['ojana_3_misana'] * 9) + ($sabutathyasreni['ojana_11_misana'] * 2) + ($sabutathyasreni['ojana_14_misana'] * 2);
            $nalku = ($sabutathyasreni['ojana_4_misana'] * 9) + ($sabutathyasreni['ojana_10_misana'] * 2) + ($sabutathyasreni['ojana_14_misana'] * 2);
            $aidu = ($sabutathyasreni['ojana_5_misana'] * 9) + ($sabutathyasreni['ojana_11_misana'] * 1.5) + ($sabutathyasreni['ojana_12_misana'] * 4.5) + ($sabutathyasreni['ojana_13_misana'] * 2);
            $aru = ($sabutathyasreni['ojana_6_misana'] * 9) + ($sabutathyasreni['ojana_10_misana'] * 2) + ($sabutathyasreni['ojana_13_misana'] * 2);
            $elu = ($sabutathyasreni['ojana_7_misana'] * 9) + ($sabutathyasreni['ojana_11_misana'] * 2) + ($sabutathyasreni['ojana_13_misana'] * 2);
            $entu = ($sabutathyasreni['ojana_8_misana'] * 9) + ($sabutathyasreni['ojana_10_misana'] * 2) + ($sabutathyasreni['ojana_13_misana'] * 2);
            $ombattu = ($sabutathyasreni['ojana_9_misana'] * 9) + ($sabutathyasreni['ojana_11_misana'] * 2) + ($sabutathyasreni['ojana_13_misana'] * 2);
            
            $sanhkyagudika = array($sunya, $ondu, $eradu, $muru, $nalku, $aidu, $aru, $elu, $entu, $ombattu);
            $kanistha = min($sanhkyagudika);
            $kadimesucyanka = $defaultNumber;
    
            if($kadimesucyanka == 0){
                $banna = 'red,violet';
            }
            else if($kadimesucyanka == 5){
                $banna = 'green,violet';
            }
            else if($kadimesucyanka == 1 || $kadimesucyanka == 3 || $kadimesucyanka == 7 || $kadimesucyanka == 9){
                $banna = 'green';
            }
            else if($kadimesucyanka == 2 || $kadimesucyanka == 4 || $kadimesucyanka == 6 || $kadimesucyanka == 8){
                $banna = 'red';
            }       
            $dinanka = date('Y-m-d H:i:s');
            
            $yadrcchikasanke = array_fill(0, 4, null);
            for ($i = 0; $i < 4; $i++) {
              $yadrcchikasanke[$i] = $defaultNumber;
            }
            $yadrcchikasanke[] = $kadimesucyanka;
            $yadrcchikasankhye = (int)implode('', $yadrcchikasanke);
            
            $tathya = mysqli_query($conn,"INSERT INTO `gellaluhogiondu_phalitansa` (`kalaparichaya`,`bele`,`phalitansa`,`banna`,`phalitansadaprakara`,`dinankavannuracisi`) VALUES ('".$samasyesreni['atadaaidi']."','".$yadrcchikasankhye."','".$kadimesucyanka."','".$banna."','uncensored','".$dinanka."')");
            
            if($kadimesucyanka == 0){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 1.5, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '10'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '10' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 4.5, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '12'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '12' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '0'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '0' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '14'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '14' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 1){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '11'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '11' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '1'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '1' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '14'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '14' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 2){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '10'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '10' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '2'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '2' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '14'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '14' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 3){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '11'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '11' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '3'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '3' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '14'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '14' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 4){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '10'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '10' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '4'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '4' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '14'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '14' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 5){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 1.5, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '11'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '11' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 4.5, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '12'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '12' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '5'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '5' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '13'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '13' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 6){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '10'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '10' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '6'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '6' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '13'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '13' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 7){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '11'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '11' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '7'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '7' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '13'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '13' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 8){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '10'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '10' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '8'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '8' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '13'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '13' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            if($kadimesucyanka == 9){
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '11'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '11' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 9, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '9'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '9' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
                
                $nabikarana = "UPDATE bajikattuttate set phalaphala = 'gagner', sesabida = ROUND(sesabida * 2, 2), ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' AND ojana = '13'";
                $conn->query($nabikarana);
                $nabikarana = "UPDATE shonu_kaichila
                INNER JOIN (
                    SELECT byabaharkarta, SUM(sesabida) AS total_paid
                    FROM bajikattuttate
                    WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."' 
                    AND ojana = '13' 
                    AND phalaphala ='gagner'
                    GROUP BY byabaharkarta
                ) AS subquery ON shonu_kaichila.balakedara = subquery.byabaharkarta
                SET shonu_kaichila.motta = TRUNCATE(shonu_kaichila.motta + subquery.total_paid, 2)
                ";
                $conn->query($nabikarana);
            }
            $nabikarana_dui = "UPDATE bajikattuttate set ergebnis = '".$kadimesucyanka."', zufallig = '".$yadrcchikasankhye."', tiarikala = '".$dinanka."' WHERE kalaparichaya = '".$samasyesreni['atadaaidi']."'";
            $conn->query($nabikarana_dui);
        }
        else{
            $yadrcchika = $defaultNumber;
            
            if($yadrcchika == 0){
                $banna = 'red,violet';
            }
            else if($yadrcchika == 5){
                $banna = 'green,violet';
            }
            else if($yadrcchika == 1 || $yadrcchika == 3 || $yadrcchika == 7 || $yadrcchika == 9){
                $banna = 'green';
            }
            else if($yadrcchika == 2 || $yadrcchika == 4 || $yadrcchika == 6 || $yadrcchika == 8){
                $banna = 'red';
            }       
            $dinanka = date('Y-m-d H:i:s');
            
            $yadrcchikasanke = array_fill(0, 4, null);
            for ($i = 0; $i < 4; $i++) {
              $yadrcchikasanke[$i] = $defaultNumber;
            }
            $yadrcchikasanke[] = $yadrcchika;
            $yadrcchikasankhye = (int)implode('', $yadrcchikasanke);
            
            $tathya = mysqli_query($conn,"INSERT INTO `gellaluhogiondu_phalitansa` (`kalaparichaya`,`bele`,`phalitansa`,`banna`,`phalitansadaprakara`,`dinankavannuracisi`) VALUES ('".$samasyesreni['atadaaidi']."','".$yadrcchikasankhye."','".$yadrcchika."','".$banna."','uncensored','".$dinanka."')");
        }
    }
?>