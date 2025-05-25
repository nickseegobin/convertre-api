<?php

namespace Convertre\Utils;

/**
 * FileHandler - Secure file operations
 * 
 * Handles secure file operations including upload, move, delete
 * with path sanitization and validation
 */
class FileHandler
{
    private static string $uploadPath;
    private static string $convertedPath;
    private static bool $initialized = false;
    
    /**
     * Initialize the file handler with storage paths
     */
    public static function init(string $uploadPath, string $convertedPath): void
    {
        self::$uploadPath = rtrim($uploadPath, '/');
        self::$convertedPath = rtrim($convertedPath, '/');
        
        // Create directories if they don't exist
        self::createDirectory(self::$uploadPath);
        self::createDirectory(self::$convertedPath);
        
        self::$initialized = true;
    }
    
    /**
     * Create directory if it doesn't exist
     */
    private static function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$path}");
            }
        }
    }
    
    /**
     * Sanitize filename to prevent path traversal and other security issues
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent empty filename
        if (empty($filename) || $filename === '.') {
            $filename = 'unnamed_file';
        }
        
        // Prevent files starting with dot (hidden files)
        if (strpos($filename, '.') === 0) {
            $filename = 'file_' . $filename;
        }
        
        return $filename;
    }
    
    /**
     * Generate unique filename to prevent conflicts
     */
    public static function generateUniqueFilename(string $originalFilename, ?string $extension = null): string
    {
        $sanitized = self::sanitizeFilename($originalFilename);
        $pathInfo = pathinfo($sanitized);
        
        $baseName = $pathInfo['filename'];
        $ext = $extension ?: ($pathInfo['extension'] ?? '');
        
        // Add timestamp and random component
        $timestamp = time();
        $random = substr(md5(uniqid(rand(), true)), 0, 8);
        
        return "{$baseName}_{$timestamp}_{$random}" . ($ext ? ".{$ext}" : '');
    }
    
    /**
     * Handle file upload from $_FILES
     */
    public static function handleUpload(array $fileData, ?string $allowedMimes = null): array
    {
        if (!self::$initialized) {
            throw new \RuntimeException('FileHandler not initialized. Call FileHandler::init() first.');
        }
        
        // Validate file upload
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
        
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload error: ' . self::getUploadErrorMessage($fileData['error']));
        }
        
        // Validate MIME type if specified
        if ($allowedMimes) {
            $mimeType = mime_content_type($fileData['tmp_name']);
            $allowedTypes = explode(',', $allowedMimes);
            $allowedTypes = array_map('trim', $allowedTypes);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new \InvalidArgumentException("Unsupported file type: {$mimeType}");
            }
        }
        
        // Generate unique filename
        $originalName = $fileData['name'];
        $uniqueName = self::generateUniqueFilename($originalName);
        $uploadPath = self::$uploadPath . '/' . $uniqueName;
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }
        
        Logger::fileOperation('upload', $uniqueName, true);
        
        return [
            'original_name' => $originalName,
            'stored_name' => $uniqueName,
            'path' => $uploadPath,
            'size' => filesize($uploadPath),
            'mime_type' => mime_content_type($uploadPath)
        ];
    }
    
    /**
     * Move file from upload directory to converted directory
     */
    public static function moveToConverted(string $filename): string
    {
        if (!self::$initialized) {
            throw new \RuntimeException('FileHandler not initialized.');
        }
        
        $sourcePath = self::$uploadPath . '/' . $filename;
        $targetPath = self::$convertedPath . '/' . $filename;
        
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file not found: {$filename}");
        }
        
        if (!rename($sourcePath, $targetPath)) {
            throw new \RuntimeException("Failed to move file to converted directory: {$filename}");
        }
        
        Logger::fileOperation('move_to_converted', $filename, true);
        
        return $targetPath;
    }
    
    /**
     * Copy file to converted directory
     */
    public static function copyToConverted(string $sourcePath, string $filename): string
    {
        if (!self::$initialized) {
            throw new \RuntimeException('FileHandler not initialized.');
        }
        
        $targetPath = self::$convertedPath . '/' . $filename;
        
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Source file not found: {$sourcePath}");
        }
        
        if (!copy($sourcePath, $targetPath)) {
            throw new \RuntimeException("Failed to copy file to converted directory: {$filename}");
        }
        
        Logger::fileOperation('copy_to_converted', $filename, true);
        
        return $targetPath;
    }
    
    /**
     * Delete file safely
     */
    public static function deleteFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return true; // Already deleted
        }
        
        // Ensure file is within our allowed directories
        $realPath = realpath($filePath);
        $uploadRealPath = realpath(self::$uploadPath);
        $convertedRealPath = realpath(self::$convertedPath);
        
        if (!$realPath || 
            (strpos($realPath, $uploadRealPath) !== 0 && strpos($realPath, $convertedRealPath) !== 0)) {
            Logger::error('Attempted to delete file outside allowed directories', ['path' => $filePath]);
            return false;
        }
        
        $success = unlink($filePath);
        Logger::fileOperation('delete', basename($filePath), $success);
        
        return $success;
    }
    
    /**
     * Clean up old files (older than specified hours)
     */
    public static function cleanupOldFiles(int $hoursToKeep = 3): int
    {
        if (!self::$initialized) {
            return 0;
        }
        
        $deletedCount = 0;
        $cutoffTime = time() - ($hoursToKeep * 3600);
        
        // Clean upload directory
        $deletedCount += self::cleanupDirectory(self::$uploadPath, $cutoffTime);
        
        // Clean converted directory
        $deletedCount += self::cleanupDirectory(self::$convertedPath, $cutoffTime);
        
        if ($deletedCount > 0) {
            Logger::info("File cleanup completed", [
                'deleted_files' => $deletedCount,
                'hours_kept' => $hoursToKeep
            ]);
        }
        
        return $deletedCount;
    }
    
    /**
     * Clean up files in a specific directory
     */
    private static function cleanupDirectory(string $directory, int $cutoffTime): int
    {
        $deletedCount = 0;
        
        if (!is_dir($directory)) {
            return 0;
        }
        
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            
            if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
                if (unlink($filePath)) {
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Get file size in human readable format
     */
    public static function getHumanFileSize(int $bytes): string
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
     * Get upload error message
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File size exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Get upload path
     */
    public static function getUploadPath(): string
    {
        return self::$uploadPath;
    }
    
    /**
     * Get converted path
     */
    public static function getConvertedPath(): string
    {
        return self::$convertedPath;
    }
}