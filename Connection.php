<?php
$DB_HOST = "mysql.railway.internal";
$DB_USER = "root";
$DB_PASS = "dFdVVmMsxnBBDWvLCJTJiFZZVqqNuwVl";
$DB_NAME = "railway";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    $conn = null;
}
?>