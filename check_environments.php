<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Environment;

echo "🔍 Checking active environments...\n\n";

$envs = Environment::active()->get();

foreach ($envs as $env) {
    echo "Name: {$env->name}\n";
    echo "UNC Path: {$env->server_unc_path}\n";
    echo "Base Path: {$env->server_base_path}\n";
    echo "Web URL: {$env->web_base_url}\n";
    echo "Deploy Endpoint: {$env->deploy_endpoint_base}\n";
    echo "Active: " . ($env->is_active ? 'Yes' : 'No') . "\n";
    echo "Order: {$env->order}\n";
    echo "---\n";
}

echo "Found {$envs->count()} active environments.\n";