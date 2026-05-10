<?php
$host = getenv('MYSQLHOST')      ?: 'mysql.railway.internal';
$port = (int)(getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER')      ?: 'root';
$pass = getenv('MYSQLPASSWORD')  ?: 'dFdVVmMsxnBBDWvLCJTJiFZZVqqNuwVl';
$db   = getenv('MYSQL_DATABASE') ?: 'railway';

mysqli_report(MYSQLI_REPORT_OFF); // disable exceptions
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) $conn = null;
?>