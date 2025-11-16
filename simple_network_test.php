<?php

echo "🔍 Simple Network Path Test\n\n";

// Test paths from the error logs
$testPaths = [
    '\\\\10.10.16.47\\c$\\wamp64\\www\\dep_env\\test_file.php',
    '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env\\test_file.php'
];

foreach ($testPaths as $i => $path) {
    echo "🧪 Testing path " . ($i + 1) . ": {$path}\n";
    
    // Check if directory exists
    $directory = dirname($path);
    echo "   Directory exists: " . (is_dir($directory) ? '✅ Yes' : '❌ No') . "\n";
    echo "   Directory writable: " . (is_writable($directory) ? '✅ Yes' : '❌ No') . "\n";
    
    // Try to write a simple test file
    echo "   Writing test file...\n";
    $content = "<?php echo 'Test file written at ' . date('Y-m-d H:i:s');";
    $result = @file_put_contents($path, $content);
    
    if ($result === false) {
        $error = error_get_last();
        echo "   ❌ Failed: " . ($error['message'] ?? 'Unknown error') . "\n";
    } else {
        echo "   ✅ Success ({$result} bytes)\n";
        
        // Try to read it back
        $readContent = @file_get_contents($path);
        if ($readContent !== false) {
            echo "   ✅ Read back successful\n";
        } else {
            echo "   ❌ Could not read file back\n";
        }
        
        // Clean up
        if (@unlink($path)) {
            echo "   🗑️  Cleaned up test file\n";
        } else {
            echo "   ⚠️  Could not clean up test file\n";
        }
    }
    
    echo "\n";
}

echo "🏁 Test complete!\n";