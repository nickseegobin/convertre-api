<?php

/**
 * Authentication Routes
 * 
 * Simple routing for authentication endpoints
 * Include this file from your main router (public/index.php)
 */

require_once __DIR__ . '/../src/Services/AuthenticationService.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

use Convertre\Controllers\AuthController;
use Convertre\Services\AuthenticationService;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;

// Initialize AuthenticationService
AuthenticationService::init(__DIR__ . '/../storage');

/**
 * Route authentication endpoints
 */
function handleAuthRoutes(string $endpoint, string $method): bool
{
    Logger::debug('Handling auth route', ['endpoint' => $endpoint, 'method' => $method]);
    
    switch ($endpoint) {
        case '/generate-key':
            if ($method === 'POST') {
                AuthController::generateKey();
                return true;
            }
            break;
            
        case '/validate-key':
            if ($method === 'POST') {
                AuthController::validateKey();
                return true;
            }
            break;
            
        case '/revoke-key':
            if ($method === 'POST') {
                AuthController::revokeKey();
                return true;
            }
            break;
            
        case '/my-keys':
            if ($method === 'GET') {
                AuthController::listKeys();
                return true;
            }
            break;
            
        case '/key-stats':
            if ($method === 'GET') {
                AuthController::getStats();
                return true;
            }
            break;
    }
    
    return false; // Route not handled
}

/**
 * Send method not allowed response
 */
function sendMethodNotAllowed(array $allowedMethods = []): void
{
    $response = ResponseFormatter::error(
        'Method not allowed',
        'METHOD_NOT_ALLOWED',
        405,
        ['allowed_methods' => $allowedMethods]
    );
    
    ResponseFormatter::sendJson($response);
}