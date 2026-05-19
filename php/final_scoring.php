<?php

// final_scoring.php — production version
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

// === BUILD FINAL GPT PROMPT ===

$final_prompt = "You are a US Visa Officer conducting a mock interview. 

Here is the full conversation with the student:

";

foreach ($conversation as $entry) {
    $role = ucfirst($entry['role']);
    $text = $entry['content'];
    $final_prompt .= "$role: $text\n";
}

$final_prompt .= "

Now, please analyze the ENTIRE interview. 

For each category, give a score from 0 to 10, and a 1-sentence feedback.

Categories:

1. Study Plan Clarity
2. University Choice Rationale
3. Motivation for Studying in US
4. Financial Stability
5. Ties to Home Country
6. Post-Graduation Plans
7. English Proficiency

Return the result in this JSON format:

{
  \"Study Plan Clarity\": { \"score\": X, \"feedback\": \"...\" },
  \"University Choice Rationale\": { \"score\": X, \"feedback\": \"...\" },
  \"Motivation for Studying in US\": { \"score\": X, \"feedback\": \"...\" },
  \"Financial Stability\": { \"score\": X, \"feedback\": \"...\" },
  \"Ties to Home Country\": { \"score\": X, \"feedback\": \"...\" },
  \"Post-Graduation Plans\": { \"score\": X, \"feedback\": \"...\" },
  \"English Proficiency\": { \"score\": X, \"feedback\": \"...\" }
}

ONLY return valid JSON, do not add any extra text.
";

// === CALL OPENAI GPT ===
$api_url = "https://api.openai.com/v1/chat/completions";

$post_fields = [
    "model" => "gpt-4o",
    "messages" => [
        ["role" => "system", "content" => "You are a helpful US Visa Officer scoring the interview."],
        ["role" => "user", "content" => $final_prompt]
    ],
    "max_tokens" => 800,
    "temperature" => 0.5
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
    $reply_content = $result['choices'][0]['message']['content'];

    // Parse JSON from GPT response:
    $json_start = strpos($reply_content, '{');
    $json_end = strrpos($reply_content, '}');

    if ($json_start !== false && $json_end !== false) {
        $json_string = substr($reply_content, $json_start, $json_end - $json_start + 1);
        $scores = json_decode($json_string, true);

        if ($scores !== null) {
            echo json_encode(["scores" => $scores]);
            exit;
        } else {
            // GPT returned invalid JSON
            echo json_encode([
                "error" => "Invalid JSON from GPT",
                "raw_reply" => $reply_content
            ]);
            exit;
        }
    } else {
        // GPT returned no JSON
        echo json_encode([
            "error" => "No JSON found in GPT reply",
            "raw_reply" => $reply_content
        ]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "OpenAI API error",
        "http_status" => $http_status,
        "response" => $result
    ]);
}
