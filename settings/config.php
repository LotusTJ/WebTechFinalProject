<?php

define('HOSTNAME', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DATABASE', 'meal_maker');

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
