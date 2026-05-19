<?php
// license_info.php — returns license expiry + current UTC time

require_once 'db.php';
date_default_timezone_set('UTC');

header("Content-Type: application/json");

$input_raw = file_get_contents("php://input");
$input = json_decode($input_raw, true);

$license_key = $input['license_key'] ?? $_GET['license'] ?? '';
$device_id   = $input['device_id']   ?? $_GET['device_id'] ?? '';

if (empty($license_key) || empty($device_id)) {
    http_response_code(403);
    echo json_encode(["error" => "Missing license key or device id"]);
    exit;
}

$stmt = $conn->prepare("SELECT expiry_date FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->bind_param("s", $license_key);
$stmt->execute();
$stmt->bind_result($expiry_date);
$stmt->fetch();
$stmt->close();

if (empty($expiry_date)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid license key"]);
    exit;
}

echo json_encode([
    "expiry_date" => $expiry_date,
    "now_utc" => date("Y-m-d H:i:s") // <--- FIXED
]);
