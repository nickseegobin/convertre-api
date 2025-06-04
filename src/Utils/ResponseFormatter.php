<?php

namespace Convertre\Utils;

/**
 * ResponseFormatter - Standardized JSON responses
 * 
 * Ensures consistent API response format across all endpoints
 * Based on the response specifications in the project document
 */
class ResponseFormatter
{
    /**
     * Create a successful response
     * 
     * @param array $data Response data
     * @param int $httpCode HTTP status code (default: 200)
     * @return array Formatted response array
     */
    public static function success(array $data = [], int $httpCode = 200): array
    {
        $response = [
            'success' => true
        ];
        
        // Merge additional data
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        // Set HTTP response code
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }
        
        return $response;
    }
    
    /**
     * Create a conversion success response
     * 
     * @param string $downloadUrl URL to download converted file
     * @param string $originalFilename Original file name
     * @param string $convertedFilename Converted file name
     * @param string $expiresAt ISO 8601 formatted expiration date
     * @param array $additionalData Any additional data to include
     * @return array Formatted conversion success response
     */
    public static function conversionSuccess(
        string $downloadUrl,
        string $originalFilename,
        string $convertedFilename,
        string $expiresAt,
        array $additionalData = []
    ): array {
        $data = [
            'download_url' => $downloadUrl,
            'original_filename' => $originalFilename,
            'converted_filename' => $convertedFilename,
            'expires_at' => $expiresAt
        ];
        
        // Add any additional data
        if (!empty($additionalData)) {
            $data = array_merge($data, $additionalData);
        }
        
        return self::success($data);
    }
    
    /**
     * Create a batch conversion success response
     * 
     * @param array $files Array of file conversion results
     * @param array $additionalData Any additional data to include
     * @return array Formatted batch conversion success response
     */
    public static function batchSuccess(array $files, array $additionalData = []): array
    {
        $data = [
            'files' => $files,
            'count' => count($files)
        ];
        
        // Add any additional data
        if (!empty($additionalData)) {
            $data = array_merge($data, $additionalData);
        }
        
        return self::success($data);
    }
    
    /**
     * Create an error response
     * 
     * @param string $error Error message
     * @param string $errorCode Error code identifier
     * @param int $httpCode HTTP status code (default: 400)
     * @param array $additionalData Any additional error data
     * @return array Formatted error response
     */
    public static function error(
        string $error,
        string $errorCode = 'GENERIC_ERROR',
        int $httpCode = 400,
        array $additionalData = []
    ): array {
        $response = [
            'success' => false,
            'error' => $error,
            'error_code' => $errorCode
        ];
        
        // Add any additional error data
        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }
        
        // Set HTTP response code
        http_response_code($httpCode);
        
        return $response;
    }
    
    /**
     * Send JSON response and exit
     * 
     * @param array $responseData Response data array
     * @return void (exits script execution)
     */
    public static function sendJson(array $responseData): void
    {
        header('Content-Type: application/json');
        echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Common error responses for quick use
     */
    public static function invalidRequest(string $message = 'Invalid request'): array
    {
        return self::error($message, 'INVALID_REQUEST', 400);
    }
    
    public static function unauthorized(string $message = 'Unauthorized access'): array
    {
        return self::error($message, 'UNAUTHORIZED', 401);
    }
    
    public static function forbidden(string $message = 'Access forbidden'): array
    {
        return self::error($message, 'FORBIDDEN', 403);
    }
    
    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::error($message, 'NOT_FOUND', 404);
    }
    
    public static function unsupportedFormat(string $message = 'File format not supported'): array
    {
        return self::error($message, 'UNSUPPORTED_FORMAT', 400);
    }
    
    public static function fileTooLarge(string $message = 'File size exceeds limit'): array
    {
        return self::error($message, 'FILE_TOO_LARGE', 413);
    }
    
    public static function conversionFailed(string $message = 'Conversion failed'): array
    {
        return self::error($message, 'CONVERSION_FAILED', 500);
    }
    
    public static function rateLimitExceeded(string $message = 'Rate limit exceeded'): array
    {
        return self::error($message, 'RATE_LIMIT_EXCEEDED', 429);
    }
    
    public static function internalError(string $message = 'Internal server error'): array
    {
        return self::error($message, 'INTERNAL_ERROR', 500);
    }
}

