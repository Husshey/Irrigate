<?php
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'irrigation_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) $conn = null;
?>