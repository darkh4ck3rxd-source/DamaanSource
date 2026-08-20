<?php
// Railway MySQL connection. Values come from service variables so the app works
// both locally and in production without hardcoded credentials.
date_default_timezone_set('Asia/Kolkata');

$dbServer = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$dbPassword = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';
$dbName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$dbPort = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);

$conn = mysqli_init();
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if (!mysqli_real_connect($conn, $dbServer, $dbUser, $dbPassword, $dbName, $dbPort)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode([
        'code' => 500,
        'msg' => 'Database connection failed',
        'error' => mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');
