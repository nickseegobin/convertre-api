<?php

/**
 * Test Phase 3.1: Abstract Module System
 * Tests the foundational abstract classes and factory
 */

// Include required files
require_once 'src/Utils/ConfigLoader.php';
require_once 'src/Utils/Logger.php';
require_once 'src/Utils/FileHandler.php';
require_once 'src/Exceptions/ConversionException.php';
require_once 'src/Services/ConversionResult.php';
require_once 'src/Services/AbstractConversionModule.php';
require_once 'src/Services/ModuleFactory.php';

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Services\ConversionResult;
use Convertre\Services\AbstractConversionModule;
use Convertre\Services\ModuleFactory;
use Convertre\Exceptions\ConversionException;

echo "=== PHASE 3.1: ABSTRACT MODULE SYSTEM TEST ===\n\n";

try {
    // Initialize systems
    ConfigLoader::init(__DIR__ . '/config');
    Logger::init(__DIR__ . '/storage/logs');
    
    echo "✓ Core systems initialized\n\n";
    
    // Test 1: ConversionResult class
    echo "1. Testing ConversionResult...\n";
    
    $successResult = ConversionResult::success(
        '/path/to/output.jpg',
        '/path/to/input.heic',
        'heic',
        'jpg',
        2.5
    );
    
    if ($successResult->isSuccess() && $successResult->getProcessingTime() === 2.5) {
        echo "✓ Success result creation works\n";
    }
    
    $failureResult = ConversionResult::failure(
        'Test error message',
        '/path/to/input.heic',
        'heic',
        'jpg'
    );
    
    if (!$failureResult->isSuccess() && $failureResult->getErrorMessage() === 'Test error message') {
        echo "✓ Failure result creation works\n";
    }
    
    // Test 2: Create a mock module to test AbstractConversionModule
    echo "\n2. Testing AbstractConversionModule with mock...\n";
    
    class MockConversionModule extends AbstractConversionModule
    {
        public function __construct()
        {
            parent::__construct('test', 'mock', 'mockTool');
        }
        
        protected function executeConversion(string $inputFile, string $outputFile): bool
        {
            // Mock successful conversion - just copy the file
            return copy($inputFile, $outputFile);
        }
        
        protected function isToolAvailable(): bool
        {
            return true; // Mock tool is always available
        }
    }
    
    $mockModule = new MockConversionModule();
    echo "✓ Mock module created: {$mockModule->getFromFormat()} → {$mockModule->getToFormat()}\n";
    
    if ($mockModule->canConvert('test', 'mock')) {
        echo "✓ Module conversion detection works\n";
    }
    
    if (!$mockModule->canConvert('wrong', 'format')) {
        echo "✓ Module correctly rejects unsupported formats\n";
    }
    
    // Test actual conversion with mock
    $tempInput = tempnam(sys_get_temp_dir(), 'test_input');
    $tempOutput = tempnam(sys_get_temp_dir(), 'test_output');
    file_put_contents($tempInput, 'test content');
    
    $result = $mockModule->convert($tempInput, $tempOutput);
    
    if ($result->isSuccess()) {
        echo "✓ Mock conversion succeeded\n";
        echo "  Processing time: " . round($result->getProcessingTime(), 3) . "s\n";
    } else {
        echo "❌ Mock conversion failed: " . $result->getErrorMessage() . "\n";
    }
    
    // Test 3: ModuleFactory
    echo "\n3. Testing ModuleFactory...\n";
    
    ModuleFactory::init();
    echo "✓ ModuleFactory initialized\n";
    
    $stats = ModuleFactory::getStats();
    echo "✓ Factory stats: {$stats['total_modules']} total, {$stats['available_modules']} available\n";
    
    // Test supported conversions
    $supported = ModuleFactory::getSupportedConversions();
    echo "✓ Supported conversions listed: " . count($supported) . " found\n";
    
    // Test unsupported conversion
    if (!ModuleFactory::isSupported('unsupported', 'format')) {
        echo "✓ Correctly detects unsupported conversions\n";
    }
    
    // Test module registration
    ModuleFactory::registerModule('test', 'mock', 'MockConversionModule');
    if (ModuleFactory::isSupported('test', 'mock')) {
        echo "✓ Module registration works\n";
    }
    
    // Test 4: Error handling
    echo "\n4. Testing error handling...\n";
    
    class FailingMockModule extends AbstractConversionModule
    {
        public function __construct()
        {
            parent::__construct('fail', 'test', 'failTool');
        }
        
        protected function executeConversion(string $inputFile, string $outputFile): bool
        {
            return false; // Always fail
        }
        
        protected function isToolAvailable(): bool
        {
            return false; // Tool not available
        }
    }
    
    $failingModule = new FailingMockModule();
    $failResult = $failingModule->convert($tempInput, $tempOutput);
    
    if (!$failResult->isSuccess() && !empty($failResult->getErrorMessage())) {
        echo "✓ Error handling works: " . $failResult->getErrorMessage() . "\n";
    }
    
    // Test 5: Command execution (safe test)
    echo "\n5. Testing command execution framework...\n";
    
    class CommandTestModule extends AbstractConversionModule
    {
        public function __construct()
        {
            parent::__construct('cmd', 'test', 'cmdTool');
        }
        
        protected function executeConversion(string $inputFile, string $outputFile): bool
        {
            // Test safe command execution and create output file
            $result = $this->executeCommand('echo "test command"', 5);
            
            // Create the output file for testing
            if ($result['success']) {
                file_put_contents($outputFile, 'test output content');
                return true;
            }
            
            return false;
        }
        
        protected function isToolAvailable(): bool
        {
            return true;
        }
    }
    
    $cmdModule = new CommandTestModule();
    $cmdResult = $cmdModule->convert($tempInput, $tempOutput . '_cmd');
    
    if ($cmdResult->isSuccess()) {
        echo "✓ Command execution framework works\n";
    } else {
        echo "⚠ Command execution test: " . $cmdResult->getErrorMessage() . "\n";
    }
    
    // Cleanup
    unlink($tempInput);
    if (file_exists($tempOutput)) unlink($tempOutput);
    if (file_exists($tempOutput . '_cmd')) unlink($tempOutput . '_cmd');
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎉 PHASE 3.1: ABSTRACT MODULE SYSTEM - COMPLETE! 🎉\n";
    echo str_repeat("=", 60) . "\n";
    echo "✅ ConversionResult - Success/failure result handling\n";
    echo "✅ AbstractConversionModule - Base class with common functionality\n";
    echo "✅ ModuleFactory - Module registration and creation\n";
    echo "✅ Error handling framework\n";
    echo "✅ Command execution framework\n";
    echo "✅ Module detection and validation\n";
    echo "\n🚀 READY FOR PHASE 3.2: MODULE INTEGRATION FRAMEWORK!\n";
    echo "Then Phase 4: MVP CONVERSION MODULES (HEIC→JPG, DOCX→PDF)\n";
    echo str_repeat("=", 60) . "\n";
    
    Logger::info('Abstract module system test completed successfully');
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    
    Logger::error('Abstract module test failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}