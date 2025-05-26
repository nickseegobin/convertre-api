<?php

/**
 * Automatic Cleanup Script
 * Run this via cron job for automated file cleanup
 * 
 * Cron job example (run every hour):
 * 0 * * * * cd /path/to/convertre-api && php cleanup.php
 */

require_once __DIR__ . '/src/Utils/ConfigLoader.php';
require_once __DIR__ . '/src/Utils/Logger.php';
require_once __DIR__ . '/src/Utils/FileHandler.php';
require_once __DIR__ . '/src/Services/CleanupService.php';

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;
use Convertre\Services\CleanupService;

// Initialize systems
try {
    ConfigLoader::init(__DIR__ . '/config');
    Logger::init(__DIR__ . '/storage/logs');
    FileHandler::init(__DIR__ . '/storage/uploads', __DIR__ . '/storage/converted');
    CleanupService::init();
    
    echo "=== Convertre API - Automatic Cleanup ===\n";
    echo "Started: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get stats before cleanup
    echo "1. Getting storage stats...\n";
    $statsBefore = CleanupService::getStorageStats();
    echo "   Files before: " . $statsBefore['total_files'] . "\n";
    echo "   Storage before: " . $statsBefore['total_size_formatted'] . "\n\n";
    
    // Run cleanup
    echo "2. Running cleanup...\n";
    $results = CleanupService::runCleanup();
    
    // Display results
    echo "   Files cleaned: " . $results['total_cleaned'] . "\n";
    echo "   Storage freed: " . formatBytes($results['storage_freed']) . "\n";
    echo "   Uploads cleaned: " . $results['uploads_cleaned'] . "\n";
    echo "   Converted cleaned: " . $results['converted_cleaned'] . "\n";
    
    if (!empty($results['errors'])) {
        echo "   Errors: " . count($results['errors']) . "\n";
        foreach ($results['errors'] as $error) {
            echo "     - $error\n";
        }
    }
    
    // Get stats after cleanup
    echo "\n3. Final stats...\n";
    $statsAfter = CleanupService::getStorageStats();
    echo "   Files after: " . $statsAfter['total_files'] . "\n";
    echo "   Storage after: " . $statsAfter['total_size_formatted'] . "\n";
    
    echo "\n=== Cleanup Complete ===\n";
    echo "Finished: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    Logger::error('Automatic cleanup script failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit(1);
}

/**
 * Format bytes to human readable format
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }
    
    return round($bytes, 2) . ' ' . $units[$unitIndex];
}