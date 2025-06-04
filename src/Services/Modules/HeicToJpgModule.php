<?php

namespace Convertre\Services\Modules;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\ConfigLoader;
use Convertre\Utils\Logger;

/**
 * HeicToJpgModule - HEIC to JPG conversion using ImageMagick
 * Simple, functional ImageMagick integration
 */
class HeicToJpgModule extends AbstractConversionModule
{
    public function __construct()
    {
        parent::__construct('heic', 'jpg', 'imagemagick');
    }
    
    /**
     * Execute HEIC to JPG conversion using ImageMagick
     */
    protected function executeConversion(string $inputFile, string $outputFile): bool
    {
        // Get ImageMagick settings
        $quality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        
        // Build ImageMagick command
        $command = $this->buildConvertCommand($convertPath, $inputFile, $outputFile, $quality);
        
        Logger::debug('ImageMagick HEIC conversion', [
            'input' => basename($inputFile),
            'output' => basename($outputFile),
            'quality' => $quality
        ]);
        
        // Execute conversion
        $result = $this->executeCommand($command, 60);
        
        if (!$result['success']) {
            Logger::error('ImageMagick conversion failed', [
                'command' => $command,
                'exit_code' => $result['exit_code'],
                'error' => $result['error']
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if ImageMagick is available and supports HEIC
     */
    protected function isToolAvailable(): bool
    {
        static $available = null;
        
        if ($available !== null) {
            return $available;
        }
        
        $convertPath = ConfigLoader::get('tools.imagemagick.binary_path', 'convert');
        
        // Test ImageMagick availability
        $result = $this->executeCommand($convertPath . ' -version', 5);
        
        if (!$result['success']) {
            Logger::warning('ImageMagick not available', ['path' => $convertPath]);
            $available = false;
            return false;
        }
        
        // Check for HEIC support (look for libheif)
        if (strpos($result['output'], 'heic') === false && strpos($result['output'], 'heif') === false) {
            Logger::warning('ImageMagick HEIC support not detected');
            $available = false;
            return false;
        }
        
        Logger::debug('ImageMagick with HEIC support available');
        $available = true;
        return true;
    }
    
    /**
     * Build ImageMagick convert command
     */
    private function buildConvertCommand(string $convertPath, string $input, string $output, int $quality): string
    {
        // Escape file paths
        $input = escapeshellarg($input);
        $output = escapeshellarg($output);
        
        // Build command with optimization options
        $options = [
            '-strip',           // Remove metadata
            '-auto-orient',     // Fix orientation
            '-colorspace sRGB', // Consistent color space
            '-quality ' . $quality  // Set JPG quality
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
     * Get optimal quality setting based on file size
     */
    private function getOptimalQuality(string $inputFile): int
    {
        $fileSize = filesize($inputFile);
        $defaultQuality = ConfigLoader::get('tools.imagemagick.quality_settings.jpg', 85);
        
        // Simple optimization: larger files get slightly lower quality
        if ($fileSize > 5 * 1024 * 1024) { // > 5MB
            return max(75, $defaultQuality - 10);
        } elseif ($fileSize > 2 * 1024 * 1024) { // > 2MB
            return max(80, $defaultQuality - 5);
        }
        
        return $defaultQuality;
    }
}