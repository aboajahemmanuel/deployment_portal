<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\Environment;

// Check for project ID 14
$project = Project::find(7);
if (!$project) {
    echo "❌ Project ID 14 not found\n";
    exit(1);
}

echo "🔍 Checking deployment files for project: {$project->name} (ID: {$project->id})\n\n";

$environments = Environment::active()->ordered()->get();

foreach ($environments as $environment) {
    echo "🧪 Checking {$environment->name} environment:\n";
    
    // Generate slug from project name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name));
    $slug = trim($slug, '-_');
    if ($slug === '') {
        $slug = 'project-' . $project->id;
    }
    
    // Generate environment-specific file names
    $envFileName = $slug . '_' . $environment->slug . '.php';
    $envRollbackFileName = $slug . '_' . $environment->slug . '_rollback.php';
    
    // Ensure UNC path formatting
    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
    if (!str_starts_with($uncBase, '\\\\')) {
        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
    }
    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
    $targetPath = $targetBase . $envFileName;
    $rollbackTargetPath = $targetBase . $envRollbackFileName;
    
    echo "   Deploy File Path: {$targetPath}\n";
    echo "   Rollback File Path: {$rollbackTargetPath}\n";
    
    // Check if files exist
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
    
    echo "\n";
}