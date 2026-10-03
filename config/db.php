<?php
$dbConfig = require __DIR__ . '/db.settings.php';

$host = $dbConfig['host'];
$user = $dbConfig['user'];
$password = $dbConfig['password'];
$database = $dbConfig['database'];

// Create connection
$conn = mysqli_connect($host, $user, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Select database
try {
    mysqli_select_db($conn, $database);
} catch (mysqli_sql_exception $e) {
    $conn = null; // Database does not exist, set connection to null
}
?>
