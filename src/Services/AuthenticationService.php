<?php

namespace Convertre\Services;

class AuthenticationService
{
    private static $keys = [];
    private static $initialized = false;
    
    public static function init($storagePath)
    {
        self::$initialized = true;
        self::$keys = [];
    }
    
    public static function generateApiKey($userId, $name = 'API Key')
    {
        $key = 'ck_' . bin2hex(random_bytes(16));
        self::$keys[$key] = [
            'key' => $key,
            'user_id' => $userId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'usage_count' => 0,
            'active' => true
        ];
        return self::$keys[$key];
    }
    
    public static function validateApiKey($apiKey)
    {
        if (isset(self::$keys[$apiKey]) && self::$keys[$apiKey]['active']) {
            self::$keys[$apiKey]['usage_count']++;
            return self::$keys[$apiKey];
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
        $active = 0;
        $totalUsage = 0;
        foreach (self::$keys as $data) {
            if ($data['active']) $active++;
            $totalUsage += $data['usage_count'];
        }
        
        return [
            'total_keys' => count(self::$keys),
            'active_keys' => $active,
            'total_usage' => $totalUsage
        ];
    }
}