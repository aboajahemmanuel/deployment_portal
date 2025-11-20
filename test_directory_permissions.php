<?php

require_once 'vendor/autoload.php';
require_once 'app/Services/DeploymentFileGenerator.php';

use App\Services\DeploymentFileGenerator;

// Create a test deployment script to verify directory permissions
$generator = new DeploymentFileGenerator();

// Test with a sample project path
$projectPath = 'C:\\xampp\\htdocs\\test_project';
$repoUrl = 'https://github.com/laravel/laravel.git';

echo "Generating deployment script with improved directory permissions...\n";

$deploymentScript = $generator->make($projectPath, $repoUrl);

// Save the generated script
file_put_contents('deployment_with_permissions.php', $deploymentScript);

echo "Deployment script generated successfully!\n";

// Check for key elements in the generated script
$content = file_get_contents('deployment_with_permissions.php');

// Check for directory creation
if (strpos($content, 'Ensuring Laravel directories exist') !== false) {
    echo "✓ Directory creation logic found in deployment script\n";
} else {
    echo "✗ Directory creation logic NOT found\n";
}

// Check for bootstrap/cache directory creation
if (strpos($content, 'bootstrap/cache') !== false) {
    echo "✓ Bootstrap cache directory handling found\n";
} else {
    echo "✗ Bootstrap cache directory handling NOT found\n";
}

// Check for storage directory creation
if (strpos($content, 'storage') !== false && strpos($content, 'storage/logs') !== false) {
    echo "✓ Storage directory handling found\n";
} else {
    echo "✗ Storage directory handling NOT found\n";
}

echo "\nGenerated script saved as deployment_with_permissions.php\n";
echo "You can now use this script to deploy with proper directory permissions.\n";