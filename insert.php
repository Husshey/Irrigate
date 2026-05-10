<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('MYSQLHOST')      ?: 'localhost';
$port = (int)(getenv('MYSQLPORT') ?: 3306);
$user = getenv('MYSQLUSER')      ?: 'root';
$pass = getenv('MYSQLPASSWORD')  ?: '';
$db   = getenv('MYSQL_DATABASE') ?: 'railway';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$conn->connect_error) {
    $soil1  = floatval($_POST['soil1_moisture'] ?? 0);
    $soil2  = intval($_POST['soil2_wet']        ?? 0);
    $temp   = floatval($_POST['temperature']    ?? 0);
    $hum    = floatval($_POST['humidity']       ?? 0);
    $water  = floatval($_POST['water_level_cm'] ?? 0);
    $pump   = intval($_POST['pump_status']      ?? 0);

    $stmt = $conn->prepare(
        "INSERT INTO sensor_readings 
         (soil1_moisture, soil2_wet, temperature, humidity, water_level_cm, pump_status)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("diiddi", $soil1, $soil2, $temp, $hum, $water, $pump);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    http_response_code(200);
    echo "OK";
} else {
    http_response_code(500);
    echo "Error: " . $conn->connect_error;
}
?>