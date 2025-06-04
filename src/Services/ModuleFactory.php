<?php

namespace Convertre\Services;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ConversionException;

/**
 * ModuleFactory - Enhanced with comprehensive format detection
 * Handles module registry and provides detailed format information for API endpoints
 */
class ModuleFactory
{
    private static array $modules = [];
    private static bool $initialized = false;
    
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::registerAvailableModules();
        self::$initialized = true;
        Logger::debug('ModuleFactory initialized with ' . count(self::$modules) . ' modules');
    }
    
    /**
     * Get module for specific conversion with proper instantiation
     * NOTE: This method has the constructor mismatch issue - will be fixed in step 2
     */
   

    public static function getModule(string $fromFormat, string $toFormat): AbstractConversionModule
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $key = strtolower($fromFormat) . '_to_' . strtolower($toFormat);
        
        if (!isset(self::$modules[$key])) {
            throw new ConversionException(
                "No module available for {$fromFormat} to {$toFormat} conversion",
                $fromFormat,
                $toFormat
            );
        }
        
        $moduleClass = self::$modules[$key];
        
        if (!class_exists($moduleClass)) {
            throw new ConversionException(
                "Module class not found: {$moduleClass}",
                $fromFormat,
                $toFormat
            );
        }
        
        Logger::debug("Creating module", ['class' => $moduleClass, 'conversion' => $key]);
        
        // Handle ALL multi-format modules (they need target format parameter)
        $multiFormatModules = [
            'Convertre\\Services\\Modules\\HeicMultiFormatModule',
            'Convertre\\Services\\Modules\\JpgMultiFormatModule',
            'Convertre\\Services\\Modules\\PngMultiFormatModule',
            'Convertre\\Services\\Modules\\WebpMultiFormatModule',
            'Convertre\\Services\\Modules\\GifMultiFormatModule',
            'Convertre\\Services\\Modules\\BmpMultiFormatModule',
            'Convertre\\Services\\Modules\\TiffMultiFormatModule',
            'Convertre\\Services\\Modules\\SvgMultiFormatModule',
            'Convertre\\Services\\Modules\\PdfMultiFormatModule'
        ];
        
        if (in_array($moduleClass, $multiFormatModules)) {
            return new $moduleClass($toFormat);
        }
        
        // Standard modules (document converters with no constructor parameters)
        return new $moduleClass();
    }
    
    /**
     * Register available modules
     */
    private static function registerAvailableModules(): void
    {
        self::$modules = [
            // Document Modules - LibreOffice based
            'docx_to_pdf' => 'Convertre\\Services\\Modules\\DocxToPdfModule',
            'doc_to_pdf' => 'Convertre\\Services\\Modules\\DocToPdfModule',
            'odt_to_pdf' => 'Convertre\\Services\\Modules\\OdtToPdfModule',
            'xlsx_to_pdf' => 'Convertre\\Services\\Modules\\XlsxToPdfModule',
            'pptx_to_pdf' => 'Convertre\\Services\\Modules\\PptxToPdfModule',
            //'epub_to_pdf' => 'Convertre\\Services\\Modules\\EpubToPdfModule', TODO: EPUB NEEDS FIX
            'rtf_to_pdf' => 'Convertre\\Services\\Modules\\RtfToPdfModule',
            'txt_to_pdf' => 'Convertre\\Services\\Modules\\TxtToPdfModule',
            
            // Image Modules - ImageMagick based
            // Multi-Format HEIC Module
            'heic_to_jpg' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',
            'heic_to_png' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',
            'heic_to_pdf' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',

            // Multi-format JPG module (same class, different target formats)
            'jpg_to_png' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule',
            'jpg_to_webp' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule', 
            'jpg_to_pdf' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule',

            // PNG MultiFormatModule
            'png_to_jpg' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',
            'png_to_webp' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',
            'png_to_pdf' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',

            // WebP MultiFormatModule
            'webp_to_jpg' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',
            'webp_to_png' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',
            'webp_to_pdf' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',

            // GIF MultiFormatModule
            'gif_to_jpg' => 'Convertre\\Services\\Modules\\GifMultiFormatModule',
            'gif_to_png' => 'Convertre\\Services\\Modules\\GifMultiFormatModule',
            'gif_to_pdf' => 'Convertre\\Services\\Modules\\GifMultiFormatModule',

            // BMP MultiFormatModule
            'bmp_to_jpg' => 'Convertre\\Services\\Modules\\BmpMultiFormatModule',
            'bmp_to_png' => 'Convertre\\Services\\Modules\\BmpMultiFormatModule',
            'bmp_to_pdf' => 'Convertre\\Services\\Modules\\BmpMultiFormatModule',
            
            // SVG MultiFormatModule
            'svg_to_jpg' => 'Convertre\\Services\\Modules\\SvgMultiFormatModule',
            'svg_to_png' => 'Convertre\\Services\\Modules\\SvgMultiFormatModule',
            'svg_to_pdf' => 'Convertre\\Services\\Modules\\SvgMultiFormatModule',

            // TIFF MultiFormatModule
            'tiff_to_jpg' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',
            'tiff_to_png' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',
            'tiff_to_pdf' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',

            // PDF MultiFormatModule
            'pdf_to_png' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',
            'pdf_to_jpg' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',
            'pdf_to_bmp' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',
        ];
    }
    
    /**
     * ===== NEW METHODS FOR FORMATS API ENDPOINT =====
     */
    
    /**
     * Get all supported conversion formats with detailed information
     */
    public static function getSupportedFormats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $formats = [];
        $conversionPairs = [];
        
        foreach (self::$modules as $conversionKey => $moduleClass) {
            // Parse the conversion key (e.g., "heic_to_jpg")
            if (preg_match('/^(.+)_to_(.+)$/', $conversionKey, $matches)) {
                $fromFormat = strtoupper($matches[1]);
                $toFormat = strtoupper($matches[2]);
                
                // Check if module class exists and is available
                $isAvailable = class_exists($moduleClass);
                $toolName = self::getToolNameForModule($moduleClass);
                
                // Group by source format
                if (!isset($formats[$fromFormat])) {
                    $formats[$fromFormat] = [
                        'source_format' => $fromFormat,
                        'target_formats' => [],
                        'available' => $isAvailable,
                        'tool' => $toolName,
                        'category' => self::getCategoryForFormat($fromFormat)
                    ];
                }
                
                // Add target format with detailed info
                $formats[$fromFormat]['target_formats'][] = [
                    'format' => $toFormat,
                    'available' => $isAvailable,
                    'conversion_key' => $conversionKey,
                    'module_class' => basename($moduleClass)
                ];
                
                // Track conversion pairs for simple listing
                if ($isAvailable) {
                    $conversionPairs[] = "{$fromFormat} → {$toFormat}";
                }
            }
        }
        
        // Sort formats alphabetically
        ksort($formats);
        foreach ($formats as &$format) {
            usort($format['target_formats'], function($a, $b) {
                return strcmp($a['format'], $b['format']);
            });
        }
        
        return [
            'formats_by_source' => array_values($formats),
            'simple_conversions' => $conversionPairs,
            'total_conversions' => count($conversionPairs)
        ];
    }
    
    /**
     * Get supported formats in a simple from->to array format
     */
    public static function getSupportedFormatsSimple(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $conversions = [];
        
        foreach (self::$modules as $conversionKey => $moduleClass) {
            if (preg_match('/^(.+)_to_(.+)$/', $conversionKey, $matches)) {
                $fromFormat = strtoupper($matches[1]);
                $toFormat = strtoupper($matches[2]);
                
                // Only include if module class exists
                if (class_exists($moduleClass)) {
                    $conversions[] = "{$fromFormat} → {$toFormat}";
                }
            }
        }
        
        sort($conversions);
        return $conversions;
    }
    
    /**
     * Get formats grouped by category (Images, Documents)
     */
    public static function getFormatsByCategory(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $categories = [
            'Images' => [],
            'Documents' => []
        ];
        
        foreach (self::$modules as $conversionKey => $moduleClass) {
            if (preg_match('/^(.+)_to_(.+)$/', $conversionKey, $matches)) {
                $fromFormat = strtoupper($matches[1]);
                $toFormat = strtoupper($matches[2]);
                
                if (!class_exists($moduleClass)) {
                    continue;
                }
                
                $category = self::getCategoryForFormat($fromFormat);
                $conversion = "{$fromFormat} → {$toFormat}";
                
                if (!in_array($conversion, $categories[$category])) {
                    $categories[$category][] = $conversion;
                }
            }
        }
        
        // Sort each category
        foreach ($categories as &$conversions) {
            sort($conversions);
        }
        
        return $categories;
    }
    
    /**
     * Check if a specific conversion is supported
     */
    public static function isConversionSupported(string $fromFormat, string $toFormat): bool
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $key = strtolower($fromFormat) . '_to_' . strtolower($toFormat);
        return isset(self::$modules[$key]) && class_exists(self::$modules[$key]);
    }
    
    /**
     * Get all source formats that can be converted
     */
    public static function getSupportedSourceFormats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $sourceFormats = [];
        
        foreach (array_keys(self::$modules) as $conversionKey) {
            if (preg_match('/^(.+)_to_.+$/', $conversionKey, $matches)) {
                $sourceFormat = strtoupper($matches[1]);
                if (!in_array($sourceFormat, $sourceFormats)) {
                    $sourceFormats[] = $sourceFormat;
                }
            }
        }
        
        sort($sourceFormats);
        return $sourceFormats;
    }
    
    /**
     * Get all target formats that files can be converted to
     */
    public static function getSupportedTargetFormats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $targetFormats = [];
        
        foreach (array_keys(self::$modules) as $conversionKey) {
            if (preg_match('/^.+_to_(.+)$/', $conversionKey, $matches)) {
                $targetFormat = strtoupper($matches[1]);
                if (!in_array($targetFormat, $targetFormats)) {
                    $targetFormats[] = $targetFormat;
                }
            }
        }
        
        sort($targetFormats);
        return $targetFormats;
    }
    
    /**
     * Get detailed statistics about available modules
     */
    public static function getDetailedStats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $stats = [
            'total_modules' => count(self::$modules),
            'available_modules' => 0,
            'unavailable_modules' => 0,
            'by_tool' => [
                'ImageMagick' => 0,
                'LibreOffice' => 0,
                'Unknown' => 0
            ],
            'by_category' => [
                'Images' => 0,
                'Documents' => 0,
                'Other' => 0
            ]
        ];
        
        foreach (self::$modules as $conversionKey => $moduleClass) {
            if (class_exists($moduleClass)) {
                $stats['available_modules']++;
            } else {
                $stats['unavailable_modules']++;
            }
            
            // Count by tool
            $tool = self::getToolNameForModule($moduleClass);
            if (isset($stats['by_tool'][$tool])) {
                $stats['by_tool'][$tool]++;
            } else {
                $stats['by_tool']['Unknown']++;
            }
            
            // Count by category
            if (preg_match('/^(.+)_to_.+$/', $conversionKey, $matches)) {
                $fromFormat = strtoupper($matches[1]);
                $category = self::getCategoryForFormat($fromFormat);
                $stats['by_category'][$category]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * ===== HELPER METHODS =====
     */
    
    /**
     * Helper: Get tool name for a module class
     */
    private static function getToolNameForModule(string $moduleClass): string
    {
        // Document conversion modules use LibreOffice
        if (strpos($moduleClass, 'DocxToPdf') !== false || 
            strpos($moduleClass, 'DocToPdf') !== false || 
            strpos($moduleClass, 'OdtToPdf') !== false || 
            strpos($moduleClass, 'XlsxToPdf') !== false || 
            strpos($moduleClass, 'PptxToPdf') !== false || 
            strpos($moduleClass, 'EpubToPdf') !== false || 
            strpos($moduleClass, 'RtfToPdf') !== false || 
            strpos($moduleClass, 'TxtToPdf') !== false) {
            return 'LibreOffice';
        }
        
        // Image conversion modules use ImageMagick
        if (strpos($moduleClass, 'MultiFormat') !== false || 
            strpos($moduleClass, 'Heic') !== false || 
            strpos($moduleClass, 'Jpg') !== false || 
            strpos($moduleClass, 'Png') !== false || 
            strpos($moduleClass, 'Webp') !== false || 
            strpos($moduleClass, 'Gif') !== false || 
            strpos($moduleClass, 'Bmp') !== false || 
            strpos($moduleClass, 'Svg') !== false || 
            strpos($moduleClass, 'Tiff') !== false || 
            strpos($moduleClass, 'Pdf') !== false) {
            return 'ImageMagick';
        }
        
        return 'Unknown';
    }
    
    /**
     * Helper: Get category for a format
     */
    private static function getCategoryForFormat(string $format): string
    {
        $imageFormats = ['HEIC', 'JPG', 'JPEG', 'PNG', 'WEBP', 'GIF', 'BMP', 'TIFF', 'SVG', 'PDF'];
        $documentFormats = ['DOC', 'DOCX', 'ODT', 'XLSX', 'PPTX', 'EPUB', 'RTF', 'TXT'];
        
        if (in_array($format, $imageFormats)) {
            return 'Images';
        } elseif (in_array($format, $documentFormats)) {
            return 'Documents';
        }
        
        return 'Other';
    }
    
    /**
     * ===== EXISTING METHODS (unchanged) =====
     */
    
    /**
     * Manually register a module (for testing or dynamic loading)
     */
    public static function registerModule(string $fromFormat, string $toFormat, string $moduleClass): void
    {
        $key = strtolower($fromFormat) . '_to_' . strtolower($toFormat);
        self::$modules[$key] = $moduleClass;
        
        Logger::debug("Registered module", [
            'conversion' => $key,
            'class' => $moduleClass
        ]);
    }
    
    /**
     * Get factory statistics (existing method - keeping for backward compatibility)
     */
    public static function getStats(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $total = count(self::$modules);
        $available = 0;
        
        foreach (self::$modules as $class) {
            if (class_exists($class)) {
                $available++;
            }
        }
        
        return [
            'total_modules' => $total,
            'available_modules' => $available,
            'unavailable_modules' => $total - $available
        ];
    }
}