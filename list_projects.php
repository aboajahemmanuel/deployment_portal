<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;

$projects = Project::all();
echo "Projects in database:\n";
echo "===================\n";

foreach ($projects as $project) {
    echo "ID: {$project->id}, Name: {$project->name}\n";
    
    if (strpos($project->env_variables, 'MAIL_FROM_NAME') !== false) {
        echo "  Has MAIL_FROM_NAME\n";
        $lines = explode("\n", $project->env_variables);
        foreach ($lines as $i => $line) {
            if (strpos($line, 'MAIL_FROM_NAME') !== false) {
                echo "    Line {$i}: {$line}\n";
            }
        }
    }
    echo "\n";
}