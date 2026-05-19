<?php
// chat_gpt.php — production version with first message + license check

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

// === GET INPUT ===
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['conversation'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing conversation"]);
    exit;
}

$conversation = $input['conversation'];

// === BUILD MESSAGES ARRAY ===
$messages = [];

if (count($conversation) === 0) {
    // FIRST MESSAGE — tell GPT to start interview
    $messages[] = [
        "role" => "system",
        "content" => "You are a US Visa Officer running a mock visa interview with a student. Start the interview with: 'Welcome to your visa interview practice session. To begin, please briefly introduce yourself and state the university and specific program you plan to attend in the US.' Then wait for student reply."
    ];
} else {
    // Conversation already started — remind GPT to continue interview
    $messages[] = [
        "role" => "system",
        "content" => "You are a US Visa Officer running a mock visa interview. Continue the interview naturally, asking 6-10 questions about the student's study plans, university choice, financial situation, ties to home country, post-graduation plans, and English proficiency."
    ];
}

// Add conversation history
foreach ($conversation as $entry) {
    $messages[] = [
        "role" => $entry['role'],
        "content" => $entry['content']
    ];
}

// === CALL OPENAI GPT ===
$api_url = "https://api.openai.com/v1/chat/completions";

$post_fields = [
    "model" => "gpt-4o",  
     //"model" => "gpt-3.5-turbo",
    "messages" => $messages,
    "max_tokens" => 300,
    "temperature" => 0.7
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $OPENAI_API_KEY"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));

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

// === PARSE RESPONSE ===
if ($http_status == 200 && isset($result['choices'][0]['message']['content'])) {
    $reply = $result['choices'][0]['message']['content'];
    echo json_encode(["reply" => $reply]);
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "OpenAI API error",
        "http_status" => $http_status,
        "response" => $result
    ]);
}
?>
