<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ict_mis';

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
