<?php

// Rollback script for Windows server deployment
// This script reverts the application to a previous state

// Set your project path (adjust this to match your actual deployment path)
$projectPath = 'C:\\wamp64\\www\\deploy_fmrr';
$logFile = __DIR__ . '/rollback-log.txt';

// Helper to safely read input as JSON or fallback to GET/form
function readRequestInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && !empty($contentType)
        && stripos($contentType, 'application/json') !== false
    ) {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }
    }
    // Fallback to GET or form-encoded POST
    return $_GET ?: $_POST ?: [];
}

// Read request data
$input = readRequestInput();

$isRollback = isset($input['rollback']) && $input['rollback'] === true;
$targetCommit = $input['rollback_target_commit'] ?? null;
$rollbackReason = $input['rollback_reason'] ?? 'No reason provided';

// Check for authorization token
$expectedToken = 'test-token-123';
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// Basic normalize for some servers (e.g., might pass only token without "Bearer ")
if ($token && stripos($token, 'bearer ') !== 0) {
    $token = 'Bearer ' . trim($token);
}

if (!$token || $token !== 'Bearer ' . $expectedToken) {
    http_response_code(401);
    $errorOutput = "❌ Unauthorized: Invalid or missing token\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized: Invalid or missing token',
    ]);
    exit;
}

// If this isn't a rollback request, return an error
if (!$isRollback) {
    http_response_code(400);
    $errorOutput = "❌ Bad Request: This endpoint is for rollback operations only\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Bad Request: This endpoint is for rollback operations only',
    ]);
    exit;
}

// If no target commit is specified, return an error
if (!$targetCommit) {
    http_response_code(400);
    $errorOutput = "❌ Bad Request: No rollback target commit specified\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Bad Request: No rollback target commit specified',
    ]);
    exit;
}

$output = "🔄 Rollback started in {$projectPath}\n";
$output .= "Target Commit: {$targetCommit}\n";
$output .= "Reason: {$rollbackReason}\n\n";

// Commands for rollback (use proper quoting/escaping)
$commands = [
    // Mark the repo directory as safe for Git (Windows service accounts can hit 'dubious ownership')
    "git config --global --add safe.directory " . escapeshellarg($projectPath),

    // Optional: If you have run into vendor git package ownership issues during composer, add those here:
    // "git config --global --add safe.directory " . escapeshellarg($projectPath . '/vendor/laravel/breeze'),

    // Reset to target commit selected by user
    "cd /d " . escapeshellarg($projectPath) . " && git reset --hard " . escapeshellarg($targetCommit),

    // Capture the current commit after checkout
    "cd /d " . escapeshellarg($projectPath) . " && git rev-parse HEAD",

    // Reinstall production deps non-interactively (DISABLED FOR NOW)
    // "cd /d " . escapeshellarg($projectPath) . " && composer install --no-dev --no-interaction --optimize-autoloader",

    // Laravel maintenance
    "cd /d " . escapeshellarg($projectPath) . " && php artisan cache:clear",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan config:cache",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan route:cache",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan optimize:clear",

    // Rollback database migrations (DISABLED FOR NOW)
    // "cd /d " . escapeshellarg($projectPath) . " && php artisan migrate:rollback --force",
];

$commitHash = null;

foreach ($commands as $index => $cmd) {
    $output .= "> Running: {$cmd}\n";
    $result = shell_exec($cmd . ' 2>&1');
    $output .= $result . "\n";

    // Capture commit hash when running git rev-parse HEAD
    if (strpos($cmd, 'git rev-parse HEAD') !== false) {
        $commitHash = trim($result);
    }
    
    // Add debug info to see which commands are executing
    $output .= "Command " . ($index + 1) . " of " . count($commands) . " completed.\n\n";
}

// Append success marker and log it
$output .= "✅ Rollback completed successfully!\n";
file_put_contents($logFile, $output, FILE_APPEND);

// Send JSON response (clean, no HTML). Include raw text output for diagnostics.
http_response_code(200);
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'Rollback completed successfully',
    'project_id' => $input['project_id'] ?? null,
    'deployment_id' => $input['deployment_id'] ?? null,
    'is_rollback' => true,
    'rollback_target_commit' => $targetCommit,
    'commit_hash' => $commitHash,
    'raw_output' => $output,
];

echo json_encode($response);