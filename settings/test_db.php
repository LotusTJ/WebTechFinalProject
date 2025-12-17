<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('HOSTNAME', 'localhost');  // Try localhost first
define('USERNAME', 'oghenetejiri.etireri');
define('PASSWORD', 'Adeyinka67$');
define('DATABASE', 'webtech_2025A_oghenetejiri_etireri');  // Then try with prefix if this fails

$conn = new mysqli(HOSTNAME, USERNAME, PASSWORD, DATABASE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connection successful!<br>";
    echo "Database: " . DATABASE . "<br>";
    
    // Test if tables exist
    $result = $conn->query("SHOW TABLES");
    echo "<br>Tables in database:<br>";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
}
?>