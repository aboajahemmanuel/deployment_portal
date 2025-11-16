<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Environment;

echo "🧪 Creating test environment for deletion testing...\n\n";

try {
    $testEnv = Environment::create([
        'name' => 'Test Environment',
        'slug' => 'test-env-' . time(),
        'server_base_path' => 'C:\\xampp\\htdocs\\test',
        'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\test',
        'web_base_url' => 'http://test-101-php-01.fmdqgroup.com',
        'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/test',
        'description' => 'Temporary test environment for deletion testing',
        'is_active' => false,
        'order' => 999,
    ]);

    echo "✅ Test environment created successfully!\n";
    echo "   Name: {$testEnv->name}\n";
    echo "   Slug: {$testEnv->slug}\n";
    echo "   ID: {$testEnv->id}\n\n";
    echo "🗑️  This environment has 0 projects and can be deleted.\n";
    echo "   Go to /admin/environments to see the clickable delete button.\n";

} catch (\Exception $e) {
    echo "❌ Error creating test environment: " . $e->getMessage() . "\n";
}
