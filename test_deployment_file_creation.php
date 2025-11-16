<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Environment;
use App\Models\Project;
use App\Services\DeploymentFileGenerator;

echo "🔍 Testing deployment file creation...\n\n";

// Get a test project
$project = Project::first();
if (!$project) {
    echo "❌ No projects found. Create a project first.\n";
    exit(1);
}

echo "📊 Using project: {$project->name} (ID: {$project->id})\n";

// Get all active environments
$environments = Environment::active()->ordered()->get();

if ($environments->isEmpty()) {
    echo "❌ No active environments found.\n";
    exit(1);
}

echo "📊 Found {$environments->count()} active environments\n\n";

$generator = new DeploymentFileGenerator();

foreach ($environments as $environment) {
    echo "🧪 Testing {$environment->name} environment:\n";
    
    // Generate slug from project name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name));
    $slug = trim($slug, '-_');
    if ($slug === '') {
        $slug = 'project-' . $project->id;
    }
    
    // Generate environment-specific file names
    $envFileName = $slug . '_' . $environment->slug . '.php';
    $envRollbackFileName = $slug . '_' . $environment->slug . '_rollback.php';
    
    // Generate environment-specific project path using server_base_path
    $projectType = $project->project_type ?? 'laravel';
    if ($projectType === 'laravel') {
        // Laravel projects use separate _deploy directory
        $windowsProjectPath = $environment->server_base_path . '\\' . $slug . '_deploy';
    } else {
        // Non-Laravel projects deploy directly to server base path
        $windowsProjectPath = $environment->server_base_path . '\\' . $slug;
    }
    
    // Generate environment-specific URLs
    $deployEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envFileName;
    $rollbackEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envRollbackFileName;
    $applicationUrl = rtrim($environment->web_base_url, '/') . '/' . $slug;

    echo "   Project Path: {$windowsProjectPath}\n";
    echo "   Deploy Endpoint: {$deployEndpoint}\n";
    echo "   Rollback Endpoint: {$rollbackEndpoint}\n";
    echo "   Application URL: {$applicationUrl}\n";

    // Generate deployment file content
    $content = $generator->make(
        $windowsProjectPath, 
        $project->repository_url,
        $project->project_type ?? 'laravel',
        $project->env_variables,
        $environment->server_base_path
    );
    
    // Generate rollback script content
    $rollbackContent = $generator->makeRollback($windowsProjectPath);

    echo "   Generated deployment content: " . strlen($content) . " bytes\n";
    echo "   Generated rollback content: " . strlen($rollbackContent) . " bytes\n";

    // Ensure UNC path formatting
    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
    if (!str_starts_with($uncBase, '\\\\')) {
        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
    }
    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
    $targetPath = $targetBase . $envFileName;
    $rollbackTargetPath = $targetBase . $envRollbackFileName;

    echo "   Target Base: {$targetBase}\n";
    echo "   Deploy File Path: {$targetPath}\n";
    echo "   Rollback File Path: {$rollbackTargetPath}\n";

    // Write deployment files with error checking
    echo "   Writing deployment file...\n";
    $deployResult = @file_put_contents($targetPath, $content);
    if ($deployResult === false) {
        $error = error_get_last();
        echo "   ❌ Failed to write deployment file: " . ($error['message'] ?? 'Unknown error') . "\n";
    } else {
        echo "   ✅ Successfully wrote deployment file ({$deployResult} bytes)\n";
    }
    
    echo "   Writing rollback file...\n";
    $rollbackResult = @file_put_contents($rollbackTargetPath, $rollbackContent);
    if ($rollbackResult === false) {
        $error = error_get_last();
        echo "   ❌ Failed to write rollback file: " . ($error['message'] ?? 'Unknown error') . "\n";
    } else {
        echo "   ✅ Successfully wrote rollback file ({$rollbackResult} bytes)\n";
    }
    
    // Verify files exist
    if (file_exists($targetPath)) {
        echo "   ✅ Deployment file exists on server\n";
    } else {
        echo "   ❌ Deployment file does not exist on server\n";
    }
    
    if (file_exists($rollbackTargetPath)) {
        echo "   ✅ Rollback file exists on server\n";
    } else {
        echo "   ❌ Rollback file does not exist on server\n";
    }
    
    // Clean up test files
    if (file_exists($targetPath) && @unlink($targetPath)) {
        echo "   🗑️  Cleaned up deployment file\n";
    }
    
    if (file_exists($rollbackTargetPath) && @unlink($rollbackTargetPath)) {
        echo "   🗑️  Cleaned up rollback file\n";
    }
    
    echo "\n";
}

echo "🏁 Test complete!\n";