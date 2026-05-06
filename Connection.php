<?php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "irrigation_db";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);

if ($conn->connect_error) {
    $conn = null;
} else {
    if (!$conn->select_db($DB_NAME)) {
        $conn = null;
    }
}
?>