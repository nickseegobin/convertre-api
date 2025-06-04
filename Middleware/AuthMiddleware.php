<?php

namespace Convertre\Middleware;

use Convertre\Services\AuthenticationService;

/**
 * AuthMiddleware - Simple request authentication
 * Core functionality only - gets the job done
 */
class AuthMiddleware
{
    public static function isAuthenticated(): bool
    {
        try {
            AuthenticationService::authenticateRequest();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function optionalAuth(): ?array
    {
        try {
            return AuthenticationService::authenticateRequest();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function requireAuth(): array
    {
        return AuthenticationService::authenticateRequest();
    }
}