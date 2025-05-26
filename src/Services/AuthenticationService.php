<?php

namespace Convertre\Services;

class AuthenticationService
{
    private static $initialized = false;
    
    public static function init($storagePath)
    {
        if (!self::$initialized) {
            // Start session to persist keys
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Initialize keys array in session if not exists
            if (!isset($_SESSION['api_keys'])) {
                $_SESSION['api_keys'] = [];
            }
            
            self::$initialized = true;
        }
    }
    
    public static function generateApiKey($userId, $name = 'API Key')
    {
        self::init('');
        
        $key = 'ck_' . bin2hex(random_bytes(16));
        $keyData = [
            'key' => $key,
            'user_id' => $userId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'usage_count' => 0,
            'active' => true
        ];
        
        // Store in session
        $_SESSION['api_keys'][$key] = $keyData;
        
        return $keyData;
    }
    
    public static function validateApiKey($apiKey)
    {
        self::init('');
        
        if (isset($_SESSION['api_keys'][$apiKey]) && $_SESSION['api_keys'][$apiKey]['active']) {
            $_SESSION['api_keys'][$apiKey]['usage_count']++;
            return $_SESSION['api_keys'][$apiKey];
        }
        return null;
    }
    
    public static function authenticateRequest()
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (!$apiKey) {
            throw new \Exception('API key required');
        }
        
        $keyData = self::validateApiKey($apiKey);
        if (!$keyData) {
            throw new \Exception('Invalid API key');
        }
        
        return $keyData;
    }
    
    public static function getStats()
    {
        self::init('');
        
        $keys = $_SESSION['api_keys'] ?? [];
        $active = 0;
        $totalUsage = 0;
        
        foreach ($keys as $data) {
            if ($data['active']) $active++;
            $totalUsage += $data['usage_count'];
        }
        
        return [
            'total_keys' => count($keys),
            'active_keys' => $active,
            'total_usage' => $totalUsage
        ];
    }
    
    // Debug method - remove in production
    public static function getAllKeys()
    {
        self::init('');
        return $_SESSION['api_keys'] ?? [];
    }
}