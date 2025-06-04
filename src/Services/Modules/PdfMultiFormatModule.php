<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;

/**
 * PdfMultiFormatModule - PDF to JPG, PNG, BMP conversion using ImageMagick
 * 
 * Single-stage conversion process using ImageMagick:
 * 1. ImageMagick: PDF → Target format directly (with page detection)
 * 2. ZIP: Package all pages into a single downloadable file
 * 
 * Benefits of ImageMagick-only approach:
 * - Better PDF page detection
 * - Higher quality rendering
 * - Fewer dependencies
 * - More reliable multi-page handling
 */
class PdfMultiFormatModule extends AbstractConversionModule
{
    private array $supportedOutputFormats = ['jpg', 'png', 'bmp'];
    private int $maxPages = 50; // Safety limit to prevent system overload
    
    public function __construct(string $toFormat = 'jpg')
    {
        // Validate the target format
        if (!in_array(strtolower($toFormat), $this->supportedOutputFormats)) {
            throw new \InvalidArgumentException(
                "Unsupported output format: {$toFormat}. Supported formats: " . 
                implode(', ', $this->supportedOutputFormats)
            );
        }
        
        parent::__construct('pdf', strtolower($toFormat), 'imagemagick');
    }
    
    /**
     * Check if ImageMagick is available with PDF support
     */
    protected function isToolAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        // Check ImageMagick availability
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        $magickResult = $this->executeCommand($convertPath . ' -version', 5);
        
        if (!$magickResult['success']) {
            Logger::warning('ImageMagick not available', ['path' => $convertPath]);
            return $available = false;
        }
        
        // Check PDF support
        $formatResult = $this->executeCommand($convertPath . ' -list format', 10);
        if (!$formatResult['success'] || strpos(strtolower($formatResult['output']), 'pdf') === false) {
            Logger::warning('ImageMagick PDF support not available');
            return $available = false;
        }
        
        Logger::debug('ImageMagick with PDF support available');
        return $available = true;
    }
    
    /**
     * Execute PDF conversion using ImageMagick
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        try {
            // Step 1: Convert PDF to target format using ImageMagick
            $convertedFiles = $this->convertPdfWithImageMagick($inputFile, $outputFile);
            
            if (empty($convertedFiles)) {
                Logger::error('ImageMagick failed to convert PDF');
                return false;
            }
            
            Logger::info('ImageMagick PDF conversion successful', [
                'pages_converted' => count($convertedFiles)
            ]);
            
            // Step 2: Handle single vs multi-page results
            $success = $this->handleConversionResults($convertedFiles, $outputFile);
            
            // Step 3: Cleanup temporary files
            $this->cleanupTempFiles($convertedFiles);
            
            return $success;
            
        } catch (\Exception $e) {
            Logger::error('PDF conversion failed', [
                'error' => $e->getMessage(),
                'input' => basename($inputFile),
                'output_format' => $this->toFormat
            ]);
            return false;
        }
    }
    
    /**
     * Convert PDF to target format using ImageMagick
     */
    private function convertPdfWithImageMagick(string $inputFile, string $outputFile): array
    {
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        
        // Create temporary output directory
        $tempDir = sys_get_temp_dir() . '/convertre_pdf_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        $baseName = pathinfo($outputFile, PATHINFO_FILENAME);
        $outputPattern = $tempDir . '/' . $baseName . '-page-%03d.' . $this->toFormat;
        
        // Build ImageMagick command for PDF conversion
        $command = $this->buildImageMagickCommand($convertPath, $inputFile, $outputPattern);
        
        Logger::debug('ImageMagick PDF conversion', [
            'input' => basename($inputFile),
            'temp_dir' => $tempDir,
            'output_pattern' => basename($outputPattern),
            'command' => $command
        ]);
        
        $result = $this->executeCommand($command, 300);
        
        if (!$result['success']) {
            Logger::error('ImageMagick PDF conversion failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return [];
        }
        
        // Find generated files
        $convertedFiles = glob($tempDir . '/*.' . $this->toFormat);
        sort($convertedFiles);
        
        if (empty($convertedFiles)) {
            Logger::error('No files generated by ImageMagick', [
                'temp_dir' => $tempDir,
                'dir_contents' => scandir($tempDir)
            ]);
            return [];
        }
        
        // Check page limit
        if (count($convertedFiles) > $this->maxPages) {
            Logger::error('PDF has too many pages', [
                'pages' => count($convertedFiles),
                'max_allowed' => $this->maxPages
            ]);
            return [];
        }
        
        Logger::debug('ImageMagick generated files', [
            'count' => count($convertedFiles),
            'files' => array_map('basename', $convertedFiles)
        ]);
        
        return $convertedFiles;
    }
    
   /**
     * Build ImageMagick command for PDF conversion - FIXED
     */
    private function buildImageMagickCommand(string $convertPath, string $inputFile, string $outputPattern): string
    {
        $options = $this->getImageMagickOptions();
        
        // IMPORTANT: Input file comes FIRST, then options, then output
        // This ensures proper multi-page handling
        return sprintf(
            '%s -density 300 %s %s %s',
            $convertPath,
            escapeshellarg($inputFile),
            implode(' ', $options),
            escapeshellarg($outputPattern)
        );
    }
    
   /**
     * Get ImageMagick options based on target format - FIXED
     */
    private function getImageMagickOptions(): array
    {
        switch ($this->toFormat) {
            case 'jpg':
                $quality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
                return [
                    '-background white',
                    // CRITICAL FIX: Only use -flatten for final single-page output, not during multi-page processing
                    '-strip',
                    '-colorspace sRGB',
                    '-quality ' . $quality,
                    '-sampling-factor 4:2:0',
                    '-interlace JPEG'
                ];
                
            case 'png':
                $compression = ConfigLoader::get('tools.imagemagick.quality_settings.png', 9);
                return [
                    '-strip',
                    '-colorspace sRGB',
                    '-compress Zip',
                    '-quality ' . $compression,
                    '-colors 256',
                    '-depth 8'
                ];
                
            case 'bmp':
                return [
                    '-background white',
                    '-strip',
                    '-colorspace sRGB',
                    '-compress None'
                ];
                
            default:
                return ['-strip', '-colorspace sRGB'];
        }
    }
    
   /**
     * Handle conversion results - single file or ZIP - ENHANCED VERSION
     */
    private function handleConversionResults(array $convertedFiles, string $outputFile): bool
    {
        Logger::debug('handleConversionResults called', [
            'converted_files_count' => count($convertedFiles),
            'output_file' => $outputFile,
            'converted_files' => array_map('basename', $convertedFiles)
        ]);
        
        if (count($convertedFiles) === 1) {
            // Single page - move to final location
            Logger::debug('Single page detected, moving file', [
                'source' => $convertedFiles[0],
                'destination' => $outputFile,
                'source_exists' => file_exists($convertedFiles[0]),
                'source_size' => file_exists($convertedFiles[0]) ? filesize($convertedFiles[0]) : 'N/A'
            ]);
            
            $success = rename($convertedFiles[0], $outputFile);
            
            if ($success) {
                Logger::info('Single-page PDF conversion completed', [
                    'output_file' => basename($outputFile),
                    'file_size' => filesize($outputFile)
                ]);
                return true;
            } else {
                Logger::error('Failed to move single page file', [
                    'source' => $convertedFiles[0],
                    'destination' => $outputFile,
                    'error' => error_get_last()
                ]);
                return false;
            }
        } else {
            // Multi-page - create ZIP
            Logger::info('Multi-page detected, creating ZIP', [
                'page_count' => count($convertedFiles),
                'original_output_file' => $outputFile
            ]);
            
            // For multi-page, we create a ZIP file but keep the expected output filename
            // The API expects the file at the original path, so we'll create the ZIP there
            $zipOutputFile = $outputFile; // Use the original output path
            
            Logger::debug('ZIP file paths', [
                'zip_output_file' => $zipOutputFile,
                'output_dir' => dirname($zipOutputFile),
                'output_dir_writable' => is_writable(dirname($zipOutputFile))
            ]);
            
            $zipSuccess = $this->createZipFromPages($convertedFiles, $zipOutputFile);
            
            if ($zipSuccess) {
                Logger::info('Multi-page PDF conversion completed - ZIP created', [
                    'total_pages' => count($convertedFiles),
                    'zip_file' => basename($zipOutputFile),
                    'zip_size' => file_exists($zipOutputFile) ? filesize($zipOutputFile) : 'unknown',
                    'zip_path' => $zipOutputFile
                ]);
                return true;
            } else {
                Logger::error('Failed to create ZIP file from converted pages', [
                    'zip_path' => $zipOutputFile,
                    'pages_to_zip' => count($convertedFiles)
                ]);
                return false;
            }
        }
    }
    
    /**
     * Create ZIP file containing all converted pages
     */
    private function createZipFromPages(array $pageFiles, string $zipOutputPath): bool
    {
        if (!class_exists('ZipArchive')) {
            Logger::error('ZipArchive not available - cannot create ZIP file');
            return false;
        }
        
        $zip = new \ZipArchive();
        $result = $zip->open($zipOutputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            Logger::error('Failed to create ZIP file', [
                'zip_path' => $zipOutputPath,
                'error_code' => $result
            ]);
            return false;
        }
        
        $addedFiles = 0;
        
        foreach ($pageFiles as $pageFile) {
            if (file_exists($pageFile)) {
                $filename = basename($pageFile);
                $success = $zip->addFile($pageFile, $filename);
                
                if ($success) {
                    $addedFiles++;
                    Logger::debug('Added page to ZIP', ['page' => $filename]);
                } else {
                    Logger::warning('Failed to add page to ZIP', ['page' => $filename]);
                }
            }
        }
        
        // Add summary info as text file in ZIP
        $summaryInfo = sprintf(
            "PDF Conversion Summary\n" .
            "======================\n" .
            "Original format: PDF\n" .
            "Output format: %s\n" .
            "Total pages: %d\n" .
           /*  "Conversion method: ImageMagick\n" . */
            "Resolution: 300 DPI\n" .
            "Created: %s\n\n" .
            "Files in this archive:\n" .
            "%s\n",
            strtoupper($this->toFormat),
            count($pageFiles),
            date('Y-m-d H:i:s'),
            implode("\n", array_map('basename', $pageFiles))
        );
        
        $zip->addFromString('README.txt', $summaryInfo);
        
        $closeResult = $zip->close();
        
        if ($closeResult && $addedFiles > 0) {
            Logger::info('ZIP file created successfully', [
                'zip_file' => basename($zipOutputPath),
                'pages_included' => $addedFiles,
                'zip_size' => file_exists($zipOutputPath) ? filesize($zipOutputPath) : 'unknown'
            ]);
            return true;
        } else {
            Logger::error('Failed to finalize ZIP file', [
                'close_result' => $closeResult,
                'added_files' => $addedFiles
            ]);
            return false;
        }
    }
    
    /**
     * Cleanup temporary files and directories
     */
    private function cleanupTempFiles(array $tempFiles): void
    {
        $tempDirs = [];
        
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
                Logger::debug('Cleaned up temp file', ['file' => basename($tempFile)]);
                
                // Track directories for cleanup
                $dir = dirname($tempFile);
                if (!in_array($dir, $tempDirs)) {
                    $tempDirs[] = $dir;
                }
            }
        }
        
        // Remove temp directories if empty
        foreach ($tempDirs as $tempDir) {
            if (is_dir($tempDir) && count(scandir($tempDir)) <= 2) { // Only . and ..
                rmdir($tempDir);
                Logger::debug('Cleaned up temp directory', ['dir' => basename($tempDir)]);
            }
        }
    }
    
    /**
     * Get supported output formats for this module
     */
    public function getSupportedOutputFormats(): array
    {
        return $this->supportedOutputFormats;
    }
    
    /**
     * Check if this module can handle the given conversion
     */
    public function canConvert(string $fromFormat, string $toFormat): bool
    {
        return strtolower($fromFormat) === 'pdf' && 
               in_array(strtolower($toFormat), $this->supportedOutputFormats);
    }
}