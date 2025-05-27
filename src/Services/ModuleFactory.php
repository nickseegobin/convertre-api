<?php

namespace Convertre\Services;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ConversionException;

/**
 * ModuleFactory - FIXED for JpgMultiFormatModule
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
     * FIXED: Get module for specific conversion with proper instantiation
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
        
        // FIXED: Handle JpgMultiFormatModule special case
        if ($moduleClass === 'Convertre\\Services\\Modules\\JpgMultiFormatModule') {
            return new $moduleClass($toFormat); // Pass target format to constructor
        }
        
        // Standard modules (no constructor parameters)
        return new $moduleClass();
    }
    
    /**
     * Register available modules
     */
    private static function registerAvailableModules(): void
    {
        self::$modules = [
            // Standard modules
            // Documents Modules
            'docx_to_pdf' => 'Convertre\\Services\\Modules\\DocxToPdfModule',
            'doc_to_pdf' => 'Convertre\\Services\\Modules\\DocToPdfModule',
            'odt_to_pdf' => 'Convertre\\Services\\Modules\\OdtToPdfModule',
            'xlsx_to_pdf' => 'Convertre\\Services\\Modules\\XlsxToPdfModule',
            'pptx_to_pdf' => 'Convertre\\Services\\Modules\\PptxToPdfModule',
            'epub_to_pdf' => 'Convertre\\Services\\Modules\\EpubToPdfModule',
            'rtf_to_pdf' => 'Convertre\\Services\\Modules\\RtfToPdfModule',
            'txt_to_pdf' => 'Convertre\\Services\\Modules\\TxtToPdfModule',
            
            
            // Advanced Modules
            // Image Modules
            //Multi-Format Heic Module
            'heic_to_jpg' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',
            'heic_to_png' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',
            'heic_to_pdf' => 'Convertre\\Services\\Modules\\HeicMultiFormatModule',

            // Multi-format module (same class, different target formats)
            'jpg_to_png' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule',
            'jpg_to_webp' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule', 
            'jpg_to_pdf' => 'Convertre\\Services\\Modules\\JpgMultiFormatModule',

            // PNG MultiFormatModule
            'png_to_jpg' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',
            'png_to_webp' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',
            'png_to_pdf' => 'Convertre\\Services\\Modules\\PngMultiFormatModule',

            // WepP MultiFormatModule
            'webp_to_jpg' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',
            'webp_to_png' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',
            'webp_to_pdf' => 'Convertre\\Services\\Modules\\WebpMultiFormatModule',

            //GIF MultiFormatModule
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

            //TIFF MultiFormatModule
            'tiff_to_jpg' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',
            'tiff_to_png' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',
            'tiff_to_pdf' => 'Convertre\\Services\\Modules\\TiffMultiFormatModule',

            //pdf_to_jpg' => 'Convertre\\Services\\Modules\\PdfToJpgModule',
            'pdf_to_png' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',
            'pdf_to_jpg' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',
            'pdf_to_bmp' => 'Convertre\\Services\\Modules\\PdfMultiFormatModule',

            

        ];
    }
    
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
     * Get factory statistics
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