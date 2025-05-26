<?php

namespace Convertre\Services;

use Convertre\Services\FileValidationService;
use Convertre\Services\RateLimitService;
use Convertre\Services\AuthenticationService;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ValidationException;
use Convertre\Exceptions\AuthenticationException;

/**
 * RequestValidator - Main validation coordinator
 * Combines authentication, rate limiting, and file validation
 */
class RequestValidator
{
    /**
     * Validate complete conversion request
     */
    public static function validateConversionRequest(): array
    {
        // 1. Authenticate request
        $apiKey = self::getApiKey();
        $authData = self::authenticateApiKey($apiKey);
        
        // 2. Check rate limits
        RateLimitService::checkLimit($apiKey);
        
        // 3. Validate files
        $files = self::getUploadedFiles();
        
        if (count($files) === 1) {
            $validation = FileValidationService::validateFile($files[0]);
            $result = [
                'type' => 'single',
                'auth' => $authData,
                'file' => $validation
            ];
        } else {
            $validation = FileValidationService::validateBatch($files);
            $result = [
                'type' => 'batch',
                'auth' => $authData,
                'files' => $validation
            ];
        }
        
        Logger::info('Request validation completed', [
            'user_id' => $authData['user_id'],
            'type' => $result['type'],
            'file_count' => $result['type'] === 'single' ? 1 : count($files)
        ]);
        
        return $result;
    }
    
    /**
     * Get API key from headers
     */
    private static function getApiKey(): string
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        
        if (!$apiKey) {
            throw new AuthenticationException('API key required in X-API-Key header');
        }
        
        return $apiKey;
    }
    
    /**
     * Authenticate API key
     */
    private static function authenticateApiKey(string $apiKey): array
    {
        $authData = AuthenticationService::validateApiKey($apiKey);
        
        if (!$authData) {
            throw new AuthenticationException('Invalid API key');
        }
        
        return $authData;
    }
    
    /**
     * Get uploaded files from $_FILES
     */
    private static function getUploadedFiles(): array
    {
        if (empty($_FILES)) {
            throw new ValidationException('No files uploaded');
        }
        
        $files = [];
        
        // Handle single file upload
        if (isset($_FILES['file']) && !is_array($_FILES['file']['name'])) {
            $files[] = $_FILES['file'];
        }
        // Handle multiple file upload
        elseif (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $fileCount = count($_FILES['files']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $files[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];
            }
        }
        // Handle batch upload (file1, file2, etc.)
        else {
            foreach ($_FILES as $key => $fileData) {
                if (strpos($key, 'file') === 0 && !is_array($fileData['name'])) {
                    $files[] = $fileData;
                }
            }
        }
        
        if (empty($files)) {
            throw new ValidationException('No valid files found in request');
        }
        
        return $files;
    }

    
    
    /**
     * Validate conversion parameters
     */
    public static function validateConversionParams(): array
    {
        $targetFormat = $_POST['to'] ?? $_GET['to'] ?? null;
        
        if (!$targetFormat) {
            throw new ValidationException('Target format required (to parameter)');
        }
        
        $targetFormat = strtolower(trim($targetFormat));
        
        // MVP formats only
        $allowedTargets = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($targetFormat, $allowedTargets)) {
            throw new ValidationException("Unsupported target format: {$targetFormat}");
        }
        
        return [
            'target_format' => $targetFormat
        ];
    }
}