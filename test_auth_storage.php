<?php
/**
 * Test Script for AuthenticationService JSON Storage
 * 
 * Place this file one directory up from the API root
 * Run: php test_auth_service_json.php
 */

// Include the AuthenticationService
require_once __DIR__ . '/src/Services/AuthenticationService.php';

use Convertre\Services\AuthenticationService;

echo "=== CONVERTRE API - AuthenticationService JSON Storage Test ===\n\n";

// Test configuration
$testStoragePath = __DIR__ . '/test_storage';
$jsonFilePath = $testStoragePath . '/api_keys.json';

// Clean up any existing test files
if (file_exists($jsonFilePath)) {
    unlink($jsonFilePath);
    echo "✓ Cleaned up existing test file\n";
}

if (is_dir($testStoragePath)) {
    rmdir($testStoragePath);
    echo "✓ Cleaned up existing test directory\n";
}

echo "\n--- Test 1: Service Initialization ---\n";
try {
    AuthenticationService::init($testStoragePath);
    echo "✓ Service initialized successfully\n";
    echo "✓ Storage directory created: " . (is_dir($testStoragePath) ? "YES" : "NO") . "\n";
    echo "✓ JSON file created: " . (file_exists($jsonFilePath) ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "✗ Initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 2: Generate API Keys ---\n";
$keys = [];

try {
    // Generate first key
    $key1 = AuthenticationService::generateApiKey('user_123', 'Test Key 1');
    $keys[] = $key1;
    echo "✓ Generated Key 1: " . substr($key1['key'], 0, 15) . "...\n";
    echo "  - User ID: " . $key1['user_id'] . "\n";
    echo "  - Name: " . $key1['name'] . "\n";
    echo "  - Created: " . $key1['created_at'] . "\n";
    
    // Generate second key
    $key2 = AuthenticationService::generateApiKey('user_456', 'WordPress Plugin');
    $keys[] = $key2;
    echo "✓ Generated Key 2: " . substr($key2['key'], 0, 15) . "...\n";
    
} catch (Exception $e) {
    echo "✗ Key generation failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 3: Validate JSON File Structure ---\n";
if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    $jsonData = json_decode($jsonContent, true);
    
    if ($jsonData === null) {
        echo "✗ Invalid JSON format\n";
        exit(1);
    }
    
    echo "✓ JSON file is valid\n";
    echo "✓ Keys in file: " . count($jsonData) . "\n";
    echo "✓ File size: " . filesize($jsonFilePath) . " bytes\n";
    
    // Check key structure
    $firstKey = array_values($jsonData)[0];
    $requiredFields = ['key', 'user_id', 'name', 'created_at', 'usage_count', 'active'];
    
    foreach ($requiredFields as $field) {
        if (!isset($firstKey[$field])) {
            echo "✗ Missing required field: $field\n";
            exit(1);
        }
    }
    echo "✓ All required fields present\n";
} else {
    echo "✗ JSON file not found\n";
    exit(1);
}

echo "\n--- Test 4: API Key Validation ---\n";
try {
    // Test valid key
    $validationResult1 = AuthenticationService::validateApiKey($keys[0]['key']);
    if ($validationResult1) {
        echo "✓ Valid key accepted: " . substr($keys[0]['key'], 0, 15) . "...\n";
        echo "  - Usage count: " . $validationResult1['usage_count'] . "\n";
    } else {
        echo "✗ Valid key rejected\n";
        exit(1);
    }
    
    // Test invalid key
    $validationResult2 = AuthenticationService::validateApiKey('invalid_key_123');
    if ($validationResult2 === null) {
        echo "✓ Invalid key properly rejected\n";
    } else {
        echo "✗ Invalid key was accepted\n";
        exit(1);
    }
    
    // Test the same valid key again (should increment usage)
    $validationResult3 = AuthenticationService::validateApiKey($keys[0]['key']);
    if ($validationResult3 && $validationResult3['usage_count'] == 2) {
        echo "✓ Usage count incremented properly: " . $validationResult3['usage_count'] . "\n";
    } else {
        echo "✗ Usage count not incrementing\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Validation test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 5: Statistics ---\n";
try {
    $stats = AuthenticationService::getStats();
    echo "✓ Stats retrieved successfully:\n";
    echo "  - Total keys: " . $stats['total_keys'] . "\n";
    echo "  - Active keys: " . $stats['active_keys'] . "\n";
    echo "  - Total usage: " . $stats['total_usage'] . "\n";
    
    if ($stats['total_keys'] == 2 && $stats['active_keys'] == 2 && $stats['total_usage'] == 2) {
        echo "✓ Statistics are accurate\n";
    } else {
        echo "✗ Statistics don't match expected values\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Stats test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 6: File Persistence Test ---\n";
// Simulate a new request by creating a fresh instance
try {
    // Create a completely new instance (simulating a fresh HTTP request)
    AuthenticationService::init($testStoragePath);
    
    // Try to validate a key that was created in the "previous request"
    $persistenceTest = AuthenticationService::validateApiKey($keys[1]['key']);
    
    if ($persistenceTest) {
        echo "✓ Keys persist across 'requests' (sessions)\n";
        echo "  - Retrieved key for user: " . $persistenceTest['user_id'] . "\n";
        echo "  - Usage count: " . $persistenceTest['usage_count'] . "\n";
    } else {
        echo "✗ Keys don't persist across sessions\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Persistence test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 7: Debug Method ---\n";
try {
    $allKeys = AuthenticationService::getAllKeys();
    echo "✓ Debug method works\n";
    echo "✓ Retrieved " . count($allKeys) . " keys via debug method\n";
    
    // Verify debug data matches generated keys
    if (isset($allKeys[$keys[0]['key']]) && isset($allKeys[$keys[1]['key']])) {
        echo "✓ Debug method returns correct key data\n";
    } else {
        echo "✗ Debug method missing keys\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Debug test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Test 8: Edge Cases ---\n";
try {
    // Test empty key
    $emptyResult = AuthenticationService::validateApiKey('');
    if ($emptyResult === null) {
        echo "✓ Empty key properly rejected\n";
    } else {
        echo "✗ Empty key was accepted\n";
        exit(1);
    }
    
    // Test null key
    $nullResult = AuthenticationService::validateApiKey(null);
    if ($nullResult === null) {
        echo "✓ Null key properly rejected\n";
    } else {
        echo "✗ Null key was accepted\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ Edge case test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Cleanup ---\n";
// Clean up test files
if (file_exists($jsonFilePath)) {
    unlink($jsonFilePath);
    echo "✓ Test JSON file removed\n";
}

if (is_dir($testStoragePath)) {
    rmdir($testStoragePath);
    echo "✓ Test directory removed\n";
}

echo "\n=== ALL TESTS PASSED! ===\n";
echo "✓ JSON file storage is working correctly\n";
echo "✓ Keys persist across requests/sessions\n";
echo "✓ Validation logic is sound\n";
echo "✓ Usage counting works properly\n";
echo "✓ Statistics are accurate\n";
echo "✓ Edge cases handled correctly\n";

echo "\n🎉 Your AuthenticationService is ready for WordPress plugin integration!\n";
echo "\nNext steps:\n";
echo "1. Replace your AuthenticationService.php with the JSON version\n";
echo "2. Generate a fresh API key via /generate-key\n";
echo "3. Test with your WordPress plugin\n";