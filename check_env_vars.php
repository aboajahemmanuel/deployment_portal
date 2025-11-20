<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;

$project = Project::find(2);
if ($project) {
    echo "Project: {$project->name}\n";
    echo "Environment Variables:\n";
    echo "====================\n";
    
    $lines = explode("\n", $project->env_variables);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'MAIL_FROM_NAME') !== false) {
            echo "Line {$i}: {$line}\n";
            echo "Length: " . strlen($line) . "\n";
            echo "Raw bytes: ";
            for ($j = 0; $j < strlen($line); $j++) {
                echo ord($line[$j]) . " ";
            }
            echo "\n";
        }
    }
    
    // Show the actual content around MAIL_FROM_NAME
    $startIndex = max(0, strpos($project->env_variables, 'MAIL_FROM_NAME') - 20);
    $length = min(100, strlen($project->env_variables) - $startIndex);
    $context = substr($project->env_variables, $startIndex, $length);
    echo "\nContext around MAIL_FROM_NAME:\n";
    echo "----------------------------\n";
    echo $context . "\n";
} else {
    echo "Project not found\n";
}