<?php

namespace Convertre\Controllers;

use Convertre\Services\CleanupService;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;

/**
 * CleanupController - Simple cleanup management endpoints
 * Core functionality only - gets the job done
 */
class CleanupController
{
    /**
     * GET /cleanup/status - Get storage statistics
     */
    public static function getStatus(): void
    {
        try {
            CleanupService::init();
            $stats = CleanupService::getStorageStats();
            
            ResponseFormatter::sendJson(
                ResponseFormatter::success([
                    'storage_stats' => $stats,
                    'cleanup_policy' => [
                        'retention_hours' => 3,
                        'automatic_cleanup' => 'enabled'
                    ],
                    'last_checked' => gmdate('c')
                ])
            );
            
        } catch (\Exception $e) {
            Logger::error('Cleanup status error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Failed to get cleanup status')
            );
        }
    }
    
    /**
     * POST /cleanup/run - Run manual cleanup
     */
    public static function runCleanup(): void
    {
        try {
            CleanupService::init();
            $results = CleanupService::runCleanup();
            
            Logger::info('Manual cleanup executed via API', $results);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::success([
                    'cleanup_results' => $results,
                    'message' => 'Cleanup completed successfully',
                    'executed_at' => gmdate('c')
                ])
            );
            
        } catch (\Exception $e) {
            Logger::error('Manual cleanup error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Cleanup failed: ' . $e->getMessage())
            );
        }
    }
    
    /**
     * POST /cleanup/force - Force cleanup all files
     * WARNING: This deletes ALL files regardless of age
     */
    public static function forceCleanup(): void
    {
        try {
            // Simple confirmation check
            $confirm = $_POST['confirm'] ?? '';
            if ($confirm !== 'yes') {
                ResponseFormatter::sendJson(
                    ResponseFormatter::invalidRequest('Force cleanup requires confirmation. Send "confirm=yes" parameter.')
                );
                return;
            }
            
            CleanupService::init();
            $results = CleanupService::forceCleanup();
            
            Logger::warning('Force cleanup executed via API', $results);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::success([
                    'cleanup_results' => $results,
                    'message' => 'Force cleanup completed - ALL files removed',
                    'warning' => 'All files have been deleted regardless of age',
                    'executed_at' => gmdate('c')
                ])
            );
            
        } catch (\Exception $e) {
            Logger::error('Force cleanup error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Force cleanup failed: ' . $e->getMessage())
            );
        }
    }
}