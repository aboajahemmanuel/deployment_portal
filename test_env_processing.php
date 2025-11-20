<?php

require_once 'app/Services/DeploymentFileGenerator.php';

use App\Services\DeploymentFileGenerator;

// Test environment variables with problematic quotes
$testEnvVariables = <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:cTnP5lbZSeAIUsBWgpu/OhUaUglN+FfMeIy0IsUcBZs=
APP_DEBUG=true
APP_URL=http://localhost/deployment-management

MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@fmdqgroup.com
MAIL_PASSWORD=J%66246093640ap
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
echo "============================\n";
echo $testEnvVariables;
echo "\n\n";

echo "Processed environment variables:\n";
echo "===============================\n";
$processed = DeploymentFileGenerator::processEnvVariables($testEnvVariables);
echo $processed;
echo "\n\n";

// Test writing to a file
$testFilePath = 'test_env_file.env';
if (file_put_contents($testFilePath, $processed)) {
    echo "✅ Successfully wrote processed env variables to {$testFilePath}\n";
    
    // Try to read it back
    $content = file_get_contents($testFilePath);
    if ($content !== false) {
        echo "✅ Successfully read back from {$testFilePath}\n";
        echo "Content:\n=======\n";
        echo $content;
        echo "\n";
    } else {
        echo "❌ Failed to read back from {$testFilePath}\n";
    }
    
    // Clean up
    unlink($testFilePath);
    echo "🗑️  Cleaned up test file\n";
} else {
    echo "❌ Failed to write to {$testFilePath}\n";
}