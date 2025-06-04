<?php

namespace Convertre\Middleware;

use Convertre\Services\RequestValidator;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ValidationException;
use Convertre\Exceptions\AuthenticationException;

/**
 * ValidationMiddleware - Request validation middleware
 * Simple middleware to validate requests before processing
 */
class ValidationMiddleware
{
    /**
     * Validate conversion request and return validation data
     */
    public static function validateConversion(): array
    {
        try {
            // Validate the request
            $validationResult = RequestValidator::validateConversionRequest();
            $conversionParams = RequestValidator::validateConversionParams();
            
            // Combine results
            return array_merge($validationResult, $conversionParams);
            
        } catch (AuthenticationException $e) {
            Logger::warning('Authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::unauthorized($e->getMessage())
            );
            
        } catch (ValidationException $e) {
            Logger::info('Validation failed', [
                'error' => $e->getMessage(),
                'field' => $e->getField()
            ]);
            
            // Determine appropriate HTTP status
            $httpCode = 400;
            if (strpos($e->getMessage(), 'Rate limit') !== false) {
                $httpCode = 429;
            } elseif (strpos($e->getMessage(), 'too large') !== false) {
                $httpCode = 413;
            }
            
            ResponseFormatter::sendJson(
                ResponseFormatter::error($e->getMessage(), 'VALIDATION_ERROR', $httpCode)
            );
            
        } catch (\Exception $e) {
            Logger::error('Validation system error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Validation system error')
            );
        }
    }
    
    /**
     * Simple health check validation (no auth required)
     */
    public static function validateHealthCheck(): bool
    {
        // Just basic request method check
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            ResponseFormatter::sendJson(
                ResponseFormatter::error('Method not allowed', 'METHOD_NOT_ALLOWED', 405)
            );
        }
        
        return true;
    }
}