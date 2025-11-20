<?php

function processEnvVariables($envVariables) {
    $lines = explode("\n", $envVariables);
    $processedLines = array();
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '#') === 0) {
            $processedLines[] = $line;
            continue;
        }
        
        // Check if this is a key=value pair
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            echo "Processing line: {$key}={$value}\n";
            
            // If value is quoted, ensure proper escaping
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                echo "  Found double-quoted value: {$matches[1]}\n";
                $innerValue = $matches[1];
                // Escape quotes within the value
                $escapedValue = str_replace('"', '\"', $innerValue);
                echo "  Escaped value: {$escapedValue}\n";
                $processedLines[] = $key . '="' . $escapedValue . '"';
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                echo "  Found single-quoted value: {$matches[1]}\n";
                $innerValue = $matches[1];
                // For single quotes, we need to escape differently
                $escapedValue = str_replace('\'', '\\\'', $innerValue);
                echo "  Escaped value: {$escapedValue}\n";
                $processedLines[] = $key . '=\'' . $escapedValue . '\'';
            } else {
                // For unquoted values, just add as is
                echo "  Unquoted value\n";
                $processedLines[] = $key . '=' . $value;
            }
        } else {
            // Not a key=value pair, add as is
            echo "  Non key=value line\n";
            $processedLines[] = $line;
        }
    }
    
    return implode("\n", $processedLines);
}

// Test with the problematic line
$testLine = 'MAIL_FROM_NAME="Deployment Management System"';

echo "Testing with: {$testLine}\n";
echo "Result: " . processEnvVariables($testLine) . "\n\n";

// Test with VITE_APP_NAME
$testLine2 = 'VITE_APP_NAME="${APP_NAME}"';

echo "Testing with: {$testLine2}\n";
echo "Result: " . processEnvVariables($testLine2) . "\n\n";

// Test with a line that has quotes inside
$testLine3 = 'TEST_VAR="This is a \"quoted\" string"';

echo "Testing with: {$testLine3}\n";
echo "Result: " . processEnvVariables($testLine3) . "\n\n";