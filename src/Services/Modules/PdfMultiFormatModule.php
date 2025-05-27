<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;

/**
 * PdfMultiFormatModule - PDF to JPG, PNG, BMP conversion using LibreOffice + ImageMagick
 * 
 * Two-stage conversion process:
 * 1. LibreOffice: PDF → PNG (reliable PDF handling, multi-page support)
 * 2. ImageMagick: PNG → Target format (optimization and format conversion)
 * 
 * This hybrid approach gives us the best of both tools:
 * - LibreOffice: Excellent PDF parsing and page extraction
 * - ImageMagick: Superior image optimization and format conversion
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
        
        parent::__construct('pdf', strtolower($toFormat), 'libreoffice');
    }
    
    /**
     * Check if both LibreOffice and ImageMagick are available
     */
    protected function isToolAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        // Check LibreOffice availability
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'soffice');
        $libreResult = $this->executeCommand($librePath . ' --version', 10);
        
        if (!$libreResult['success']) {
            Logger::warning('LibreOffice not available', ['path' => $librePath]);
            return $available = false;
        }
        
        // Check ImageMagick availability
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'magick');
        $magickResult = $this->executeCommand($convertPath . ' -version', 5);
        
        if (!$magickResult['success']) {
            Logger::warning('ImageMagick not available', ['path' => $convertPath]);
            return $available = false;
        }
        
        Logger::debug('Both LibreOffice and ImageMagick available for PDF conversion');
        return $available = true;
    }
    
    /**
     * Execute hybrid PDF conversion - LibreOffice + ImageMagick
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        try {
            // Step 1: Use LibreOffice to convert PDF to PNG(s)
            $tempPngFiles = $this->convertPdfToPngWithLibreOffice($inputFile);
            
            if (empty($tempPngFiles)) {
                Logger::error('LibreOffice failed to convert PDF to PNG');
                return false;
            }
            
            Logger::info('LibreOffice conversion successful', [
                'pages_converted' => count($tempPngFiles)
            ]);
            
            // Step 2: Use ImageMagick to optimize and convert to target format
            $success = $this->optimizeAndConvertWithImageMagick($tempPngFiles, $outputFile);
            
            // Step 3: Cleanup temporary PNG files
            $this->cleanupTempFiles($tempPngFiles);
            
            return $success;
            
        } catch (\Exception $e) {
            Logger::error('Hybrid PDF conversion failed', [
                'error' => $e->getMessage(),
                'input' => basename($inputFile),
                'output_format' => $this->toFormat
            ]);
            return false;
        }
    }
    
    /**
     * Step 1: Convert PDF to PNG using LibreOffice
     */
    private function convertPdfToPngWithLibreOffice(string $inputFile): array
    {
        $librePath = ConfigLoader::get('tools.libreoffice.binary_path', 'soffice');
        $timeout = ConfigLoader::get('tools.libreoffice.timeout', 300);
        
        // Create temporary output directory
        $tempDir = sys_get_temp_dir() . '/convertre_pdf_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // LibreOffice command to convert PDF to PNG
            $command = $this->buildLibreOfficeCommand($librePath, $inputFile, $tempDir);
            
            Logger::debug('LibreOffice PDF to PNG conversion', [
                'input' => basename($inputFile),
                'temp_dir' => $tempDir,
                'command' => $command
            ]);
            
            $result = $this->executeCommand($command, $timeout);
            
            if (!$result['success']) {
                Logger::error('LibreOffice conversion failed', [
                    'command' => $command,
                    'exit_code' => $result['exit_code'],
                    'error' => $result['error']
                ]);
                return [];
            }
            
            // Find generated PNG files
            $pngFiles = $this->findGeneratedPngFiles($tempDir);
            
            if (empty($pngFiles)) {
                Logger::error('No PNG files generated by LibreOffice', [
                    'temp_dir' => $tempDir,
                    'dir_contents' => scandir($tempDir)
                ]);
                return [];
            }
            
            // Check page limit
            if (count($pngFiles) > $this->maxPages) {
                Logger::error('PDF has too many pages', [
                    'pages' => count($pngFiles),
                    'max_allowed' => $this->maxPages
                ]);
                return [];
            }
            
            Logger::debug('LibreOffice generated PNG files', [
                'count' => count($pngFiles),
                'files' => array_map('basename', $pngFiles)
            ]);
            
            return $pngFiles;
            
        } finally {
            // Note: We don't cleanup tempDir here because we're returning the PNG files
            // Cleanup happens in cleanupTempFiles() after ImageMagick processing
        }
    }
    
    /**
     * Step 2: Optimize and convert PNG files using ImageMagick
     */
    private function optimizeAndConvertWithImageMagick(array $pngFiles, string $outputFile): bool
    {
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'magick');
        $successCount = 0;
        $createdFiles = [];
        
        if (count($pngFiles) === 1) {
            // Single page - direct conversion
            $success = $this->convertSinglePngFile($convertPath, $pngFiles[0], $outputFile);
            return $success;
        } else {
            // Multi-page - create separate files
            $outputDir = dirname($outputFile);
            $baseName = pathinfo($outputFile, PATHINFO_FILENAME);
            $extension = pathinfo($outputFile, PATHINFO_EXTENSION);
            
            foreach ($pngFiles as $index => $pngFile) {
                $pageNumber = $index + 1;
                $pageOutputFile = sprintf('%s/%s-page-%03d.%s', $outputDir, $baseName, $pageNumber, $extension);
                
                $success = $this->convertSinglePngFile($convertPath, $pngFile, $pageOutputFile);
                
                if ($success && file_exists($pageOutputFile)) {
                    $createdFiles[] = $pageOutputFile;
                    $successCount++;
                    Logger::debug("Page {$pageNumber} optimized and converted successfully");
                } else {
                    Logger::warning("Failed to convert page {$pageNumber}");
                }
            }
            
            // Create summary file
            $this->createPageSummary($outputFile, $createdFiles, count($pngFiles));
            
            Logger::info('Multi-page ImageMagick optimization completed', [
                'total_pages' => count($pngFiles),
                'successful_pages' => $successCount
            ]);
            
            return $successCount > 0;
        }
    }
    
    /**
     * Convert and optimize single PNG file with ImageMagick
     */
    private function convertSinglePngFile(string $convertPath, string $pngFile, string $outputFile): bool
    {
        $command = $this->buildImageMagickCommand($convertPath, $pngFile, $outputFile);
        
        Logger::debug('ImageMagick optimization', [
            'input' => basename($pngFile),
            'output' => basename($outputFile),
            'format' => $this->toFormat
        ]);
        
        $result = $this->executeCommand($command, 60);
        
        if (!$result['success']) {
            Logger::error('ImageMagick optimization failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Build LibreOffice command for PDF to PNG conversion
     */
    private function buildLibreOfficeCommand(string $librePath, string $inputFile, string $tempDir): string
    {
        $inputFile = escapeshellarg($inputFile);
        $tempDir = escapeshellarg($tempDir);
        
        // LibreOffice options for PDF to PNG conversion
        $options = [
            '--headless',
            '--invisible',
            '--nodefault',
            '--nolockcheck',
            '--nologo',
            '--norestore',
            '--convert-to png',  // Convert to PNG format
            '--outdir ' . $tempDir
        ];
        
        return sprintf(
            '%s %s %s',
            $librePath,
            implode(' ', $options),
            $inputFile
        );
    }
    
    /**
     * Build ImageMagick command for PNG optimization and format conversion
     */
    private function buildImageMagickCommand(string $convertPath, string $pngFile, string $outputFile): string
    {
        $pngFile = escapeshellarg($pngFile);
        $outputFile = escapeshellarg($outputFile);
        
        $options = $this->getImageMagickOptions();
        
        return sprintf(
            '%s %s %s %s',
            $convertPath,
            $pngFile,
            implode(' ', $options),
            $outputFile
        );
    }
    
    /**
     * Get ImageMagick optimization options based on target format
     */
    private function getImageMagickOptions(): array
    {
        switch ($this->toFormat) {
            case 'jpg':
                $quality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
                return [
                    '-background white',     // Handle transparency
                    '-flatten',              // Flatten layers
                    '-strip',                // Remove metadata
                    '-colorspace sRGB',      // Consistent color space
                    '-quality ' . $quality,  // JPEG quality
                    '-sampling-factor 4:2:0', // JPEG subsampling for better compression
                    '-interlace JPEG'        // Progressive JPEG
                ];
                
            case 'png':
                $compression = ConfigLoader::get('tools.imagemagick.quality_settings.png', 9);
                return [
                    '-strip',                // Remove metadata
                    '-colorspace sRGB',      // Consistent color space
                    '-compress Zip',         // PNG compression
                    '-quality ' . $compression, // PNG compression level
                    '-colors 256',           // Optimize color palette if possible
                    '-depth 8'               // 8-bit depth for smaller files
                ];
                
            case 'bmp':
                return [
                    '-background white',     // Handle transparency
                    '-flatten',              // Flatten layers
                    '-strip',                // Remove metadata
                    '-colorspace sRGB',      // Consistent color space
                    '-compress None'         // BMP typically uncompressed
                ];
                
            default:
                return ['-strip', '-colorspace sRGB'];
        }
    }
    
    /**
     * Find PNG files generated by LibreOffice
     */
    private function findGeneratedPngFiles(string $tempDir): array
    {
        $pngFiles = glob($tempDir . '/*.png');
        
        // Sort files to ensure consistent page ordering
        sort($pngFiles);
        
        return $pngFiles;
    }
    
    /**
     * Create summary file for multi-page conversion
     */
    private function createPageSummary(string $originalOutputFile, array $createdFiles, int $totalPages): void
    {
        $summaryFile = str_replace(
            '.' . pathinfo($originalOutputFile, PATHINFO_EXTENSION),
            '-pages-summary.json',
            $originalOutputFile
        );
        
        $summary = [
            'conversion_type' => 'hybrid_pdf_conversion',
            'method' => 'LibreOffice + ImageMagick',
            'total_pages' => $totalPages,
            'successful_conversions' => count($createdFiles),
            'created_files' => array_map('basename', $createdFiles),
            'conversion_time' => date('Y-m-d H:i:s'),
            'output_format' => $this->toFormat,
            'process' => [
                'step_1' => 'LibreOffice PDF → PNG',
                'step_2' => 'ImageMagick PNG → ' . strtoupper($this->toFormat) . ' (optimized)'
            ]
        ];
        
        file_put_contents($summaryFile, json_encode($summary, JSON_PRETTY_PRINT));
        
        Logger::debug('Created hybrid conversion summary', ['summary_file' => basename($summaryFile)]);
    }
    
    /**
     * Cleanup temporary PNG files and directories
     */
    private function cleanupTempFiles(array $pngFiles): void
    {
        foreach ($pngFiles as $pngFile) {
            if (file_exists($pngFile)) {
                unlink($pngFile);
                Logger::debug('Cleaned up temp PNG file', ['file' => basename($pngFile)]);
            }
        }
        
        // Remove temp directory if empty
        if (!empty($pngFiles)) {
            $tempDir = dirname($pngFiles[0]);
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