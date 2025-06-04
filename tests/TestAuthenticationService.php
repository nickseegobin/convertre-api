<?php

/**
 * TestAuthenticationService - CLI-safe version for testing
 * Avoids session_start() conflicts in CLI environment
 */

class TestAuthenticationService
{
    private static array $testKeys = [];
    private static bool $initialized = false;
    
    public static function init($storagePath)
    {
        if (!self::$initialized) {
            self::$testKeys = [];
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
        
        // Store in memory for testing
        self::$testKeys[$key] = $keyData;
        
        return $keyData;
    }
    
    public static function validateApiKey($apiKey)
    {
        self::init('');
        
        if (isset(self::$testKeys[$apiKey]) && self::$testKeys[$apiKey]['active']) {
            self::$testKeys[$apiKey]['usage_count']++;
            return self::$testKeys[$apiKey];
        }
        return null;
    }
    
    public static function authenticateRequest()
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? 'test_key_for_cli';
        if (!$apiKey) {
            throw new \Exception('API key required');
        }
        
        // For CLI testing, create a dummy key
        if ($apiKey === 'test_key_for_cli') {
            return self::generateApiKey('test_user', 'Test Key');
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
        
        $keys = self::$testKeys;
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
    
    public static function getAllKeys()
    {
        self::init('');
        return self::$testKeys;
    }
}