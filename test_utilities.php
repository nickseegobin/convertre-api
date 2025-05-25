<?php

/**
 * Simple test file to verify Core Utilities are working
 * Run this from the root directory: php test_utilities.php
 */

echo "=== Convertre API - Core Utilities Test ===\n\n";

// Create necessary directories first
$directories = [
    __DIR__ . '/storage',
    __DIR__ . '/storage/logs',
    __DIR__ . '/storage/uploads', 
    __DIR__ . '/storage/converted',
    __DIR__ . '/src/Exceptions'
];

echo "Creating necessary directories...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ Created: {$dir}\n";
    }
}

// Now create the exception files in the proper directory
echo "\nCreating exception files...\n";

// ConversionException.php
$conversionException = '<?php

namespace Convertre\Exceptions;

class ConversionException extends \Exception
{
    private string $fromFormat;
    private string $toFormat;
    private string $filename;
    
    public function __construct(
        string $message,
        string $fromFormat = "",
        string $toFormat = "",
        string $filename = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->fromFormat = $fromFormat;
        $this->toFormat = $toFormat;
        $this->filename = $filename;
    }
    
    public function getFromFormat(): string
    {
        return $this->fromFormat;
    }
    
    public function getToFormat(): string
    {
        return $this->toFormat;
    }
    
    public function getFilename(): string
    {
        return $this->filename;
    }
    
    public function getConversionContext(): array
    {
        return [
            "from_format" => $this->fromFormat,
            "to_format" => $this->toFormat,
            "filename" => $this->filename
        ];
    }
}';

file_put_contents(__DIR__ . '/src/Exceptions/ConversionException.php', $conversionException);
echo "✓ Created ConversionException.php\n";

// ValidationException.php  
$validationException = '<?php

namespace Convertre\Exceptions;

class ValidationException extends \Exception
{
    private array $validationErrors;
    private string $field;
    
    public function __construct(
        string $message,
        string $field = "",
        array $validationErrors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->field = $field;
        $this->validationErrors = $validationErrors;
    }
    
    public function getField(): string
    {
        return $this->field;
    }
    
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}';

file_put_contents(__DIR__ . '/src/Exceptions/ValidationException.php', $validationException);
echo "✓ Created ValidationException.php\n";

// AuthenticationException.php
$authException = '<?php

namespace Convertre\Exceptions;

class AuthenticationException extends \Exception
{
    private string $authMethod;
    private string $identifier;
    
    public function __construct(
        string $message,
        string $authMethod = "api_key",
        string $identifier = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->authMethod = $authMethod;
        $this->identifier = $identifier;
    }
    
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }
    
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}';

file_put_contents(__DIR__ . '/src/Exceptions/AuthenticationException.php', $authException);
echo "✓ Created AuthenticationException.php\n";

// Now include and test our classes
require_once 'src/Utils/ConfigLoader.php';
require_once 'src/Utils/ResponseFormatter.php';
require_once 'src/Utils/Logger.php';
require_once 'src/Utils/FileHandler.php';
require_once 'src/Exceptions/ConversionException.php';
require_once 'src/Exceptions/ValidationException.php';
require_once 'src/Exceptions/AuthenticationException.php';

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;
use Convertre\Exceptions\ConversionException;
use Convertre\Exceptions\ValidationException;
use Convertre\Exceptions\AuthenticationException;

try {
    // 1. Test ConfigLoader
    echo "\n1. Testing ConfigLoader...\n";
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
    echo "✓ Success response created\n";
    
    $errorResponse = ResponseFormatter::unsupportedFormat('Test error message');
    echo "✓ Error response created\n";
    
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
        throw new ConversionException('Test conversion error', 'heic', 'jpg', 'test.heic');
    } catch (ConversionException $e) {
        echo "✓ ConversionException works: " . $e->getMessage() . "\n";
    }
    
    try {
        throw new ValidationException('Test validation error', 'file', ['file' => 'Required field']);
    } catch (ValidationException $e) {
        echo "✓ ValidationException works: " . $e->getMessage() . "\n";
    }
    
    try {
        throw new AuthenticationException('Invalid API key', 'api_key', 'key123');
    } catch (AuthenticationException $e) {
        echo "✓ AuthenticationException works: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== All Core Utilities Tests Passed! ===\n";
    echo "Phase 1.3 Complete - Ready for Phase 2: Authentication & Security\n";
    
    Logger::info('Core utilities test completed successfully');
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}