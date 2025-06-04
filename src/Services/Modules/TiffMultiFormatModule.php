<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * TiffMultiFormatModule - TIFF to JPG, PNG, and PDF conversion using ImageMagick
 * 
 * This multi-tool module handles three different output formats from TIFF:
 * - TIFF to JPG: Lossy conversion with optimized quality
 * - TIFF to PNG: Lossless conversion maintaining quality
 * - TIFF to PDF: Document conversion for archival purposes
 */
class TiffMultiFormatModule extends AbstractConversionModule
{
    private array $supportedOutputFormats = ['jpg', 'png', 'pdf'];
    
    public function __construct(string $toFormat = 'jpg')
    {
        // Validate the target format
        if (!in_array(strtolower($toFormat), $this->supportedOutputFormats)) {
            throw new \InvalidArgumentException(
                "Unsupported output format: {$toFormat}. Supported formats: " . 
                implode(', ', $this->supportedOutputFormats)
            );
        }
        
        parent::__construct('tiff', strtolower($toFormat), 'imagemagick');
    }
    
    /**
     * Check if ImageMagick is available with required format support
     */
    protected function isToolAvailable(): bool
    {
        static $availability = [];
        
        $cacheKey = $this->toFormat;
        if (isset($availability[$cacheKey])) {
            return $availability[$cacheKey];
        }
        
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'magick');
        
        // Test ImageMagick availability
        $result = $this->executeCommand($convertPath . ' -version', 5);
        
        if (!$result['success']) {
            Logger::warning('ImageMagick not available', [
                'path' => $convertPath,
                'format' => $this->toFormat
            ]);
            return $availability[$cacheKey] = false;
        }
        
        // Check format-specific support
        if (!$this->checkFormatSupport($convertPath)) {
            return $availability[$cacheKey] = false;
        }
        
        Logger::debug("ImageMagick available for TIFF to {$this->toFormat} conversion");
        return $availability[$cacheKey] = true;
    }
    
    /**
     * Execute TIFF conversion to the specified target format
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'magick');
        
        try {
            switch ($this->toFormat) {
                case 'jpg':
                    return $this->convertToJpg($convertPath, $inputFile, $outputFile);
                case 'png':
                    return $this->convertToPng($convertPath, $inputFile, $outputFile);
                case 'pdf':
                    return $this->convertToPdf($convertPath, $inputFile, $outputFile);
                default:
                    Logger::error('Unsupported conversion format', ['format' => $this->toFormat]);
                    return false;
            }
        } catch (\Exception $e) {
            Logger::error('Conversion execution failed', [
                'error' => $e->getMessage(),
                'input' => basename($inputFile),
                'output_format' => $this->toFormat
            ]);
            return false;
        }
    }
    
    /**
     * Convert TIFF to JPG with optimized quality
     */
    private function convertToJpg(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $quality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
        
        $command = $this->buildJpgCommand($convertPath, $inputFile, $outputFile, $quality);
        
        Logger::debug('ImageMagick TIFF to JPG conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'quality' => $quality
        ]);
        
        $result = $this->executeCommand($command, 60);
        
        if (!$result['success']) {
            Logger::error('JPG conversion failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Convert TIFF to PNG maintaining quality
     */
    private function convertToPng(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $compression = ConfigLoader::get('tools.imagemagick.quality_settings.png', 9);
        
        $command = $this->buildPngCommand($convertPath, $inputFile, $outputFile, $compression);
        
        Logger::debug('ImageMagick TIFF to PNG conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'compression' => $compression
        ]);
        
        $result = $this->executeCommand($command, 60);
        
        if (!$result['success']) {
            Logger::error('PNG conversion failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Convert TIFF to PDF for archival purposes
     */
    private function convertToPdf(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $quality = ConfigLoader::get('tools.imagemagick.quality_settings.pdf', 85);
        
        $command = $this->buildPdfCommand($convertPath, $inputFile, $outputFile, $quality);
        
        Logger::debug('ImageMagick TIFF to PDF conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'quality' => $quality
        ]);
        
        $result = $this->executeCommand($command, 120); // PDF conversion may take longer
        
        if (!$result['success']) {
            Logger::error('PDF conversion failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Build ImageMagick command for JPG conversion
     */
    private function buildJpgCommand(string $convertPath, string $input, string $output, int $quality): string
    {
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // JPG-specific options (handle potential transparency in TIFF)
        $options = [
            '-strip',                    // Remove metadata
            '-auto-orient',              // Fix orientation
            '-colorspace sRGB',          // Consistent color space
            '-background white',         // Set background for potential transparency
            '-flatten',                  // Flatten layers if multi-page TIFF
            '-quality ' . $quality       // JPG quality (0-100)
        ];
        
        return sprintf(
            '%s %s %s %s',
            $convertPath,
            $input . '[0]',              // Take first page for multi-page TIFF
            implode(' ', $options),
            $output
        );
    }
    
    /**
     * Build ImageMagick command for PNG conversion
     */
    private function buildPngCommand(string $convertPath, string $input, string $output, int $compression): string
    {
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // PNG-specific optimization options (preserve transparency if present)
        $options = [
            '-strip',                    // Remove metadata
            '-auto-orient',              // Fix orientation
            '-colorspace sRGB',          // Consistent color space  
            '-compress Zip',             // PNG compression method
            '-quality ' . $compression   // PNG compression level (0-9)
        ];
        
        return sprintf(
            '%s %s %s %s',
            $convertPath,
            $input . '[0]',              // Take first page for multi-page TIFF
            implode(' ', $options),
            $output
        );
    }
    
    /**
     * Build ImageMagick command for PDF conversion
     */
    private function buildPdfCommand(string $convertPath, string $input, string $output, int $quality): string
    {
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // PDF-specific options for image conversion (handle multi-page TIFF)
        $options = [
            '-strip',                    // Remove metadata
            '-auto-orient',              // Fix orientation
            '-colorspace sRGB',          // Consistent color space
            '-quality ' . $quality,      // Image quality in PDF
            '-density 300',              // High DPI for crisp images
            '-compress JPEG',            // Use JPEG compression within PDF
            '-page A4',                  // Set page size
            '-gravity center'            // Center image on page
        ];
        
        return sprintf(
            '%s %s %s %s',
            $convertPath,
            $input,                      // Don't specify [0] for PDF - convert all pages
            implode(' ', $options),
            $output
        );
    }
    
    /**
     * Check if ImageMagick supports the target format
     */
    private function checkFormatSupport(string $convertPath): bool
    {
        $result = $this->executeCommand($convertPath . ' -list format', 10);
        
        if (!$result['success']) {
            Logger::warning('Could not check ImageMagick format support');
            return true; // Assume support if we can't check
        }
        
        $output = strtolower($result['output']);
        $formatUpper = strtoupper($this->toFormat);
        
        // Check if the format is listed in supported formats
        $isSupported = strpos($output, strtolower($this->toFormat)) !== false || 
                      strpos($output, $formatUpper) !== false;
        
        if (!$isSupported) {
            Logger::warning("ImageMagick does not support {$formatUpper} format", [
                'format' => $this->toFormat,
                'available_check' => 'format support verification failed'
            ]);
            return false;
        }
        
        return true;
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
        return strtolower($fromFormat) === 'tiff' && 
               in_array(strtolower($toFormat), $this->supportedOutputFormats);
    }
}