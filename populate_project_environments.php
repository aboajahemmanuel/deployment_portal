<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\Environment;
use App\Models\ProjectEnvironment;
use App\Services\DeploymentFileGenerator;

echo "🔧 Populating project_environments for existing projects...\n\n";

// Get all projects and active environments
$projects = Project::all();
$environments = Environment::active()->ordered()->get();

if ($environments->isEmpty()) {
    echo "❌ No active environments found. Please seed environments first.\n";
    exit(1);
}

echo "📊 Found {$projects->count()} projects and {$environments->count()} active environments\n\n";

$generator = new DeploymentFileGenerator();
$created = 0;

foreach ($projects as $project) {
    echo "🔨 Processing project: {$project->name}\n";
    
    // Generate slug from project name
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name));
    $slug = trim($slug, '-_');
    if ($slug === '') {
        $slug = 'project-' . $project->id;
    }
    
    foreach ($environments as $environment) {
        // Check if project environment already exists
        $existing = ProjectEnvironment::where('project_id', $project->id)
            ->where('environment_id', $environment->id)
            ->first();
            
        if ($existing) {
            echo "  ⏭️  {$environment->name}: Already exists\n";
            continue;
        }
        
        try {
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

            // Ensure UNC path formatting
            $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
            if (!str_starts_with($uncBase, '\\\\')) {
                $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
            }
            $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
            $targetPath = $targetBase . $envFileName;
            $rollbackTargetPath = $targetBase . $envRollbackFileName;

            // Write deployment files
            @file_put_contents($targetPath, $content);
            @file_put_contents($rollbackTargetPath, $rollbackContent);
            
            // Create project environment record
            ProjectEnvironment::create([
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'deploy_endpoint' => $deployEndpoint,
                'rollback_endpoint' => $rollbackEndpoint,
                'application_url' => $applicationUrl,
                'project_path' => $windowsProjectPath,
                'env_variables' => $project->env_variables,
                'branch' => $project->current_branch,
                'is_active' => true,
            ]);

            echo "  ✅ {$environment->name}: Created deployment files and database record\n";
            $created++;
            
        } catch (\Throwable $e) {
            echo "  ❌ {$environment->name}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

echo "🎉 Migration complete! Created {$created} project-environment configurations.\n";
echo "📊 Total project environments now: " . ProjectEnvironment::count() . "\n";
