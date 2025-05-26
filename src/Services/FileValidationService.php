<?php

namespace Convertre\Services;

use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ValidationException;

/**
 * FileValidationService - Core file validation
 * Simple, functional validation - gets the job done
 */
class FileValidationService
{
    private static bool $initialized = false;
    
    public static function init(): void
    {
        self::$initialized = true;
        Logger::debug('FileValidationService initialized');
    }
    
    /**
     * Validate single uploaded file - core functionality only
     */
    public static function validateFile(array $fileData): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        // Basic upload check
        if (!isset($fileData['tmp_name']) || !is_uploaded_file($fileData['tmp_name'])) {
            throw new ValidationException('Invalid file upload', 'file');
        }
        
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException('File upload failed: ' . self::getUploadError($fileData['error']), 'file');
        }
        
        // File size check
        $maxSize = ConfigLoader::get('limits.file_size.max_upload_size', 50 * 1024 * 1024);
        if ($fileData['size'] > $maxSize) {
            throw new ValidationException('File too large. Max size: ' . self::formatBytes($maxSize), 'file_size');
        }
        
        if ($fileData['size'] < 1024) {
            throw new ValidationException('File too small. Min size: 1KB', 'file_size');
        }
        
        // MIME type and extension check
        $filePath = $fileData['tmp_name'];
        $mimeType = mime_content_type($filePath);
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        
        if (!self::isFormatSupported($extension, $mimeType)) {
            throw new ValidationException("Unsupported format: {$extension}", 'format');
        }
        
        // Security check - block executables
        $dangerousTypes = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'php', 'js', 'html'];
        if (in_array($extension, $dangerousTypes)) {
            throw new ValidationException('Executable files not allowed', 'security');
        }
        
        Logger::debug('File validation passed', [
            'filename' => $fileData['name'],
            'size' => $fileData['size'],
            'mime' => $mimeType
        ]);
        
        return [
            'valid' => true,
            'filename' => $fileData['name'],
            'size' => $fileData['size'],
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Validate batch of files (max 10)
     */
    public static function validateBatch(array $files): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $fileCount = count($files);
        
        // Check batch limit
        if ($fileCount > 10) {
            throw new ValidationException('Too many files. Maximum 10 files per batch', 'batch_limit');
        }
        
        if ($fileCount === 0) {
            throw new ValidationException('No files provided', 'batch_empty');
        }
        
        $validatedFiles = [];
        $totalSize = 0;
        
        foreach ($files as $index => $fileData) {
            try {
                $validated = self::validateFile($fileData);
                $validatedFiles[] = $validated;
                $totalSize += $validated['size'];
            } catch (ValidationException $e) {
                throw new ValidationException(
                    "File #{$index}: " . $e->getMessage(),
                    'batch_file_' . $index
                );
            }
        }
        
        // Check total batch size (100MB limit)
        if ($totalSize > 100 * 1024 * 1024) {
            throw new ValidationException('Batch too large. Maximum 100MB total', 'batch_size');
        }
        
        Logger::info('Batch validation passed', [
            'file_count' => $fileCount,
            'total_size' => $totalSize
        ]);
        
        return [
            'valid' => true,
            'files' => $validatedFiles,
            'count' => $fileCount,
            'total_size' => $totalSize
        ];
    }
    
    /**
     * Check if format is supported
     */
    private static function isFormatSupported(string $extension, string $mimeType): bool
    {
        // MVP formats only
        $supportedFormats = [
            'heic' => ['image/heic', 'image/heif'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            // Add more as needed
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'pdf' => ['application/pdf']
        ];
        
        if (!isset($supportedFormats[$extension])) {
            return false;
        }
        
        return in_array($mimeType, $supportedFormats[$extension]);
    }
    
    /**
     * Get upload error message
     */
    private static function getUploadError(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE: return 'File exceeds upload_max_filesize';
            case UPLOAD_ERR_FORM_SIZE: return 'File exceeds MAX_FILE_SIZE';
            case UPLOAD_ERR_PARTIAL: return 'File partially uploaded';
            case UPLOAD_ERR_NO_FILE: return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR: return 'No temp directory';
            case UPLOAD_ERR_CANT_WRITE: return 'Cannot write to disk';
            default: return 'Upload error';
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < 3) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}