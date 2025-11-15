<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Fixing scheduled deployments without environment_id...\n";

try {
    // Get the first available environment as default
    $defaultEnvironment = \App\Models\Environment::active()->ordered()->first();
    
    if (!$defaultEnvironment) {
        echo "ERROR: No active environments found. Please create environments first.\n";
        exit(1);
    }
    
    echo "Using default environment: {$defaultEnvironment->name} (ID: {$defaultEnvironment->id})\n";
    
    // Update scheduled deployments with NULL environment_id
    $updatedCount = \App\Models\ScheduledDeployment::whereNull('environment_id')
        ->update(['environment_id' => $defaultEnvironment->id]);
    
    echo "Updated {$updatedCount} scheduled deployments with default environment.\n";
    
    // Show summary
    $totalScheduled = \App\Models\ScheduledDeployment::count();
    $withEnvironment = \App\Models\ScheduledDeployment::whereNotNull('environment_id')->count();
    
    echo "\nSummary:\n";
    echo "- Total scheduled deployments: {$totalScheduled}\n";
    echo "- With environment assigned: {$withEnvironment}\n";
    echo "- Without environment: " . ($totalScheduled - $withEnvironment) . "\n";
    
    if ($totalScheduled === $withEnvironment) {
        echo "\n✅ All scheduled deployments now have environments assigned!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
