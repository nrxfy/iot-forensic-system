<?php
// Copy this file to db.php and fill in your actual credentials
$host = 'localhost';
$db   = 'your_database_name';
$user = 'your_username';
$pass = 'your_password';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { 
    die('Database connection failed: ' . $conn->connect_error); 
}
$conn->set_charset('utf8mb4');
?>
