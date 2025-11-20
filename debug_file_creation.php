<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Environment;
use App\Models\Project;
use App\Services\DeploymentFileGenerator;

echo "🔍 Debugging file creation process...\n\n";

// Get project ID 14
$project = Project::find(1);
if (!$project) {
    echo "❌ Project ID 14 not found.\n";
    exit(1);
}

echo "📊 Using project: {$project->name} (ID: {$project->id})\n";

$environments = Environment::active()->ordered()->get();

$generator = new DeploymentFileGenerator();

foreach ($environments as $environment) {
    echo "\n🧪 Testing {$environment->name} environment:\n";
    
    // Generate slug from project name (same logic as in DeploymentController)
    $slug = str_replace([' ', '/','\\'], ['-','-','-'], strtolower($project->name));
    echo "   Generated slug: {$slug}\n";
    
    // Generate environment-specific file names
    $envFileName = $slug . '_' . $environment->slug . '.php';
    $envRollbackFileName = $slug . '_' . $environment->slug . '_rollback.php';
    echo "   Deploy file name: {$envFileName}\n";
    echo "   Rollback file name: {$envRollbackFileName}\n";
    
    // Generate environment-specific project path
    $projectType = $project->project_type ?? 'laravel';
    // Remove _deploy suffix for all project types
    $windowsProjectPath = $environment->server_base_path . '\\' . $slug;
    echo "   Windows project path: {$windowsProjectPath}\n";
    
    // Generate URLs
    $deployEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envFileName;
    $rollbackEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envRollbackFileName;
    $applicationUrl = rtrim($environment->web_base_url, '/') . '/' . $slug;
    echo "   Deploy endpoint: {$deployEndpoint}\n";
    echo "   Rollback endpoint: {$rollbackEndpoint}\n";
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

    // Ensure UNC path formatting (same logic as in DeploymentController)
    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
    if (!str_starts_with($uncBase, '\\\\')) {
        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
    }
    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
    $targetPath = $targetBase . $envFileName;
    $rollbackTargetPath = $targetBase . $envRollbackFileName;

    echo "   UNC Base: {$uncBase}\n";
    echo "   Target Base: {$targetBase}\n";
    echo "   Deploy File Path: {$targetPath}\n";
    echo "   Rollback File Path: {$rollbackTargetPath}\n";

    // Write deployment files with detailed error checking
    echo "   Writing deployment file...\n";
    $deployResult = file_put_contents($targetPath, $content);
    if ($deployResult === false) {
        $error = error_get_last();
        echo "   ❌ Failed to write deployment file: " . ($error['message'] ?? 'Unknown error') . "\n";
        echo "   Error type: " . ($error['type'] ?? 'Unknown') . "\n";
    } else {
        echo "   ✅ Successfully wrote deployment file ({$deployResult} bytes)\n";
    }
    
    echo "   Writing rollback file...\n";
    $rollbackResult = file_put_contents($rollbackTargetPath, $rollbackContent);
    if ($rollbackResult === false) {
        $error = error_get_last();
        echo "   ❌ Failed to write rollback file: " . ($error['message'] ?? 'Unknown error') . "\n";
        echo "   Error type: " . ($error['type'] ?? 'Unknown') . "\n";
    } else {
        echo "   ✅ Successfully wrote rollback file ({$rollbackResult} bytes)\n";
    }
    
    // Immediate verification
    echo "   Immediate verification:\n";
    if (file_exists($targetPath)) {
        echo "   ✅ Deployment file exists (" . filesize($targetPath) . " bytes)\n";
    } else {
        echo "   ❌ Deployment file does not exist\n";
    }
    
    if (file_exists($rollbackTargetPath)) {
        echo "   ✅ Rollback file exists (" . filesize($rollbackTargetPath) . " bytes)\n";
    } else {
        echo "   ❌ Rollback file does not exist\n";
    }
    
    // Wait a moment and check again
    sleep(1);
    echo "   Verification after 1 second:\n";
    if (file_exists($targetPath)) {
        echo "   ✅ Deployment file still exists (" . filesize($targetPath) . " bytes)\n";
    } else {
        echo "   ❌ Deployment file no longer exists\n";
    }
    
    if (file_exists($rollbackTargetPath)) {
        echo "   ✅ Rollback file still exists (" . filesize($rollbackTargetPath) . " bytes)\n";
    } else {
        echo "   ❌ Rollback file no longer exists\n";
    }
}

echo "\n🏁 Debug complete!\n";