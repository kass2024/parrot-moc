<?php
// tts.php — production version with license check

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

header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['text'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing text"]);
    exit;
}

$text = $input['text'];

$api_url = "https://api.openai.com/v1/audio/speech";

$post_fields = [
    "model" => "tts-1",
    "voice" => "nova",
    "input" => $text
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $OPENAI_API_KEY"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));

$mp3_data = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($mp3_data === false) {
    http_response_code(500);
    echo json_encode(["error" => "cURL Error: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($http_status == 200) {
    $filename = "tts_output_" . time() . "_" . rand(1000,9999) . ".mp3";
    $filepath = "../tts_audio/" . $filename;

    if (!file_exists("../tts_audio")) {
        mkdir("../tts_audio", 0777, true);
    }

    file_put_contents($filepath, $mp3_data);

    echo json_encode(["audio_url" => "tts_audio/" . $filename]);
} else {
    echo json_encode([
        "error" => "OpenAI TTS API error",
        "http_status" => $http_status
    ]);
}
?>
