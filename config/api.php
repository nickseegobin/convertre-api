<?php

/**
 * General API Configuration
 */

return [
    // API Information
    'name' => 'Convertre API',
    'version' => '1.0.0-MVP',
    'description' => 'File conversion API for images and documents',
    
    // Rate Limiting
    'rate_limit' => [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000,
        'burst_limit' => 10
    ],
    
    // File Download Settings
    'download' => [
        'base_url' => 'https://api.convertre.com/download',
        'expiry_hours' => 3,
        'secure_tokens' => true
    ],
    
    // Request Limits
    'limits' => [
        'batch_max_files' => 10,
        'max_concurrent_conversions' => 5
    ],
    
    // Environment
    'environment' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => $_ENV['APP_DEBUG'] ?? true,
    
    // Timezone
    'timezone' => 'UTC'
];