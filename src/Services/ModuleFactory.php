<?php

namespace Convertre\Services;

use Convertre\Services\AbstractConversionModule;
use Convertre\Utils\Logger;
use Convertre\Exceptions\ConversionException;

/**
 * ModuleFactory - Creates and manages conversion modules
 * Simple factory - gets the right module for the job
 */
class ModuleFactory
{
    private static array $modules = [];
    private static bool $initialized = false;
    
    /**
     * Initialize factory and register available modules
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Register MVP modules (will be created in Phase 4)
        self::registerAvailableModules();
        
        self::$initialized = true;
        Logger::debug('ModuleFactory initialized with ' . count(self::$modules) . ' modules');
    }
    
    /**
     * Get module for specific conversion
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
        
        return new $moduleClass();
    }
    
    /**
     * Check if conversion is supported
     */
    public static function isSupported(string $fromFormat, string $toFormat): bool
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $key = strtolower($fromFormat) . '_to_' . strtolower($toFormat);
        return isset(self::$modules[$key]) && class_exists(self::$modules[$key]);
    }
    
    /**
     * Get all supported conversions
     */
    public static function getSupportedConversions(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $supported = [];
        foreach (self::$modules as $key => $class) {
            if (class_exists($class)) {
                $parts = explode('_to_', $key);
                $supported[] = [
                    'from' => $parts[0],
                    'to' => $parts[1],
                    'module' => $class
                ];
            }
        }
        
        return $supported;
    }
    
    /**
     * Register available modules (MVP only for now)
     */
    private static function registerAvailableModules(): void
    {
        // MVP Module registrations (Phase 4 will create these classes)
        self::$modules = [
            'heic_to_jpg' => 'Convertre\\Services\\Modules\\HeicToJpgModule',
            'docx_to_pdf' => 'Convertre\\Services\\Modules\\DocxToPdfModule'
        ];
        
        // Future modules can be added here
        // 'jpg_to_png' => 'Convertre\\Services\\Modules\\JpgToPngModule',
        // 'png_to_jpg' => 'Convertre\\Services\\Modules\\PngToJpgModule',
        // etc.
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