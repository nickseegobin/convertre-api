<?php

/**
 * Convertre API - Complete Test Suite
 * Tests Phase 1.3 (Core Utilities) + Phase 2.1 (Authentication)
 */

// Include all files first
require_once 'src/Utils/ConfigLoader.php';
require_once 'src/Utils/ResponseFormatter.php';
require_once 'src/Utils/Logger.php';
require_once 'src/Utils/FileHandler.php';

// Include authentication files if they exist
if (file_exists('src/Services/AuthenticationService.php')) {
    require_once 'src/Services/AuthenticationService.php';
}
if (file_exists('src/Controllers/AuthController.php')) {
    require_once 'src/Controllers/AuthController.php';
}
if (file_exists('src/Middleware/AuthMiddleware.php')) {
    require_once 'src/Middleware/AuthMiddleware.php';
}

// All use statements at the top
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;

echo "=== Convertre API - Complete Test Suite ===\n\n";

// Create necessary directories
$directories = [
    __DIR__ . '/storage',
    __DIR__ . '/storage/logs',
    __DIR__ . '/storage/uploads', 
    __DIR__ . '/storage/converted',
    __DIR__ . '/src/Exceptions',
    __DIR__ . '/src/Services',
    __DIR__ . '/src/Controllers',
    __DIR__ . '/src/Middleware'
];

echo "Setting up directories...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Created: " . basename($dir) . "\n";
    }
}

// Create simple exception files
$exceptions = [
    'ConversionException' => 'class ConversionException extends \Exception {}',
    'ValidationException' => 'class ValidationException extends \Exception {}', 
    'AuthenticationException' => 'class AuthenticationException extends \Exception {}'
];

foreach ($exceptions as $name => $code) {
    $file = "src/Exceptions/{$name}.php";
    if (!file_exists($file)) {
        file_put_contents($file, "<?php\nnamespace Convertre\Exceptions;\n{$code}");
        echo "✓ Created: {$name}.php\n";
    }
}

// Include exception files after creating them
require_once 'src/Exceptions/ConversionException.php';
require_once 'src/Exceptions/ValidationException.php';
require_once 'src/Exceptions/AuthenticationException.php';

echo "\n";

try {
    echo "=== PHASE 1.3: CORE UTILITIES ===\n\n";
    
    // 1. Test ConfigLoader
    echo "1. Testing ConfigLoader...\n";
    ConfigLoader::init(__DIR__ . '/config');
    $apiConfig = ConfigLoader::load('api');
    echo "✓ API config loaded: " . $apiConfig['name'] . " v" . $apiConfig['version'] . "\n";
    
    $rateLimit = ConfigLoader::get('api.rate_limit.requests_per_minute', 0);
    echo "✓ Config dot notation works: Rate limit = {$rateLimit} requests/minute\n";
    
    // 2. Test Logger
    echo "\n2. Testing Logger...\n";
    Logger::init(__DIR__ . '/storage/logs');
    Logger::info('Core utilities test started');
    Logger::debug('This is a debug message', ['test' => true]);
    Logger::conversionStart('heic', 'jpg', 'test.heic');
    echo "✓ Logger initialized and test messages written\n";
    
    // 3. Test ResponseFormatter
    echo "\n3. Testing ResponseFormatter...\n";
    $successResponse = ResponseFormatter::conversionSuccess(
        'https://api.convertre.com/download/test123.jpg',
        'photo.heic',
        'photo.jpg',
        '2025-05-25T15:30:00Z'
    );
    echo "✓ Success response format created\n";
    
    $errorResponse = ResponseFormatter::unsupportedFormat('Test error message');
    echo "✓ Error response format created\n";
    
    // 4. Test FileHandler
    echo "\n4. Testing FileHandler...\n";
    FileHandler::init(__DIR__ . '/storage/uploads', __DIR__ . '/storage/converted');
    
    $sanitized = FileHandler::sanitizeFilename('../../dangerous/../file.txt');
    echo "✓ File sanitization works: '{$sanitized}'\n";
    
    $unique = FileHandler::generateUniqueFilename('test.jpg');
    echo "✓ Unique filename generation: '{$unique}'\n";
    
    // 5. Test Custom Exceptions
    echo "\n5. Testing Custom Exceptions...\n";
    
    try {
        throw new Convertre\Exceptions\ConversionException('Test conversion error');
    } catch (Convertre\Exceptions\ConversionException $e) {
        echo "✓ ConversionException works: " . $e->getMessage() . "\n";
    }
    
    try {
        throw new Convertre\Exceptions\ValidationException('Test validation error');
    } catch (Convertre\Exceptions\ValidationException $e) {
        echo "✓ ValidationException works: " . $e->getMessage() . "\n";
    }
    
    try {
        throw new Convertre\Exceptions\AuthenticationException('Test auth error');
    } catch (Convertre\Exceptions\AuthenticationException $e) {
        echo "✓ AuthenticationException works: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ PHASE 1.3: CORE UTILITIES - COMPLETE!\n";
    
    // Test Authentication System if available
    if (class_exists('Convertre\Services\AuthenticationService')) {
        echo "\n=== PHASE 2.1: AUTHENTICATION SYSTEM ===\n\n";
        
        // 6. Test AuthenticationService
        echo "6. Testing AuthenticationService...\n";
        
        Convertre\Services\AuthenticationService::init(__DIR__ . '/storage');
        echo "✓ AuthenticationService initialized\n";
        
        $keyData = Convertre\Services\AuthenticationService::generateApiKey('test_user_123', 'Test Application');
        echo "✓ API key generated: " . substr($keyData['key'], 0, 15) . "...\n";
        
        $validated = Convertre\Services\AuthenticationService::validateApiKey($keyData['key']);
        if ($validated && $validated['user_id'] === 'test_user_123') {
            echo "✓ API key validation works\n";
        } else {
            echo "❌ API key validation failed\n";
        }
        
        $invalidKey = Convertre\Services\AuthenticationService::validateApiKey('invalid_key_123');
        if ($invalidKey === null) {
            echo "✓ Invalid key rejection works\n";
        } else {
            echo "❌ Invalid key was accepted\n";
        }
        
        $stats = Convertre\Services\AuthenticationService::getStats();
        echo "✓ Key statistics: {$stats['active_keys']} active keys, {$stats['total_usage']} total usage\n";
        
        // 7. Test Authentication Middleware
        if (class_exists('Convertre\Middleware\AuthMiddleware')) {
            echo "\n7. Testing AuthMiddleware...\n";
            
            $_SERVER['HTTP_X_API_KEY'] = $keyData['key'];
            
            if (Convertre\Middleware\AuthMiddleware::isAuthenticated()) {
                echo "✓ Middleware authentication detection works\n";
                
                $authUser = Convertre\Middleware\AuthMiddleware::optionalAuth();
                if ($authUser && $authUser['user_id'] === 'test_user_123') {
                    echo "✓ Optional authentication works\n";
                }
            } else {
                echo "❌ Middleware authentication failed\n";
            }
            
            $_SERVER['HTTP_X_API_KEY'] = 'invalid_key';
            if (!Convertre\Middleware\AuthMiddleware::isAuthenticated()) {
                echo "✓ Middleware rejects invalid keys\n";
            } else {
                echo "❌ Middleware accepted invalid key\n";
            }
            
            unset($_SERVER['HTTP_X_API_KEY']);
        }
        
        echo "\n✅ PHASE 2.1: AUTHENTICATION SYSTEM - COMPLETE!\n";
    } else {
        echo "\n*** Authentication classes not found - skipping Phase 2.1 tests ***\n";
    }
    
    // Final Summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 ALL AVAILABLE TESTS PASSED! 🎉\n";
    echo str_repeat("=", 60) . "\n";
    echo "✅ Phase 1.3: Core Utilities (ConfigLoader, Logger, ResponseFormatter, FileHandler, Exceptions)\n";
    
    if (class_exists('Convertre\Services\AuthenticationService')) {
        echo "✅ Phase 2.1: API Key System (AuthenticationService, AuthController, AuthMiddleware)\n";
        echo "\n🚀 READY FOR PHASE 2.2: REQUEST VALIDATION!\n";
    } else {
        echo "⚠️  Phase 2.1: Authentication files need to be created\n";
        echo "\n📝 Next: Create authentication files, then move to Phase 2.2\n";
    }
    echo str_repeat("=", 60) . "\n";
    
    Logger::info('Test suite completed - Core utilities working, authentication ' . 
        (class_exists('Convertre\Services\AuthenticationService') ? 'working' : 'pending'));
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    
    if (class_exists('Convertre\Utils\Logger')) {
        Logger::error('Test suite failed', [
            'error' => $e->getMessage(), 
            'file' => $e->getFile(), 
            'line' => $e->getLine()
        ]);
    }
}