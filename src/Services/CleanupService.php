<?php

namespace Convertre\Services;

use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;
use Convertre\Utils\ConfigLoader;

/**
 * CleanupService - Simple automated file cleanup
 * Core functionality only - gets the job done
 */
class CleanupService
{
    private static bool $initialized = false;
    
    public static function init(): void
    {
        self::$initialized = true;
        Logger::debug('CleanupService initialized');
    }
    
    /**
     * Run automatic cleanup - 3 hour retention policy
     */
    public static function runCleanup(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $startTime = microtime(true);
        $retentionHours = ConfigLoader::get('api.download.expiry_hours', 3);
        $cutoffTime = time() - ($retentionHours * 3600);
        
        Logger::info('Starting automatic cleanup', [
            'retention_hours' => $retentionHours,
            'cutoff_time' => date('Y-m-d H:i:s', $cutoffTime)
        ]);
        
        $results = [
            'uploads_cleaned' => 0,
            'converted_cleaned' => 0,
            'total_cleaned' => 0,
            'storage_freed' => 0,
            'errors' => []
        ];
        
        // Clean upload directory
        try {
            $uploadResults = self::cleanDirectory(FileHandler::getUploadPath(), $cutoffTime);
            $results['uploads_cleaned'] = $uploadResults['files_deleted'];
            $results['storage_freed'] += $uploadResults['bytes_freed'];
        } catch (\Exception $e) {
            $results['errors'][] = 'Upload cleanup failed: ' . $e->getMessage();
            Logger::error('Upload cleanup failed', ['error' => $e->getMessage()]);
        }
        
        // Clean converted directory
        try {
            $convertedResults = self::cleanDirectory(FileHandler::getConvertedPath(), $cutoffTime);
            $results['converted_cleaned'] = $convertedResults['files_deleted'];
            $results['storage_freed'] += $convertedResults['bytes_freed'];
        } catch (\Exception $e) {
            $results['errors'][] = 'Converted cleanup failed: ' . $e->getMessage();
            Logger::error('Converted cleanup failed', ['error' => $e->getMessage()]);
        }
        
        $results['total_cleaned'] = $results['uploads_cleaned'] + $results['converted_cleaned'];
        $processingTime = microtime(true) - $startTime;
        
        Logger::info('Cleanup completed', [
            'files_cleaned' => $results['total_cleaned'],
            'storage_freed' => self::formatBytes($results['storage_freed']),
            'processing_time' => round($processingTime, 3) . 's',
            'errors' => count($results['errors'])
        ]);
        
        return $results;
    }
    
    /**
     * Clean files in a specific directory
     */
    private static function cleanDirectory(string $directory, int $cutoffTime): array
    {
        $filesDeleted = 0;
        $bytesFreed = 0;
        
        if (!is_dir($directory)) {
            Logger::warning('Directory not found for cleanup', ['directory' => $directory]);
            return ['files_deleted' => 0, 'bytes_freed' => 0];
        }
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            
            if (is_file($filePath)) {
                $fileTime = filemtime($filePath);
                $fileSize = filesize($filePath);
                
                if ($fileTime < $cutoffTime) {
                    if (unlink($filePath)) {
                        $filesDeleted++;
                        $bytesFreed += $fileSize;
                        
                        Logger::debug('File cleaned up', [
                            'file' => $file,
                            'size' => self::formatBytes($fileSize),
                            'age' => self::getFileAge($fileTime)
                        ]);
                    } else {
                        Logger::warning('Failed to delete file', ['file' => $file]);
                    }
                }
            }
        }
        
        return [
            'files_deleted' => $filesDeleted,
            'bytes_freed' => $bytesFreed
        ];
    }
    
    /**
     * Get current storage usage
     */
    public static function getStorageStats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $stats = [
            'upload_directory' => self::getDirectoryStats(FileHandler::getUploadPath()),
            'converted_directory' => self::getDirectoryStats(FileHandler::getConvertedPath()),
            'total_files' => 0,
            'total_size' => 0
        ];
        
        $stats['total_files'] = $stats['upload_directory']['file_count'] + $stats['converted_directory']['file_count'];
        $stats['total_size'] = $stats['upload_directory']['total_size'] + $stats['converted_directory']['total_size'];
        $stats['total_size_formatted'] = self::formatBytes($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Get statistics for a directory
     */
    private static function getDirectoryStats(string $directory): array
    {
        $stats = [
            'file_count' => 0,
            'total_size' => 0,
            'oldest_file' => null,
            'newest_file' => null
        ];
        
        if (!is_dir($directory)) {
            return $stats;
        }
        
        $files = scandir($directory);
        $oldestTime = PHP_INT_MAX;
        $newestTime = 0;
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            
            if (is_file($filePath)) {
                $stats['file_count']++;
                $stats['total_size'] += filesize($filePath);
                
                $fileTime = filemtime($filePath);
                if ($fileTime < $oldestTime) {
                    $oldestTime = $fileTime;
                    $stats['oldest_file'] = $file;
                }
                if ($fileTime > $newestTime) {
                    $newestTime = $fileTime;
                    $stats['newest_file'] = $file;
                }
            }
        }
        
        $stats['total_size_formatted'] = self::formatBytes($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Manual cleanup - force cleanup regardless of age
     */
    public static function forceCleanup(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        Logger::warning('Force cleanup initiated');
        
        $results = [
            'uploads_cleaned' => 0,
            'converted_cleaned' => 0,
            'total_cleaned' => 0,
            'storage_freed' => 0,
            'errors' => []
        ];
        
        // Clean all files regardless of age
        $cutoffTime = time(); // Current time - will delete everything
        
        try {
            $uploadResults = self::cleanDirectory(FileHandler::getUploadPath(), $cutoffTime);
            $results['uploads_cleaned'] = $uploadResults['files_deleted'];
            $results['storage_freed'] += $uploadResults['bytes_freed'];
        } catch (\Exception $e) {
            $results['errors'][] = 'Force upload cleanup failed: ' . $e->getMessage();
        }
        
        try {
            $convertedResults = self::cleanDirectory(FileHandler::getConvertedPath(), $cutoffTime);
            $results['converted_cleaned'] = $convertedResults['files_deleted'];
            $results['storage_freed'] += $convertedResults['bytes_freed'];
        } catch (\Exception $e) {
            $results['errors'][] = 'Force converted cleanup failed: ' . $e->getMessage();
        }
        
        $results['total_cleaned'] = $results['uploads_cleaned'] + $results['converted_cleaned'];
        
        Logger::warning('Force cleanup completed', [
            'files_cleaned' => $results['total_cleaned'],
            'storage_freed' => self::formatBytes($results['storage_freed'])
        ]);
        
        return $results;
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Get human readable file age
     */
    private static function getFileAge(int $timestamp): string
    {
        $age = time() - $timestamp;
        
        if ($age < 60) {
            return $age . 's';
        } elseif ($age < 3600) {
            return round($age / 60) . 'm';
        } elseif ($age < 86400) {
            return round($age / 3600) . 'h';
        } else {
            return round($age / 86400) . 'd';
        }
    }
}