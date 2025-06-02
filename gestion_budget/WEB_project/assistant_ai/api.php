<?php
header("Content-Type: application/json");

try {
    // 1. Load API key securely
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        throw new Exception("Configuration file not found");
    }
    
    $env = parse_ini_file($envPath);
    $apiKey = $env['API_KEY2'] ?? null;
    if (!$apiKey) {
        throw new Exception("API key not configured");
    }

    // 2. Get user message
    $input = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }
    
    $userMessage = trim($input['message'] ?? '');
    if (empty($userMessage)) {
        throw new Exception("Message cannot be empty");
    }

    // 3. Prepare API request
    $payload = [
        "model" => "deepseek/deepseek-r1:free",
        "messages" => [["role" => "user", "content" => $userMessage]]
    ];
    
    $options = [
        "http" => [
            "method" => "POST",
            "header" => implode("\r\n", [
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json",
                "Accept: application/json"
            ]),
            "content" => json_encode($payload),
            "ignore_errors" => true,
            "timeout" => 30
        ]
    ];
    
    $context = stream_context_create($options);
    
    // 4. Make API call
    $response = @file_get_contents("https://openrouter.ai/api/v1/chat/completions", false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        throw new Exception($error['message'] ?? "API request failed");
    }
    
    // 5. Return raw response
    echo $response;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => $e->getMessage(),
        "details" => "An error occurred while processing your request"
    ]);
}
?>