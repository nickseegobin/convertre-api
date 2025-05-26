<?php

echo "=== Quick Test ===\n";

require_once 'src/Services/AuthenticationService.php';

use Convertre\Services\AuthenticationService;

try {
    echo "Testing AuthenticationService...\n";
    
    AuthenticationService::init(__DIR__ . '/storage');
    echo "✓ AuthenticationService initialized\n";
    
    $keyData = AuthenticationService::generateApiKey('test_user', 'Test App');
    echo "✓ Key generated: " . substr($keyData['key'], 0, 10) . "...\n";
    
    $validated = AuthenticationService::validateApiKey($keyData['key']);
    if ($validated) {
        echo "✓ Key validation works\n";
    } else {
        echo "❌ Key validation failed\n";
    }
    
    echo "\n🎉 Authentication works!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}