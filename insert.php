<?php
require_once 'Connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soil1  = floatval($_POST['soil1_moisture'] ?? 0);
    $soil2  = intval($_POST['soil2_wet']        ?? 0);
    $temp   = floatval($_POST['temperature']    ?? 0);
    $hum    = floatval($_POST['humidity']       ?? 0);
    $water  = floatval($_POST['water_level_cm'] ?? 0);
    $pump   = intval($_POST['pump_status']      ?? 0);

    if ($conn) {
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
        echo "DB error";
    }
} else {
    http_response_code(405);
    echo "POST only";
}
?>