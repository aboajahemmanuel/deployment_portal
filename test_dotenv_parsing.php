<?php

// Test if the dotenv parser can handle our .env file
$envContent = file_get_contents('.env');

echo "Testing .env file parsing...\n";
echo "==========================\n";

// Try to parse it with a simple approach
$lines = explode("\n", $envContent);
$parsingErrors = [];

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Skip empty lines and comments
    if (empty($line) || strpos($line, '#') === 0) {
        continue;
    }
    
    // Check if this is a key=value pair
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Try to detect problematic patterns
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $innerValue = $matches[1];
            // Check if there are unescaped quotes inside
            if (preg_match('/[^\\\\]"/', $innerValue)) {
                $parsingErrors[] = "Line " . ($lineNum + 1) . ": Potential unescaped quotes in value: {$line}";
            }
        }
    }
}

if (empty($parsingErrors)) {
    echo "✅ No obvious parsing issues detected\n";
} else {
    echo "❌ Potential parsing issues found:\n";
    foreach ($parsingErrors as $error) {
        echo "  - {$error}\n";
    }
}

// Test with a simple dotenv parser approach
echo "\nTesting with simplified parser:\n";
echo "=============================\n";

function parseDotEnv($content) {
    $lines = explode("\n", $content);
    $vars = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Check if this is a key=value pair
        if (strpos($line, '=') !== false) {
            $parts = explode('=', $line, 2);
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Handle quoted values
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
                // Unescape quotes
                $value = str_replace('\"', '"', $value);
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
                // Unescape single quotes
                $value = str_replace('\\\'', '\'', $value);
            }
            
            $vars[$key] = $value;
        }
    }
    
    return $vars;
}

try {
    $parsed = parseDotEnv($envContent);
    echo "✅ Successfully parsed .env file\n";
    echo "MAIL_FROM_NAME: " . ($parsed['MAIL_FROM_NAME'] ?? 'NOT FOUND') . "\n";
    echo "VITE_APP_NAME: " . ($parsed['VITE_APP_NAME'] ?? 'NOT FOUND') . "\n";
} catch (Exception $e) {
    echo "❌ Failed to parse .env file: " . $e->getMessage() . "\n";
}