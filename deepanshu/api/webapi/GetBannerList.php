<?php
include "../../conn.php";

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin !== '') {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Vary: Origin');

date_default_timezone_set('Asia/Kolkata');

$fallback = [
    ['url' => '', 'bannerUrl' => '/assets/png/Banner_20240131164516hwsn.jpg'],
    ['url' => '', 'bannerUrl' => '/assets/png/Banner_20240131164516hwsn.jpg'],
    ['url' => '', 'bannerUrl' => '/assets/png/Banner_20240131164516hwsn.jpg']
];

$data = $fallback;
if ($conn instanceof mysqli && !$conn->connect_errno) {
    $create = "CREATE TABLE IF NOT EXISTS jalwa_banners (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL DEFAULT '',
        image_url VARCHAR(500) NOT NULL,
        target_url VARCHAR(500) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        status TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_jalwa_banners_status_order (status, sort_order, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    @mysqli_query($conn, $create);

    $rows = [];
    $result = @mysqli_query($conn, "SELECT image_url, target_url FROM jalwa_banners WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['image_url'])) {
                $rows[] = [
                    'url' => (string)$row['target_url'],
                    'bannerUrl' => (string)$row['image_url']
                ];
            }
        }
    }
    if (count($rows) > 0) {
        $data = $rows;
    }
}

echo json_encode([
    'data' => $data,
    'code' => 0,
    'msg' => 'Succeed',
    'msgCode' => 0,
    'serviceNowTime' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_SLASHES);
