<?php

namespace Convertre\Controllers;

use Convertre\Middleware\ValidationMiddleware;
use Convertre\Services\ModuleFactory;
use Convertre\Utils\FileHandler;
use Convertre\Utils\ResponseFormatter;
use Convertre\Utils\Logger;
use Convertre\Utils\ConfigLoader;

/**
 * ConversionController - Main API endpoints for file conversion
 * Simple, functional API endpoints - gets the job done
 */
class ConversionController
{
    /**
     * POST /convert - Single file conversion endpoint
     */
    public static function convert(): void
    {
        try {
            // Validate request (auth + file + params)
            $validation = ValidationMiddleware::validateConversion();
            
            Logger::apiRequest('/convert', 'POST', [
                'user_id' => $validation['auth']['user_id'],
                'target_format' => $validation['target_format'],
                'file_count' => 1
            ]);
            
            // Get file info
            $fileInfo = $validation['file'];
            $targetFormat = $validation['target_format'];
            $sourceFormat = $fileInfo['extension'];
            
            // Get conversion module
            ModuleFactory::init();
            $module = ModuleFactory::getModule($sourceFormat, $targetFormat);
            
            
            // Handle file upload and get paths
            $uploadResult = FileHandler::handleUpload($_FILES['file']);
            $inputPath = $uploadResult['path'];
            
            // Generate unique output filename
            $outputFilename = FileHandler::generateUniqueFilename(
                $uploadResult['original_name'], 
                $targetFormat
            );
            $outputPath = FileHandler::getConvertedPath() . '/' . $outputFilename;
            
            // Perform conversion
            $conversionResult = $module->convert($inputPath, $outputPath);
            
            if (!$conversionResult->isSuccess()) {
                // Cleanup input file
                FileHandler::deleteFile($inputPath);
                
                ResponseFormatter::sendJson(
                    ResponseFormatter::conversionFailed($conversionResult->getErrorMessage())
                );
            }
            
            // Generate download URL
            $downloadUrl = self::generateDownloadUrl($outputFilename);
            $expiresAt = self::getExpirationTime();
            
            // Cleanup input file (keep output for download)
            FileHandler::deleteFile($inputPath);
            
            // Send success response
            ResponseFormatter::sendJson(
                ResponseFormatter::conversionSuccess(
                    $downloadUrl,
                    $uploadResult['original_name'],
                    $outputFilename,
                    $expiresAt,
                    [
                        'processing_time' => round($conversionResult->getProcessingTime(), 3) . 's',
                        'file_size' => filesize($outputPath) . ' bytes',
                        'conversion' => $sourceFormat . ' → ' . $targetFormat
                    ]
                )
            );
            
        } catch (\Exception $e) {
            Logger::error('Conversion endpoint error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Conversion failed: ' . $e->getMessage())
            );
        }
    }
    
    /**
     * POST /convert-batch - Batch file conversion endpoint
     * SIMPLIFIED: Just handle multiple files sent as files[0], files[1], etc.
     */
    public static function convertBatch(): void
    {
        try {
            // Simple batch validation - just auth and target format
            $targetFormat = $_POST['to'] ?? null;
            if (!$targetFormat) {
                ResponseFormatter::sendJson(
                    ResponseFormatter::invalidRequest('Target format required (to parameter)')
                );
                return;
            }
            
            // Simple auth check
            $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
            if (!$apiKey) {
                ResponseFormatter::sendJson(
                    ResponseFormatter::unauthorized('API key required')
                );
                return;
            }
            
            // Get uploaded files - SIMPLIFIED approach
            $uploadedFiles = self::getUploadedFiles();
            
            if (empty($uploadedFiles)) {
                ResponseFormatter::sendJson(
                    ResponseFormatter::invalidRequest('No files uploaded')
                );
                return;
            }
            
            if (count($uploadedFiles) > 10) {
                ResponseFormatter::sendJson(
                    ResponseFormatter::invalidRequest('Maximum 10 files allowed per batch')
                );
                return;
            }
            
            Logger::apiRequest('/convert-batch', 'POST', [
                'target_format' => $targetFormat,
                'file_count' => count($uploadedFiles)
            ]);
            
            $results = [];
            $totalProcessingTime = 0;
            
            ModuleFactory::init();
            
            foreach ($uploadedFiles as $index => $fileData) {
                try {
                    // Basic file validation
                    if ($fileData['error'] !== UPLOAD_ERR_OK) {
                        $results[] = [
                            'success' => false,
                            'original_filename' => $fileData['name'],
                            'error' => 'File upload error: ' . $fileData['error']
                        ];
                        continue;
                    }
                    
                    // Get source format from file extension
                    $sourceFormat = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
                    
                    // Get conversion module
                    $module = ModuleFactory::getModule($sourceFormat, $targetFormat);
                    
                    // Handle file upload
                    $uploadResult = FileHandler::handleUpload($fileData);
                    $inputPath = $uploadResult['path'];
                    
                    // Generate output path
                    $outputFilename = FileHandler::generateUniqueFilename(
                        $uploadResult['original_name'], 
                        $targetFormat
                    );
                    $outputPath = FileHandler::getConvertedPath() . '/' . $outputFilename;
                    
                    // Perform conversion
                    $conversionResult = $module->convert($inputPath, $outputPath);
                    
                    if ($conversionResult->isSuccess()) {
                        $results[] = [
                            'success' => true,
                            'original_filename' => $uploadResult['original_name'],
                            'converted_filename' => $outputFilename,
                            'download_url' => self::generateDownloadUrl($outputFilename),
                            'processing_time' => round($conversionResult->getProcessingTime(), 3) . 's',
                            'file_size' => filesize($outputPath) . ' bytes'
                        ];
                        
                        $totalProcessingTime += $conversionResult->getProcessingTime();
                    } else {
                        $results[] = [
                            'success' => false,
                            'original_filename' => $uploadResult['original_name'],
                            'error' => $conversionResult->getErrorMessage()
                        ];
                    }
                    
                    // Cleanup input file
                    FileHandler::deleteFile($inputPath);
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'success' => false,
                        'original_filename' => $fileData['name'] ?? "file_{$index}",
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Count successes
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $expiresAt = self::getExpirationTime();
            
            // Send batch response
            ResponseFormatter::sendJson(
                ResponseFormatter::batchSuccess($results, [
                    'total_files' => count($uploadedFiles),
                    'successful_conversions' => $successCount,
                    'failed_conversions' => count($uploadedFiles) - $successCount,
                    'total_processing_time' => round($totalProcessingTime, 3) . 's',
                    'conversion_type' => $targetFormat,
                    'expires_at' => $expiresAt
                ])
            );
            
        } catch (\Exception $e) {
            Logger::error('Batch conversion endpoint error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Batch conversion failed: ' . $e->getMessage())
            );
        }
    }

    /**
     * GET /formats - Get supported conversion formats
     * 
     * Query parameters:
     * - view: simple (default), detailed, category
     * 
     * Examples:
     * - GET /formats
     * - GET /formats?view=detailed  
     * - GET /formats?view=category
     */
    public static function getFormats(): void
    {
        try {
            $queryParams = $_GET;
            $view = $queryParams['view'] ?? 'simple'; // simple, detailed, category
            
            Logger::apiRequest('/formats', 'GET', ['view' => $view]);
            
            // Initialize the module factory
            ModuleFactory::init();
            
            switch ($view) {
                case 'detailed':
                    $data = ModuleFactory::getSupportedFormats();
                    $response = ResponseFormatter::success([
                        'view' => 'detailed',
                        'data' => $data,
                        'statistics' => ModuleFactory::getDetailedStats()
                    ]);
                    break;
                    
                case 'category':
                    $categories = ModuleFactory::getFormatsByCategory();
                    $response = ResponseFormatter::success([
                        'view' => 'category',
                        'categories' => $categories,
                        'total_conversions' => array_sum(array_map('count', $categories))
                    ]);
                    break;
                    
                case 'simple':
                default:
                    $conversions = ModuleFactory::getSupportedFormatsSimple();
                    $response = ResponseFormatter::success([
                        'view' => 'simple',
                        'supported_conversions' => $conversions,
                        'total_conversions' => count($conversions),
                        'source_formats' => ModuleFactory::getSupportedSourceFormats(),
                        'target_formats' => ModuleFactory::getSupportedTargetFormats()
                    ]);
                    break;
            }
            
            ResponseFormatter::sendJson($response);
            
        } catch (\Exception $e) {
            Logger::error('Formats endpoint error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Failed to retrieve supported formats: ' . $e->getMessage())
            );
        }
    }
    
    /**
     * ALTERNATIVE: Get uploaded files - handles both array and individual fields
     */
    private static function getUploadedFiles(): array
    {
        $files = [];
        
        // Debug: Log what we received
        Logger::debug('Files received in $_FILES', $_FILES);
        
        // Method 1: Handle multiple files uploaded as files[0], files[1], etc.
        if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $fileCount = count($_FILES['files']['name']);
            Logger::debug('Processing array of files', ['count' => $fileCount]);
            
            for ($i = 0; $i < $fileCount; $i++) {
                // Skip empty file slots
                if (empty($_FILES['files']['name'][$i]) || $_FILES['files']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                $files[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i]
                ];
            }
        }
        // Method 2: Handle individual file fields (file1, file2, file3, etc.)
        else {
            Logger::debug('Looking for individual file fields');
            foreach ($_FILES as $key => $fileData) {
                // Skip if it's not a file field or if it's empty
                if (!is_array($fileData) || empty($fileData['name']) || $fileData['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                // Accept any field that contains 'file' (file, files, file1, file2, etc.)
                if (strpos($key, 'file') !== false) {
                    Logger::debug('Found individual file', ['field' => $key, 'name' => $fileData['name']]);
                    $files[] = $fileData;
                }
            }
        }
        
        Logger::debug('Final files processed for batch', ['file_count' => count($files)]);
        
        return $files;
    }
    
    /**
     * GET /download/{filename} - File download endpoint
     */
    public static function download(string $filename): void
    {
        try {
            // Sanitize filename
            $filename = FileHandler::sanitizeFilename($filename);
            $filePath = FileHandler::getConvertedPath() . '/' . $filename;
            
            // Check if file exists
            if (!file_exists($filePath)) {
                ResponseFormatter::sendJson(
                    ResponseFormatter::notFound('File not found or expired')
                );
                return;
            }
            
            // Check file age (3 hour expiry)
            $fileAge = time() - filemtime($filePath);
            $maxAge = ConfigLoader::get('api.download.expiry_hours', 3) * 3600;
            
            if ($fileAge > $maxAge) {
                // File expired, delete it
                FileHandler::deleteFile($filePath);
                
                ResponseFormatter::sendJson(
                    ResponseFormatter::notFound('File expired and has been removed')
                );
                return;
            }
            
            // Determine content type
            $contentType = self::getContentType($filename);
            
            // Send file
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
            
            readfile($filePath);
            
            Logger::info('File downloaded', [
                'filename' => $filename,
                'size' => filesize($filePath)
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Download endpoint error', [
                'filename' => $filename ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            ResponseFormatter::sendJson(
                ResponseFormatter::internalError('Download failed')
            );
        }
    }
    
    /**
     * Generate download URL for converted file - FIXED for localhost
     */
  /*   private static function generateDownloadUrl(string $filename): string
    {
        // For development, use localhost
        //$baseUrl = 'http://localhost/convertre-api/public/download';
        
        // For production, this would be:
         $baseUrl = ConfigLoader::get('api.download.base_url', 'https://api.convertre.com/download');
        
        return $baseUrl . '/' . $filename;
    } */

    /**
     * Generate download URL for converted file - FIXED for Laragon
     */
    private static function generateDownloadUrl(string $filename): string
    {
        // Build base URL from current request
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // For Laragon setup, we don't need complex path detection
        // Just build the URL directly
        $baseUrl = $protocol . '://' . $host;
        
        // Add /download path
        $downloadUrl = $baseUrl . '/download/' . $filename;
        
        return $downloadUrl;
    }
        
    /**
     * Get file expiration time (ISO 8601 format)
     */
    private static function getExpirationTime(): string
    {
        $hours = ConfigLoader::get('api.download.expiry_hours', 3);
        return gmdate('c', time() + ($hours * 3600));
    }
    
    /**
     * Get content type for file download
     */
    private static function getContentType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'heic' => 'image/heic'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}