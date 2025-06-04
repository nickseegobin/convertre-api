<?php

namespace Convertre\Services;

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ValidationException;

/**
 * RateLimitService - Simple rate limiting
 * Core functionality - tracks requests per API key
 */
class RateLimitService
{
    private static array $requests = [];
    private static bool $initialized = false;
    
    public static function init(): void
    {
        self::$initialized = true;
        Logger::debug('RateLimitService initialized');
    }
    
    /**
     * Check if API key can make request
     */
    public static function checkLimit(string $apiKey): bool
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $now = time();
        $window = 60; // 1 minute window
        $limit = ConfigLoader::get('api.rate_limit.requests_per_minute', 60);
        
        // Clean old requests
        self::cleanOldRequests($apiKey, $now, $window);
        
        // Count requests in current window
        $requestCount = count(self::$requests[$apiKey] ?? []);
        
        if ($requestCount >= $limit) {
            Logger::warning('Rate limit exceeded', [
                'api_key' => substr($apiKey, 0, 10) . '...',
                'requests' => $requestCount,
                'limit' => $limit
            ]);
            
            throw new ValidationException(
                'Rate limit exceeded. Try again in 1 minute.',
                'rate_limit'
            );
        }
        
        // Record this request
        self::recordRequest($apiKey, $now);
        
        Logger::debug('Rate limit check passed', [
            'api_key' => substr($apiKey, 0, 10) . '...',
            'requests' => $requestCount + 1,
            'limit' => $limit
        ]);
        
        return true;
    }
    
    /**
     * Record a request for an API key
     */
    private static function recordRequest(string $apiKey, int $timestamp): void
    {
        if (!isset(self::$requests[$apiKey])) {
            self::$requests[$apiKey] = [];
        }
        
        self::$requests[$apiKey][] = $timestamp;
    }
    
    /**
     * Clean old requests outside the window
     */
    private static function cleanOldRequests(string $apiKey, int $now, int $window): void
    {
        if (!isset(self::$requests[$apiKey])) {
            return;
        }
        
        $cutoff = $now - $window;
        self::$requests[$apiKey] = array_filter(
            self::$requests[$apiKey],
            function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            }
        );
    }
    
    /**
     * Get current usage for API key
     */
    public static function getUsage(string $apiKey): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $now = time();
        self::cleanOldRequests($apiKey, $now, 60);
        
        $requestCount = count(self::$requests[$apiKey] ?? []);
        $limit = ConfigLoader::get('api.rate_limit.requests_per_minute', 60);
        
        return [
            'requests_used' => $requestCount,
            'requests_limit' => $limit,
            'requests_remaining' => max(0, $limit - $requestCount),
            'reset_time' => $now + 60
        ];
    }
}