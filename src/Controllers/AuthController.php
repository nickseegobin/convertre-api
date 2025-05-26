<?php

namespace Convertre\Controllers;

use Convertre\Services\AuthenticationService;
use Convertre\Utils\ResponseFormatter;

/**
 * AuthController - Simple API endpoints for key management
 * Core functionality only - gets the job done
 */
class AuthController
{
    public static function generateKey(): void
    {
        $userId = $_POST['user_id'] ?? 'user_' . uniqid();
        $name = $_POST['name'] ?? 'API Key';
        
        $keyData = AuthenticationService::generateApiKey($userId, $name);
        
        ResponseFormatter::sendJson(
            ResponseFormatter::success([
                'api_key' => $keyData['key'],
                'user_id' => $keyData['user_id'],
                'name' => $keyData['name'],
                'created_at' => $keyData['created_at']
            ])
        );
    }
    
    public static function validateKey(): void
    {
        $apiKey = $_POST['api_key'] ?? null;
        
        if (!$apiKey) {
            ResponseFormatter::sendJson(
                ResponseFormatter::invalidRequest('API key required')
            );
        }
        
        $keyData = AuthenticationService::validateApiKey($apiKey);
        
        if ($keyData) {
            ResponseFormatter::sendJson(
                ResponseFormatter::success([
                    'valid' => true,
                    'user_id' => $keyData['user_id'],
                    'usage_count' => $keyData['usage_count']
                ])
            );
        } else {
            ResponseFormatter::sendJson(
                ResponseFormatter::unauthorized('Invalid API key')
            );
        }
    }
}