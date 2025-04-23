<?php
// Database configuration
$host = 'localhost';
$dbname = 'lab08';
$username = 'root';
$password = ''; // Update this with your actual MySQL password

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>