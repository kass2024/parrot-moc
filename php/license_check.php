<?php
require_once 'db.php';
header("Content-Type: application/json");

// Detect if JSON or FormData
$content_type = $_SERVER["CONTENT_TYPE"] ?? '';

if (stripos($content_type, 'application/json') !== false) {
    // JSON body
    $input_raw = file_get_contents("php://input");
    $input = json_decode($input_raw, true);
    $license_key = $input['license_key'] ?? $_GET['license'] ?? '';
    $device_id   = $input['device_id'] ?? $_GET['device_id'] ?? '';
} else {
    // FormData (multipart/form-data) — use $_REQUEST
    $license_key = $_REQUEST['license_key'] ?? '';
    $device_id   = $_REQUEST['device_id'] ?? '';
}

if (empty($license_key) || empty($device_id)) {
    http_response_code(403);
    echo json_encode(["error" => "Missing license key or device id"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, expiry_date, status, device_id FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->bind_param("s", $license_key);
$stmt->execute();
$stmt->bind_result($id, $expiry_date, $status, $stored_device_id);
$stmt->fetch();
$stmt->close();

if (!$id) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid license key"]);
    exit;
}

if ($status !== 'active') {
    http_response_code(403);
    echo json_encode(["error" => "License not active"]);
    exit;
}

if (strtotime('now') > strtotime($expiry_date)) {
    http_response_code(403);
    echo json_encode(["error" => "License expired"]);
    exit;
}

if (empty($stored_device_id)) {
    $stmt = $conn->prepare("UPDATE licenses SET device_id = ?, last_used_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $device_id, $id);
    $stmt->execute();
    $stmt->close();
} elseif ($stored_device_id !== $device_id) {
    http_response_code(403);
    echo json_encode(["error" => "License already used on another device"]);
    exit;
} else {
    $stmt = $conn->prepare("UPDATE licenses SET last_used_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
?>
