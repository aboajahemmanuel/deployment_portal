<?php

// Include the function directly
function processEnvVariables($envVariables) {
    $lines = explode("\n", $envVariables);
    $processedLines = array();
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            $processedLines[] = $line;
            continue;
        }
        
        // Check if this is a key=value pair
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Special handling for problematic values
            if ($key === 'MAIL_FROM_NAME' || $key === 'VITE_APP_NAME') {
                // Remove outer quotes if they exist and re-add them with proper escaping
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $innerValue = $matches[1];
                    // Escape any internal quotes
                    $escapedValue = str_replace('"', '\"', $innerValue);
                    $processedLines[] = $key . '="' . $escapedValue . '"';
                } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $innerValue = $matches[1];
                    // Escape any internal single quotes
                    $escapedValue = str_replace('\'', '\\\'', $innerValue);
                    $processedLines[] = $key . '=\'' . $escapedValue . '\'';
                } else {
                    // No quotes, add them
                    $processedLines[] = $key . '="' . $value . '"';
                }
            } else {
                // For other variables, just add as is
                $processedLines[] = $line;
            }
        } else {
            // Not a key=value pair, add as is
            $processedLines[] = $line;
        }
    }
    
    return implode("\n", $processedLines);
}

// Test with the problematic environment variables
$testEnv = <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:cTnP5lbZSeAIUsBWgpu/OhUaUglN+FfMeIy0IsUcBZs=
APP_DEBUG=true
APP_URL=http://localhost/deployment-management

MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@fmdqgroup.com
MAIL_PASSWORD=J%662460932740ap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@fmdqgroup.com
MAIL_FROM_NAME="Deployment Management System"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"
ENV;

echo "Original environment variables:\n";
echo "=============================\n";
echo $testEnv;
echo "\n\n";

echo "Processed environment variables:\n";
echo "==============================\n";
$processed = processEnvVariables($testEnv);
echo $processed;
echo "\n\n";

// Write to a test file and try to read it back
$testFile = 'test_final_env.env';
if (file_put_contents($testFile, $processed)) {
    echo "✅ Successfully wrote to {$testFile}\n";
    
    // Try to simulate how Laravel's dotenv parser might read it
    $content = file_get_contents($testFile);
    echo "Reading back content:\n";
    echo "===================\n";
    echo $content;
    echo "\n";
    
    // Clean up
    unlink($testFile);
    echo "🗑️  Cleaned up test file\n";
} else {
    echo "❌ Failed to write to {$testFile}\n";
}