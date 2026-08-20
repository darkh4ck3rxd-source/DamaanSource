<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'winningb_paisa');
define('DB_PASSWORD', 'winningb_paisa');
define('DB_NAME', 'winningb_paisa');

function getDBConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
