<?php

require_once 'vendor/autoload.php';
require_once 'app/Services/DeploymentFileGenerator.php';

use App\Services\DeploymentFileGenerator;

// Create a test deployment script
$generator = new DeploymentFileGenerator();

// Test with a sample project path and repository
$projectPath = 'C:\\xampp\\htdocs\\test_laravel_project';
$repoUrl = 'https://github.com/laravel/laravel.git';

echo "Generating deployment script...\n";

$deploymentScript = $generator->make($projectPath, $repoUrl);

// Save the generated script to examine
file_put_contents('sample_deployment.php', $deploymentScript);

echo "Deployment script generated successfully!\n";
echo "Check the sample_deployment.php file to verify database creation and migration commands are included.\n";

// Show key parts of the generated script
$content = file_get_contents('sample_deployment.php');

// Check for database creation
if (strpos($content, 'CREATE DATABASE') !== false) {
    echo "✓ Database creation command found in deployment script\n";
} else {
    echo "✗ Database creation command NOT found\n";
}

// Check for migration command
if (strpos($content, 'php artisan migrate') !== false) {
    echo "✓ Migration command found in deployment script\n";
} else {
    echo "✗ Migration command NOT found\n";
}

echo "\nYou can now run the sample_deployment.php script on your target server to deploy with database creation and migrations.\n";