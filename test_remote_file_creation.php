<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Environment;

echo "🔍 Testing remote file creation capabilities...\n\n";

// Get all active environments
$environments = Environment::active()->ordered()->get();

if ($environments->isEmpty()) {
    echo "❌ No active environments found.\n";
    exit(1);
}

echo "📊 Found {$environments->count()} active environments\n\n";

foreach ($environments as $environment) {
    echo "🧪 Testing {$environment->name} environment:\n";
    echo "   Server Base Path: {$environment->server_base_path}\n";
    echo "   Server UNC Path: {$environment->server_unc_path}\n";
    echo "   Web Base URL: {$environment->web_base_url}\n";
    echo "   Deploy Endpoint Base: {$environment->deploy_endpoint_base}\n";
    
    // Test UNC path accessibility
    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
    if (!str_starts_with($uncBase, '\\\\')) {
        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
    }
    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
    
    echo "   Formatted UNC Base: {$uncBase}\n";
    echo "   Target Base: {$targetBase}\n";
    
    // Test if directory exists
    if (is_dir($targetBase)) {
        echo "   ✅ Directory exists and is accessible\n";
        
        // Test file creation
        $testFilePath = $targetBase . 'test444444444_file_creation_' . time() . '.php';
        $testContent = "Test file created at " . date('Y-m-d H:i:s') . "\nThis file was created to test remote file creation capabilities.\n";
        
        echo "   Writing test file: {$testFilePath}\n";
        
        $result = @file_put_contents($testFilePath, $testContent);
        if ($result !== false) {
            echo "   ✅ Successfully wrote test file ({$result} bytes)\n";
            
            // Verify file content
            $readContent = @file_get_contents($testFilePath);
            if ($readContent === $testContent) {
                echo "   ✅ File content verified\n";
            } else {
                echo "   ❌ File content mismatch\n";
            }
            
            // Clean up test file
            if (@unlink($testFilePath)) {
                echo "   🗑️  Cleaned up test file\n";
            } else {
                echo "   ⚠️  Failed to clean up test file\n";
            }
        } else {
            $error = error_get_last();
            echo "   ❌ Failed to write test file: " . ($error['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ Directory does not exist or is not accessible\n";
        echo "   This could be due to:\n";
        echo "   - Network connectivity issues\n";
        echo "   - Authentication/permission issues\n";
        echo "   - Incorrect UNC path\n";
    }
    
    echo "\n";
}

echo "🏁 Test complete!\n";