<?php
// === CORS FIX ===
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';
require_once 'license_check.php';
require_once dirname(__DIR__) . '/load_env.php';
load_env();

$OPENAI_API_KEY = env('OPENAI_API_KEY');
if (!$OPENAI_API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI API key not configured']);
    exit;
}

// === READ LICENSE AND DEVICE_ID ===
$license_key = $_POST['license_key'] ?? $_GET['license'] ?? '';
$device_id   = $_POST['device_id'] ?? $_GET['device_id'] ?? '';

if (empty($license_key) || empty($device_id)) {
    http_response_code(403);
    echo json_encode(["error" => "Missing license key or device id"]);
    exit;
}

// === LICENSE CHECK ===
require 'license_check.php'; // this will exit if invalid license

// === RECEIVE FILE ===
if (!isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing audio file"]);
    exit;
}

$audioFile = $_FILES['audio']['tmp_name'];

// === CALL OPENAI Whisper API ===
$api_url = "https://api.openai.com/v1/audio/transcriptions";

$curl_file = new CURLFile($audioFile, 'audio/mpeg', 'audio.mp3');

$post_fields = [
    "file" => $curl_file,
    "model" => "whisper-1",
    "response_format" => "json",
    "language" => "en"
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $OPENAI_API_KEY"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "cURL Error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

if ($http_status == 200 && isset($result['text'])) {
    echo json_encode(["transcript" => $result['text']]);
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "OpenAI Whisper API error",
        "http_status" => $http_status,
        "response" => $result
    ]);
}
?>
