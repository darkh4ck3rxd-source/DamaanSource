<?php
header("Access-Control-Allow-Origin: http://diuvin.shop");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Asia/Kolkata');
$serviceNowTimeFormatted = date('Y-m-d H:i:s');

$jsonData = '{
    "data": [
        {
            "id": 1,
            "typeNameCode": 9301,
            "categoryCode": "Lottery",
            "categoryName": "彩票",
            "state": 1,
            "sort": 10,
            "categoryImg": "/assets/png/jalwa-lottery-billiards.png"
        },
        {
            "id": 8,
            "typeNameCode": 9308,
            "categoryCode": "Flash",
            "categoryName": "小游戏",
            "state": 1,
            "sort": 9,
            "categoryImg": "/assets/png/game_dice-596db528.png"
        },
        {
            "id": 2,
            "typeNameCode": 9302,
            "categoryCode": "Popular",
            "categoryName": "热门游戏",
            "state": 1,
            "sort": 8,
            "categoryImg": "/assets/png/all-5227f2a4.png"
        },
        {
            "id": 4,
            "typeNameCode": 9304,
            "categoryCode": "Slot",
            "categoryName": "电子游戏",
            "state": 1,
            "sort": 6,
            "categoryImg": "/assets/png/casino_a-037fd34b.png"
        },
        {
            "id": 3,
            "typeNameCode": 9303,
            "categoryCode": "Fish",
            "categoryName": "捕鱼游戏",
            "state": 1,
            "sort": 5,
            "categoryImg": "/assets/png/fishing_a-8b8f8c2c.png"
        },
        {
            "id": 7,
            "typeNameCode": 9307,
            "categoryCode": "Chess",
            "categoryName": "棋牌游戏",
            "state": 1,
            "sort": 5,
            "categoryImg": "/assets/png/game_dice_sit-13373d77.png"
        },
        {
            "id": 6,
            "typeNameCode": 9306,
            "categoryCode": "Video",
            "categoryName": "视讯游戏",
            "state": 1,
            "sort": 4,
            "categoryImg": "/assets/png/video-0216ce19.png"
        },
        {
            "id": 5,
            "typeNameCode": 9305,
            "categoryCode": "Sport",
            "categoryName": "体育游戏",
            "state": 1,
            "sort": 1,
            "categoryImg": "/assets/png/activityIcon1-67076a48.png"
        }
    ],
    "code": 0,
    "msg": "Succeed",
    "msgCode": 0,
    "serviceNowTime": "' . $serviceNowTimeFormatted . '"
}';

$data = json_decode($jsonData, true);

$response = json_encode($data, JSON_PRETTY_PRINT);

header('Content-Type: application/json');
echo $response;

?>