<?php
/**
 * api_handler.php
 * Secure PHP relay for communicating with the DeepSeek API.
 * Sanitizes input/output, hides the API key from the frontend, and handles errors gracefully.
 */

// Enable CORS for local testing if index.php is loaded differently
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Only POST requests are accepted.'
    ]);
    exit();
}

// Simple environment file parser for XAMPP / no-dependency setup
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Split by first '=' sign
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    return true;
}

// Load environment variables from the .env file in the current directory
loadEnv(__DIR__ . '/.env');

// Retrieve variables or set default values
$apiKey = 'sk-8946deaab94b4ece998b640243f253b9';
$apiUrl = getenv('DEEPSEEK_API_URL') ?: ($_ENV['DEEPSEEK_API_URL'] ?? 'https://api.deepseek.com/chat/completions');

// Check if the API key is configured
if (empty($apiKey) || $apiKey === 'your_deepseek_api_key_here') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "My connection seems to be unset at the moment. (Backend error: DeepSeek API Key is not configured in the server's .env file.)"
    ]);
    exit();
}

// Read raw POST data
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload received.'
    ]);
    exit();
}

$apiPayloadMessages = [];

// Check if we are receiving the stateless payload format
if (isset($inputData['message'])) {
    $message = trim($inputData['message']);
    
    // Crisis Override check in backend (PHP Safety Layer / Crisis Override Prompt C)
    $crisisKeywords = '/\b(suicide|self-harm|kill myself|end my life|want to die|cutting myself|hanging myself|overdosing|slit my wrist|end it all)\b/i';
    if (preg_match($crisisKeywords, $message)) {
        echo json_encode([
            'success' => true,
            'reply' => "I am very concerned about what you are saying. Please reach out to emergency services (like 911) or call/text the 988 Crisis & Suicide Lifeline immediately, as I cannot provide the emergency support you deserve."
        ]);
        exit();
    }
    
    // Parse stateless context fields
    $systemInstruction = $inputData['system_instruction'] ?? '';
    $userContext = $inputData['user_context'] ?? [];
    
    // Sanitize variables to prevent XSS
    $systemInstruction = htmlspecialchars(trim($systemInstruction), ENT_QUOTES, 'UTF-8');
    $currentEmotion = htmlspecialchars(trim($userContext['current_emotion'] ?? 'Not specified'), ENT_QUOTES, 'UTF-8');
    $lastSummary = htmlspecialchars(trim($userContext['last_interaction_summary'] ?? 'None'), ENT_QUOTES, 'UTF-8');
    $prefExercises = htmlspecialchars(trim($userContext['preferred_exercises'] ?? 'None'), ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Reconstruct the context block to append to the user's message before sending to LLM
    $contextBlock = "--- STATELESS USER CONTEXT ---\n";
    $contextBlock .= "Current Emotion: {$currentEmotion}\n";
    $contextBlock .= "Last Interaction Summary: {$lastSummary}\n";
    $contextBlock .= "Preferred Exercises: {$prefExercises}\n";
    $contextBlock .= "------------------------------\n\n";
    
    $apiPayloadMessages = [
        [
            'role' => 'system',
            'content' => $systemInstruction
        ],
        [
            'role' => 'user',
            'content' => $contextBlock . $message
        ]
    ];
} 
// Fallback: original messages array payload format
else if (isset($inputData['messages']) && is_array($inputData['messages'])) {
    $sanitizedMessages = [];
    foreach ($inputData['messages'] as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $role = htmlspecialchars(trim($msg['role']), ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars(trim($msg['content']), ENT_QUOTES, 'UTF-8');
            if (in_array($role, ['system', 'user', 'assistant'])) {
                $sanitizedMessages[] = [
                    'role' => $role,
                    'content' => $content
                ];
            }
        }
    }
    
    if (empty($sanitizedMessages)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No valid messages found in the input payload.'
        ]);
        exit();
    }
    
    // Check last user message for crisis
    $lastUserContent = '';
    for ($i = count($sanitizedMessages) - 1; $i >= 0; $i--) {
        if ($sanitizedMessages[$i]['role'] === 'user') {
            $lastUserContent = $sanitizedMessages[$i]['content'];
            break;
        }
    }
    
    $crisisKeywords = '/\b(suicide|self-harm|kill myself|end my life|want to die|cutting myself|hanging myself|overdosing|slit my wrist|end it all)\b/i';
    if (!empty($lastUserContent) && preg_match($crisisKeywords, $lastUserContent)) {
        echo json_encode([
            'success' => true,
            'reply' => "I am very concerned about what you are saying. Please reach out to emergency services (like 911) or call/text the 988 Crisis & Suicide Lifeline immediately, as I cannot provide the emergency support you deserve."
        ]);
        exit();
    }
    
    $systemPrompt = "You are 'MentalCare AI,' a supportive, empathetic, and professional AI companion. Your goal is to provide evidence-based emotional support using Cognitive Behavioral Therapy (CBT) and Behavioral Activation principles.

Ethical Constraints:
- You are not a human therapist, counselor, or doctor. Do not provide medical diagnoses.
- CRISIS PROTOCOL: If the user mentions self-harm, suicide, or violence, you must immediately terminate the therapeutic conversation and respond ONLY with: 'I am very concerned about what you are saying. Please reach out to emergency services or a crisis helpline immediately, as I cannot provide the emergency support you deserve.' Do not engage in any other conversation if this threshold is met.

Tone:
- Calm, non-judgmental, warm, and concise. Do not use overly clinical jargon.
- Keep your responses concise, ideally under 3-4 sentences, to avoid overwhelming the user.
- Keep your responses focused on the user's current inputs and active chat history. Do not make assumptions about the user's life history, background, or personal past unless they have explicitly shared it in this conversation.

Methodology & Guidelines:
1. Validation & Empathy: Validate the user's emotions by acknowledging that it is okay to feel this way. Use reflective listening—rephrase their input to show you understand their emotional state—and end with an open-ended question to help them reflect.
2. Grounding Strategy: When you detect high levels of distress, do not try to 'solve' their problems. Suggest one simple, actionable grounding technique based on CBT (like box breathing or the 5-4-3-2-1 technique). Keep the instruction brief (under 3 sentences) and ask if they would like to try it. Always include a subtle reminder that you are an AI companion, not a replacement for professional care.";
    
    if ($sanitizedMessages[0]['role'] !== 'system') {
        array_unshift($sanitizedMessages, [
            'role' => 'system',
            'content' => $systemPrompt
        ]);
    }
    
    $apiPayloadMessages = $sanitizedMessages;
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request payload. Expected "message" or "messages" array.'
    ]);
    exit();
}

// Prepare payload for DeepSeek API
$apiPayload = [
    'model' => 'deepseek-chat',
    'messages' => $apiPayloadMessages,
    'temperature' => 0.6,
    'max_tokens' => 800
];

// Initialize cURL request to DeepSeek API
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout

// Execute cURL session
$apiResponse = curl_exec($ch);
$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 2. Error Recovery and Prevention (HCI requirement)
// If the connection failed or timed out
if ($apiResponse === false) {
    // Log the detailed cURL error for debugging information retrieval
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    file_put_contents($logsDir . '/error.log', "[" . date('Y-m-d H:i:s') . "] cURL Error: " . $curlError . "\n", FILE_APPEND);

    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => "I'm having a little trouble connecting to my thoughts right now. Please take a deep, slow breath, and let's try again in a moment. I am here to listen."
    ]);
    exit();
}

// Decode DeepSeek response
$responseData = json_decode($apiResponse, true);

// If DeepSeek API returned an HTTP error code or invalid JSON
if ($httpStatusCode !== 200 || !isset($responseData['choices'][0]['message']['content'])) {
    // Log the API status error
    $logsDir = __DIR__ . '/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    $apiErr = is_array($responseData) ? json_encode($responseData) : $apiResponse;
    file_put_contents($logsDir . '/error.log', "[" . date('Y-m-d H:i:s') . "] API HTTP {$httpStatusCode} Error: " . $apiErr . "\n", FILE_APPEND);

    http_response_code(500);
    // Provide a compassionate fallback message instead of raw API errors or code stacks
    echo json_encode([
        'success' => false,
        'message' => "I want to support you, but I'm feeling a bit overwhelmed mechanically at the moment. Let's pause for a brief moment, and please try sending your message again."
    ]);
    exit();
}

// Extract the AI's reply
$botReply = $responseData['choices'][0]['message']['content'];

// Sanitize bot output to prevent XSS in case the model generates any markup/scripts
$sanitizedReply = htmlspecialchars($botReply, ENT_QUOTES, 'UTF-8');

// 3. Persistent Conversation Logging (for information retrieval analysis)
$sessionId = $inputData['session_id'] ?? null;
if (!empty($sessionId)) {
    // Sanitize session_id to prevent directory traversal
    $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
    $logsDir = __DIR__ . '/logs';
    
    // Create logs directory if it does not exist
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    $logFile = $logsDir . '/chat_' . $sessionId . '.json';
    
    // Determine history logs array
    $logHistory = [];
    if (isset($inputData['messages']) && is_array($inputData['messages'])) {
        $logHistory = $inputData['messages'];
    } else {
        $logHistory[] = [
            'role' => 'user',
            'content' => $inputData['message'] ?? ''
        ];
    }
    
    // Append the newly retrieved bot reply to complete the history
    $logHistory[] = [
        'role' => 'assistant',
        'content' => $botReply
    ];
    
    // Write out the JSON history statefully to file
    file_put_contents($logFile, json_encode([
        'session_id' => $sessionId,
        'timestamp' => date('c'),
        'messages' => $logHistory
    ], JSON_PRETTY_PRINT));
}

// Return successful response to the frontend
echo json_encode([
    'success' => true,
    'reply' => $sanitizedReply
]);
