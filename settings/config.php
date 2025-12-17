<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('HOSTNAME', 'localhost');
define('USERNAME', 'oghenetejiri.etireri');
define('PASSWORD', 'Adeyinka67$');
define('DATABASE', 'webtech_2025A_oghenetejiri_etireri');

function getDBConnection() {
    $conn = new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    return $conn;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
