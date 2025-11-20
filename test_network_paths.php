<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\Environment;
use App\Services\DeploymentFileGenerator;

echo "🔍 Testing network path access for project ID 10 (Chiquita Patton)...\n\n";

// Get the specific project that's failing
$project = Project::find(10);
if (!$project) {
    echo "❌ Project ID 10 not found.\n";
    exit(1);
}

echo "📊 Project: {$project->name} (ID: {$project->id})\n";
echo "🔗 Repository: {$project->repository_url}\n\n";

// Generate slug the same way as in DeploymentController
$slug = str_replace([' ', '/','\\'], ['-','-','-'], strtolower($project->name));
$slug = trim($slug, '-_');
echo "🏷️  Generated slug: {$slug}\n\n";

// Get all active environments
$environments = Environment::active()->ordered()->get();

if ($environments->isEmpty()) {
    echo "❌ No active environments found.\n";
    exit(1);
}

echo "🌐 Testing {$environments->count()} active environments\n\n";

$generator = new DeploymentFileGenerator();

foreach ($environments as $environment) {
    echo "🧪 Testing {$environment->name} environment:\n";
    
    // Generate environment-specific file names (same logic as in DeploymentController)
    $envFileName = $slug . '_' . $environment->slug . '.php';
    $envRollbackFileName = $slug . '_' . $environment->slug . '_rollback.php';
    
    echo "   File names: {$envFileName}, {$envRollbackFileName}\n";
    
    // Generate environment-specific project path
    $projectType = $project->project_type ?? 'laravel';
    // Remove _deploy suffix for all project types
    $windowsProjectPath = $environment->server_base_path . '\\' . $slug;
    
    echo "   Windows project path: {$windowsProjectPath}\n";
    
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

    echo "   Generated content: " . strlen($content) . " bytes\n";
    echo "   Generated rollback: " . strlen($rollbackContent) . " bytes\n";

    // Ensure UNC path formatting (same logic as in DeploymentController)
    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
    if (!str_starts_with($uncBase, '\\\\')) {
        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
    }
    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
    $targetPath = $targetBase . $envFileName;
    $rollbackTargetPath = $targetBase . $envRollbackFileName;

    echo "   UNC Base: {$uncBase}\n";
    echo "   Target paths:\n";
    echo "     - {$targetPath}\n";
    echo "     - {$rollbackTargetPath}\n";
    
    // Test directory access
    $directory = dirname($targetPath);
    echo "   Directory checks:\n";
    echo "     - Exists: " . (is_dir($directory) ? '✅ Yes' : '❌ No') . "\n";
    echo "     - Writable: " . (is_writable($directory) ? '✅ Yes' : '❌ No') . "\n";
    echo "     - Resolved path: " . (realpath($directory) ?: '❌ Could not resolve') . "\n";
    
    // Test network connectivity
    echo "   Network connectivity test:\n";
    $parsedUrl = parse_url($environment->server_unc_path);
    if (preg_match('/^\\\\\\\\([^\\\\]+)/', $environment->server_unc_path, $matches)) {
        $host = $matches[1];
        echo "     - Target host: {$host}\n";
        
        // Test if we can reach the host
        $pingResult = shell_exec("ping -n 1 -w 1000 {$host} 2>&1");
        if (strpos($pingResult, 'TTL=') !== false) {
            echo "     - Ping test: ✅ Host reachable\n";
        } else {
            echo "     - Ping test: ❌ Host unreachable\n";
        }
    } else {
        echo "     - Could not parse host from UNC path\n";
    }
    
    // Try to write files with detailed error reporting
    echo "   File write test:\n";
    
    // Test writing deployment file
    echo "     - Writing deployment file...\n";
    $deployResult = @file_put_contents($targetPath, $content);
    if ($deployResult === false) {
        $error = error_get_last();
        echo "       ❌ Failed: " . ($error['message'] ?? 'Unknown error') . "\n";
        echo "       Error type: " . ($error['type'] ?? 'Unknown') . "\n";
        echo "       Error file: " . ($error['file'] ?? 'Unknown') . "\n";
        echo "       Error line: " . ($error['line'] ?? 'Unknown') . "\n";
    } else {
        echo "       ✅ Success ({$deployResult} bytes)\n";
        
        // Verify file exists
        if (file_exists($targetPath)) {
            echo "       ✅ File confirmed to exist\n";
            
            // Clean up
            if (@unlink($targetPath)) {
                echo "       🗑️  Cleaned up test file\n";
            } else {
                echo "       ⚠️  Could not clean up test file\n";
            }
        } else {
            echo "       ❌ File does not exist after write\n";
        }
    }
    
    echo "\n";
}

echo "🏁 Network path testing complete!\n";