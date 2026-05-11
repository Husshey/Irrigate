<?php
mysqli_report(MYSQLI_REPORT_OFF);
$host = getenv('MYSQLHOST')      ?: 'localhost';
$port = (int)(getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER')      ?: 'root';
$pass = getenv('MYSQLPASSWORD')  ?: '';
$db = getenv('MYSQLDATABASE') ?: 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) $conn = null;
?>