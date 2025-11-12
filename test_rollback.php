<?php

// Test script to verify rollback functionality

echo "Testing rollback functionality...\n";

// Test data for rollback
$testData = [
    'rollback' => true,
    'rollback_target_commit' => 'abc123def456',
    'rollback_reason' => 'Testing rollback functionality',
    'project_id' => 1,
    'deployment_id' => 1
];

// Convert to JSON
$jsonData = json_encode($testData);

echo "Sending test data:\n";
echo $jsonData . "\n\n";

// Test the rollback endpoint
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Authorization: Bearer test-token-123'
        ],
        'content' => $jsonData
    ]
]);

// Try to call the rollback script
$response = @file_get_contents('http://localhost/rollback.php', false, $context);

if ($response === false) {
    echo "Error: Could not connect to rollback endpoint\n";
    echo "Please make sure the server is running and the endpoint is accessible\n";
} else {
    echo "Response from rollback endpoint:\n";
    echo $response . "\n";
}

echo "\nTest completed.\n";