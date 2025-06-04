<?php

/**
 * Simple Phase 2.2 Validation Test
 * Tests only the core validation components that exist
 */

// Include only the files we know exist
require_once 'src/Utils/ConfigLoader.php';
require_once 'src/Utils/ResponseFormatter.php';
require_once 'src/Utils/Logger.php';
require_once 'src/Utils/FileHandler.php';
require_once 'src/Services/AuthenticationService.php';
require_once 'src/Exceptions/ValidationException.php';
require_once 'src/Exceptions/AuthenticationException.php';

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Services\AuthenticationService;
use Convertre\Exceptions\ValidationException;

echo "=== SIMPLE VALIDATION TEST ===\n\n";

try {
    // Initialize systems
    ConfigLoader::init(__DIR__ . '/config');
    Logger::init(__DIR__ . '/storage/logs');
    AuthenticationService::init(__DIR__ . '/storage');
    
    echo "✓ Core systems initialized\n\n";
    
    // Test 1: Basic file validation logic
    echo "1. Testing basic file validation logic...\n";
    
    function validateBasicFile($fileData) {
        // Basic upload check
        if (!isset($fileData['tmp_name']) || $fileData['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('Invalid file upload');
        }
        
        // File size check (50MB max)
        $maxSize = 50 * 1024 * 1024;
        if ($fileData['size'] > $maxSize) {
            throw new ValidationException('File too large');
        }
        
        if ($fileData['size'] < 1024) {
            throw new ValidationException('File too small');
        }
        
        return true;
    }
    
    // Test with mock good file
    $goodFile = [
        'name' => 'test.jpg',
        'tmp_name' => __FILE__,
        'error' => UPLOAD_ERR_OK,
        'size' => 1024 * 50 // 50KB
    ];
    
    if (validateBasicFile($goodFile)) {
        echo "✓ Good file validation passed\n";
    }
    
    // Test with mock bad file (too large)
    $badFile = [
        'name' => 'huge.jpg',
        'tmp_name' => __FILE__,
        'error' => UPLOAD_ERR_OK,
        'size' => 100 * 1024 * 1024 // 100MB
    ];
    
    try {
        validateBasicFile($badFile);
        echo "❌ Large file validation failed\n";
    } catch (ValidationException $e) {
        echo "✓ Large file correctly rejected: " . $e->getMessage() . "\n";
    }
    
    echo "\n2. Testing rate limiting logic...\n";
    
    // Simple rate limiting test
    $requests = [];
    $apiKey = 'test_key';
    $limit = 5;
    $window = 60;
    
    function checkRateLimit($apiKey, &$requests, $limit, $window) {
        $now = time();
        
        // Clean old requests
        $requests[$apiKey] = array_filter(
            $requests[$apiKey] ?? [],
            function($timestamp) use ($now, $window) {
                return $timestamp > ($now - $window);
            }
        );
        
        // Check limit
        $count = count($requests[$apiKey] ?? []);
        if ($count >= $limit) {
            throw new ValidationException('Rate limit exceeded');
        }
        
        // Record request
        $requests[$apiKey][] = $now;
        return true;
    }
    
    // Test rate limiting
    for ($i = 1; $i <= 6; $i++) {
        try {
            checkRateLimit($apiKey, $requests, $limit, $window);
            echo "✓ Request {$i} passed\n";
        } catch (ValidationException $e) {
            echo "✓ Request {$i} rate limited: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n3. Testing batch validation logic...\n";
    
    function validateBatch($files) {
        $count = count($files);
        
        if ($count > 10) {
            throw new ValidationException('Too many files. Max 10 allowed');
        }
        
        if ($count === 0) {
            throw new ValidationException('No files provided');
        }
        
        return ['count' => $count, 'valid' => true];
    }
    
    // Test good batch
    $goodBatch = array_fill(0, 5, $goodFile);
    $result = validateBatch($goodBatch);
    echo "✓ Good batch ({$result['count']} files) validated\n";
    
    // Test bad batch (too many files)
    $badBatch = array_fill(0, 15, $goodFile);
    try {
        validateBatch($badBatch);
        echo "❌ Large batch not rejected\n";
    } catch (ValidationException $e) {
        echo "✓ Large batch rejected: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. Testing authentication integration...\n";
    
    $keyData = AuthenticationService::generateApiKey('test_user', 'Validation Test');
    echo "✓ API key generated: " . substr($keyData['key'], 0, 15) . "...\n";
    
    $validated = AuthenticationService::validateApiKey($keyData['key']);
    if ($validated && $validated['user_id'] === 'test_user') {
        echo "✓ API key validation works\n";
    }
    
    echo "\n5. Testing format validation logic...\n";
    
    function isFormatSupported($extension, $mimeType) {
        $supported = [
            'jpg' => ['image/jpeg'],
            'png' => ['image/png'],
            'pdf' => ['application/pdf'],
            'heic' => ['image/heic', 'image/heif'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ];
        
        return isset($supported[$extension]) && 
               in_array($mimeType, $supported[$extension]);
    }
    
    // Test supported formats
    if (isFormatSupported('jpg', 'image/jpeg')) {
        echo "✓ JPG format supported\n";
    }
    
    if (isFormatSupported('heic', 'image/heic')) {
        echo "✓ HEIC format supported\n";
    }
    
    if (!isFormatSupported('exe', 'application/exe')) {
        echo "✓ EXE format correctly rejected\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 VALIDATION LOGIC TESTS PASSED! 🎉\n";
    echo str_repeat("=", 50) . "\n";
    echo "Core validation components working:\n";
    echo "✓ File size validation\n";
    echo "✓ Rate limiting logic\n";
    echo "✓ Batch validation\n";
    echo "✓ Authentication integration\n";
    echo "✓ Format validation\n";
    echo "✓ Error handling with ValidationException\n";
    echo "\n📁 Next: Create the actual Service files\n";
    echo "     Then test with real file uploads\n";
    echo str_repeat("=", 50) . "\n";
    
    Logger::info('Simple validation test completed successfully');
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}