<?php

namespace Convertre\Controllers;

use Convertre\Services\AuthenticationService;

/**
 * AuthController - Simple API endpoints for key management
 * Core functionality only - gets the job done
 */
class AuthController
{
    public static function generateKey(): void
    {
        $userId = $_POST['user_id'] ?? 'user123';
        $name = $_POST['name'] ?? 'API Key';
        
        $keyData = AuthenticationService::generateApiKey($userId, $name);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'api_key' => $keyData['key'],
            'user_id' => $keyData['user_id'],
            'name' => $keyData['name'],
            'created_at' => $keyData['created_at']
        ]);
    }
    
    public static function validateKey(): void
    {
        $apiKey = $_POST['api_key'] ?? null;
        
        if (!$apiKey) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'API key required']);
            return;
        }
        
        $keyData = AuthenticationService::validateApiKey($apiKey);
        
        header('Content-Type: application/json');
        if ($keyData) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'user_id' => $keyData['user_id'],
                'usage_count' => $keyData['usage_count']
            ]);
        } else {
            echo json_encode(['success' => false, 'valid' => false, 'error' => 'Invalid API key']);
        }
    }
}