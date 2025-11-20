<?php

// Rollback script for Windows server deployment
// This script reverts the application to a previous state

$projectPath = realpath(__DIR__);
$logFile = __DIR__ . '/rollback-log.txt';

// Get request data - handle both GET parameters and JSON body
// Read input robustly
$input = [];
$rawBody = file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) { $input = $decoded; }
}
// Merge with POST/GET fallback
$input = array_merge($_GET ?? [], $_POST ?? [], $input);
// Log received input keys for diagnostics
@file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Received keys: " . implode(',', array_keys($input)) . "\n", FILE_APPEND);

$isRollback = false;
if (isset($input['rollback'])) {
    $val = $input['rollback'];
    $isRollback = ($val === true || $val === 1 || $val === '1' || $val === 'true' || $val === 'on');
}
$targetCommit = $input['rollback_target_commit'] ?? ($input['commit_hash'] ?? null);

$rollbackReason = $input['rollback_reason'] ?? 'No reason provided';

// Check for authorization token
$expectedToken = 'test-token-123';
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!$token || $token !== 'Bearer ' . $expectedToken) {
    http_response_code(401);
    $errorOutput = "❌ Unauthorized: Invalid or missing token\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    echo $errorOutput;
    exit;
}

// If this isn't a rollback request, return an error
if (!$isRollback) {
    http_response_code(400);
    $errorOutput = "❌ Bad Request: This endpoint is for rollback operations only\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    echo $errorOutput;
    exit;
}

// If no target commit is specified, return an error
if (!$targetCommit) {
    http_response_code(400);
    $errorOutput = "❌ Bad Request: No rollback target commit specified\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    echo $errorOutput;
    exit;
}

$output = "🔄 Rollback started in {$projectPath}\n";
$output .= "Target Commit: {$targetCommit}\n";
$output .= "Reason: {$rollbackReason}\n\n";

// Commands for rollback
$commands = [
    "git config --global --add safe.directory " . escapeshellarg($projectPath),
    "cd /d " . escapeshellarg($projectPath) . " && git checkout {$targetCommit}",
    "cd /d " . escapeshellarg($projectPath) . " && composer install --no-dev --optimize-autoloader",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan cache:clear",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan config:cache",
    // Skip route caching to avoid MethodNotAllowedHttpException issues
    // "cd /d " . escapeshellarg($projectPath) . " && php artisan route:cache",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan optimize:clear",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan migrate:rollback", // Rollback database migrations
];

foreach ($commands as $cmd) {
    $output .= "> Running: {$cmd}\n";
    $result = shell_exec($cmd . ' 2>&1');
    $output .= $result . "\n";
}

// Add success response
$output .= "✅ Rollback completed successfully!\n";
file_put_contents($logFile, $output, FILE_APPEND);

// Return success response
http_response_code(200);
echo nl2br($output);

// Get current commit hash after rollback
$commitHash = trim(shell_exec("cd /d " . escapeshellarg($projectPath) . " && git rev-parse HEAD 2>&1"));

// Return JSON response for the deployment manager
$response = [
    'status' => 'success',
    'message' => 'Rollback completed successfully',
    'project_id' => $input['project_id'] ?? null,
    'deployment_id' => $input['deployment_id'] ?? null,
    'is_rollback' => true,
    'rollback_target_commit' => $targetCommit,
    'commit_hash' => $commitHash
];

// Output the JSON response as well (this will be captured by the deployment manager)
echo "\n" . json_encode($response);