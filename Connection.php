<?php
$conn = new mysqli(
    getenv('MYSQL_HOST') ?: 'mysql.railway.internal',
    getenv('MYSQL_USER') ?: 'root',
    getenv('MYSQL_PASS') ?: '',
    getenv('MYSQL_DB')   ?: 'railway'
);
if ($conn->connect_error) $conn = null;
?>