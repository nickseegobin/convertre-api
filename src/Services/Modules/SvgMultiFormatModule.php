<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * SvgMultiFormatModule - SVG to JPG, PNG, and PDF conversion using ImageMagick
 * 
 * This multi-tool module handles three different output formats from SVG:
 * - SVG to JPG: Rasterized conversion with background color handling
 * - SVG to PNG: Rasterized conversion preserving transparency
 * - SVG to PDF: Vector-to-vector conversion maintaining scalability
 */
class SvgMultiFormatModule extends AbstractConversionModule
{
    private array $supportedOutputFormats = ['jpg', 'png', 'pdf'];
    
    public function __construct(string $toFormat = 'png')
    {
        // Validate the target format
        if (!in_array(strtolower($toFormat), $this->supportedOutputFormats)) {
            throw new \InvalidArgumentException(
                "Unsupported output format: {$toFormat}. Supported formats: " . 
                implode(', ', $this->supportedOutputFormats)
            );
        }
        
        parent::__construct('svg', strtolower($toFormat), 'imagemagick');
    }
    
    /**
     * Check if ImageMagick is available with SVG support
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
        
        // Check SVG input support (requires librsvg or similar)
        if (!$this->checkSvgInputSupport($convertPath)) {
            return $availability[$cacheKey] = false;
        }
        
        // Check format-specific output support
        if (!$this->checkFormatSupport($convertPath)) {
            return $availability[$cacheKey] = false;
        }
        
        Logger::debug("ImageMagick available for SVG to {$this->toFormat} conversion");
        return $availability[$cacheKey] = true;
    }
    
    /**
     * Execute SVG conversion to the specified target format
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
     * Convert SVG to JPG with background color handling
     */
    private function convertToJpg(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $quality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
        $density = ConfigLoader::get('tools.imagemagick.svg_settings.density', 300);
        
        $command = $this->buildJpgCommand($convertPath, $inputFile, $outputFile, $quality, $density);
        
        Logger::debug('ImageMagick SVG to JPG conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'quality' => $quality,
            'density' => $density
        ]);
        
        $result = $this->executeCommand($command, 120); // SVG conversion can take longer
        
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
     * Convert SVG to PNG preserving transparency
     */
    private function convertToPng(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $compression = ConfigLoader::get('tools.imagemagick.quality_settings.png', 9);
        $density = ConfigLoader::get('tools.imagemagick.svg_settings.density', 300);
        
        $command = $this->buildPngCommand($convertPath, $inputFile, $outputFile, $compression, $density);
        
        Logger::debug('ImageMagick SVG to PNG conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'compression' => $compression,
            'density' => $density
        ]);
        
        $result = $this->executeCommand($command, 120); // SVG conversion can take longer
        
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
     * Convert SVG to PDF maintaining vector quality
     */
    private function convertToPdf(string $convertPath, string $inputFile, string $outputFile): bool
    {
        $quality = ConfigLoader::get('tools.imagemagick.quality_settings.pdf', 85);
        
        $command = $this->buildPdfCommand($convertPath, $inputFile, $outputFile, $quality);
        
        Logger::debug('ImageMagick SVG to PDF conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'quality' => $quality
        ]);
        
        $result = $this->executeCommand($command, 120); // SVG conversion can take longer
        
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
    private function buildJpgCommand(string $convertPath, string $input, string $output, int $quality, int $density): string
    {
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // JPG-specific options for SVG rasterization
        $options = [
            '-density ' . $density,      // Set DPI for rasterization
            '-background white',         // Set background for transparency
            '-flatten',                  // Flatten layers (removes transparency)
            '-strip',                    // Remove metadata
            '-colorspace sRGB',          // Consistent color space
            '-quality ' . $quality       // JPG quality (0-100)
        ];
        
        return sprintf(
            '%s -density %d %s %s %s',
            $convertPath,
            $density,
            $input,
            implode(' ', array_slice($options, 1)), // Skip density since it's already specified
            $output
        );
    }
    
    /**
     * Build ImageMagick command for PNG conversion
     */
    private function buildPngCommand(string $convertPath, string $input, string $output, int $compression, int $density): string
    {
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // PNG-specific options for SVG rasterization (preserve transparency)
        $options = [
            '-density ' . $density,      // Set DPI for rasterization
            '-strip',                    // Remove metadata
            '-colorspace sRGB',          // Consistent color space  
            '-compress Zip',             // PNG compression method
            '-quality ' . $compression   // PNG compression level (0-9)
        ];
        
        return sprintf(
            '%s -density %d %s %s %s',
            $convertPath,
            $density,
            $input,
            implode(' ', array_slice($options, 1)), // Skip density since it's already specified
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
        
        // PDF-specific options for SVG conversion (try to maintain vector quality)
        $options = [
            '-strip',                    // Remove metadata
            '-colorspace sRGB',          // Consistent color space
            '-quality ' . $quality,      // Image quality in PDF
            '-page A4',                  // Set page size
            '-gravity center'            // Center image on page
        ];
        
        return sprintf(
            '%s %s %s %s',
            $convertPath,
            $input,
            implode(' ', $options),
            $output
        );
    }
    
    /**
     * Check if ImageMagick can read SVG files (requires librsvg or similar)
     */
    private function checkSvgInputSupport(string $convertPath): bool
    {
        $result = $this->executeCommand($convertPath . ' -list format | grep -i svg', 10);
        
        if (!$result['success'] || empty(trim($result['output']))) {
            Logger::warning('ImageMagick SVG input support not detected', [
                'suggestion' => 'Install librsvg2-dev and recompile ImageMagick for SVG support'
            ]);
            return false;
        }
        
        // Check if SVG format supports reading (should show 'r' in format string)
        $formatOutput = $result['output'];
        if (strpos($formatOutput, 'r') === false && strpos($formatOutput, 'rw') === false) {
            Logger::warning('ImageMagick SVG read support not available');
            return false;
        }
        
        Logger::debug('SVG input support confirmed');
        return true;
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
        return strtolower($fromFormat) === 'svg' && 
               in_array(strtolower($toFormat), $this->supportedOutputFormats);
    }
}