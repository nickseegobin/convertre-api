<?php

namespace Convertre\Services;

class AuthenticationService
{
    private static $initialized = false;
    private static $keysFilePath = '';
    
    public static function init($storagePath)
    {
        if (!self::$initialized) {
            self::$keysFilePath = $storagePath . '/api_keys.json';
            
            // Create the storage directory if it doesn't exist
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            
            // Create the file if it doesn't exist
            if (!file_exists(self::$keysFilePath)) {
                file_put_contents(self::$keysFilePath, json_encode([]));
            }
            
            self::$initialized = true;
        }
    }
    
    private static function loadKeys()
    {
        if (!file_exists(self::$keysFilePath)) {
            return [];
        }
        
        $content = file_get_contents(self::$keysFilePath);
        $keys = json_decode($content, true);
        
        return $keys ?: [];
    }
    
    private static function saveKeys($keys)
    {
        file_put_contents(self::$keysFilePath, json_encode($keys, JSON_PRETTY_PRINT));
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
        
        // Load existing keys
        $keys = self::loadKeys();
        
        // Add new key
        $keys[$key] = $keyData;
        
        // Save back to file
        self::saveKeys($keys);
        
        return $keyData;
    }
    
    public static function validateApiKey($apiKey)
    {
        self::init('');
        
        $keys = self::loadKeys();
        
        if (isset($keys[$apiKey]) && $keys[$apiKey]['active']) {
            // Increment usage count
            $keys[$apiKey]['usage_count']++;
            
            // Save updated keys
            self::saveKeys($keys);
            
            return $keys[$apiKey];
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
        
        $keys = self::loadKeys();
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
        return self::loadKeys();
    }
}