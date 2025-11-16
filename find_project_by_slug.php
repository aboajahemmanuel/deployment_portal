<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;

$projectId = 10;
$project = Project::find($projectId);

if ($project) {
    // Generate slug the same way as in DeploymentController
    $slug = str_replace([' ', '/','\\'], ['-','-','-'], strtolower($project->name));
    $slug = trim($slug, '-_');
    
    echo "Project ID: {$project->id}\n";
    echo "Name: {$project->name}\n";
    echo "Generated slug: {$slug}\n";
    echo "Repository URL: {$project->repository_url}\n";
} else {
    echo "Project with ID {$projectId} not found.\n";
}