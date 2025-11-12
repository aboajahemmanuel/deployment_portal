<?php

// Deployment script for Windows server deployment
// This script deploys the latest code from the repository

$projectPath = realpath(__DIR__);
$logFile = __DIR__ . '/deploy-log.txt';

// Get request data - handle both GET parameters and JSON body
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    // Handle JSON POST data
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    // Handle GET parameters or form data
    $input = $_GET;
}

$isRollback = isset($input['rollback']) && $input['rollback'] === true;

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

// If this is a rollback request but we're on the deploy endpoint, return an error
if ($isRollback) {
    http_response_code(400);
    $errorOutput = "❌ Bad Request: Rollback requests should use the rollback endpoint\n";
    file_put_contents($logFile, $errorOutput, FILE_APPEND);
    echo $errorOutput;
    exit;
}

$output = "🚀 Deployment started in {$projectPath}\n\n";

// Commands for deployment
$commands = [
    "git config --global --add safe.directory " . escapeshellarg($projectPath),
    "cd /d " . escapeshellarg($projectPath) . " && git pull origin main",
    "cd /d " . escapeshellarg($projectPath) . " && git rev-parse HEAD", // Get current commit hash
    "cd /d " . escapeshellarg($projectPath) . " && composer install --no-dev --optimize-autoloader",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan cache:clear",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan config:cache",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan route:cache",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan optimize:clear",
    "cd /d " . escapeshellarg($projectPath) . " && php artisan migrate --force", // Run database migrations
];

$commitHash = null;

foreach ($commands as $cmd) {
    $output .= "> Running: {$cmd}\n";
    $result = shell_exec($cmd . ' 2>&1');
    $output .= $result . "\n";
    
    // Capture commit hash from git rev-parse command
    if (strpos($cmd, 'git rev-parse HEAD') !== false) {
        $commitHash = trim($result);
    }
}

// Add success response
$output .= "✅ Deployment completed successfully!\n";
file_put_contents($logFile, $output, FILE_APPEND);

// Return success response
http_response_code(200);
echo nl2br($output);

// Return JSON response for the deployment manager
$response = [
    'status' => 'success',
    'message' => 'Deployment completed successfully',
    'project_id' => $input['project_id'] ?? null,
    'deployment_id' => $input['deployment_id'] ?? null,
    'is_rollback' => false,
    'commit_hash' => $commitHash
];

// Output the JSON response as well (this will be captured by the deployment manager)
echo "\n" . json_encode($response);