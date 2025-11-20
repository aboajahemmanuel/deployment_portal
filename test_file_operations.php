<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

echo "Testing file operations with timeout handling...\n\n";

// Test the writeFileWithRetry method
class TestDeploymentController {
    /**
     * Write content to a file with retry logic and timeout.
     */
    public function writeFileWithRetry(string $filename, string $content, int $maxRetries = 3, int $timeoutSeconds = 60)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Log the attempt
                if ($attempt > 1) {
                    echo "Retrying file write operation: attempt {$attempt}\n";
                    // Wait a bit before retrying
                    sleep(1);
                }
                
                // Try to write the file with timeout
                $result = $this->writeFileWithTimeout($filename, $content, $timeoutSeconds);
                
                if ($result !== false) {
                    // Success
                    if ($attempt > 1) {
                        echo "File write operation succeeded on retry {$attempt}\n";
                    }
                    return $result;
                }
                
                // Log failure
                echo "File write attempt {$attempt} failed\n";
                
            } catch (\Exception $e) {
                echo "Exception during file write attempt {$attempt}: " . $e->getMessage() . "\n";
            } catch (\Error $e) {
                echo "Error during file write attempt {$attempt}: " . $e->getMessage() . "\n";
            }
        }
        
        // All retries failed
        echo "File write operation failed after all retries\n";
        return false;
    }
    
    /**
     * Write content to a file with a timeout.
     */
    private function writeFileWithTimeout(string $filename, string $content, int $timeoutSeconds = 60)
    {
        // Store the current time
        $startTime = time();
        
        try {
            // For network file operations, we need to handle them differently
            if (strpos($filename, '\\\\') === 0) {
                // This is a UNC path, use a more robust approach
                return $this->writeNetworkFile($filename, $content, $timeoutSeconds);
            }
            
            // Use file_put_contents with error suppression for local files
            $result = @file_put_contents($filename, $content);
            
            // Check if we've exceeded the timeout
            $elapsedTime = time() - $startTime;
            if ($elapsedTime > $timeoutSeconds) {
                echo "File write operation timed out ({$elapsedTime}s > {$timeoutSeconds}s)\n";
                return false;
            }
            
            return $result;
        } catch (\Exception $e) {
            echo "Exception during file write operation: " . $e->getMessage() . "\n";
            return false;
        } catch (\Error $e) {
            echo "Error during file write operation: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Write content to a network file (UNC path) with better error handling.
     */
    private function writeNetworkFile(string $filename, string $content, int $timeoutSeconds = 60)
    {
        // Check if the directory exists and is writable first
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            echo "Network directory does not exist: {$directory}\n";
            return false;
        }
        
        if (!is_writable($directory)) {
            echo "Network directory is not writable: {$directory}\n";
            return false;
        }
        
        // Try to write the file with a more controlled approach
        $handle = @fopen($filename, 'w');
        if ($handle === false) {
            $lastError = error_get_last();
            echo "Failed to open network file for writing: " . ($lastError ? $lastError['message'] : 'Unknown error') . "\n";
            return false;
        }
        
        // Write content
        $bytesWritten = @fwrite($handle, $content);
        if ($bytesWritten === false) {
            $lastError = error_get_last();
            echo "Failed to write to network file: " . ($lastError ? $lastError['message'] : 'Unknown error') . "\n";
            @fclose($handle);
            return false;
        }
        
        // Close the file
        if (@fclose($handle) === false) {
            $lastError = error_get_last();
            echo "Warning: Failed to close network file handle: " . ($lastError ? $lastError['message'] : 'Unknown error') . "\n";
            // Still return success since we wrote the content
        }
        
        return $bytesWritten;
    }
}

// Test with a simple file
$testController = new TestDeploymentController();
$testContent = "<?php\necho 'Test file created at ' . date('Y-m-d H:i:s');\n";
$testFile = 'test_file_operations_' . time() . '.php';

echo "Testing local file write...\n";
$result = $testController->writeFileWithRetry($testFile, $testContent, 2, 30);
if ($result !== false) {
    echo "✅ Successfully wrote {$result} bytes to {$testFile}\n";
    
    // Clean up
    if (@unlink($testFile)) {
        echo "🗑️  Cleaned up test file\n";
    }
} else {
    echo "❌ Failed to write to {$testFile}\n";
}

echo "\nTest complete!\n";