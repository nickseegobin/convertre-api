<?php

/**
 * Simple Authentication Routes
 * Core functionality only - gets the job done
 */

require_once __DIR__ . '/src/Services/AuthenticationService.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Middleware/AuthMiddleware.php';

use Convertre\Controllers\AuthController;
use Convertre\Services\AuthenticationService;

// Initialize AuthenticationService
AuthenticationService::init(__DIR__ . '/storage');

/**
 * Simple route handler for authentication endpoints
 */
function handleAuthRoutes(string $endpoint, string $method): bool
{
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
    }
    
    return false; // Route not handled
}

/**
 * Simple method not allowed response
 */
function sendMethodNotAllowed(): void
{
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}