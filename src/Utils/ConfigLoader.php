<?php

namespace Convertre\Utils;

/**
 * ConfigLoader - Centralized configuration management
 * 
 * Handles loading configuration files from /config/ directory
 * Supports environment-specific overrides and caching
 */
class ConfigLoader
{
    private static array $cache = [];
    private static string $configPath;
    
    /**
     * Initialize the config loader with the config directory path
     */
    public static function init(string $configPath): void
    {
        self::$configPath = rtrim($configPath, '/');
    }
    
    /**
     * Load configuration from a specific file
     * 
     * @param string $configName Name of config file (without .php extension)
     * @param bool $useCache Whether to use cached version if available
     * @return array Configuration array
     * @throws \RuntimeException If config file doesn't exist or is invalid
     */
    public static function load(string $configName, bool $useCache = true): array
    {
        // Return cached version if available and requested
        if ($useCache && isset(self::$cache[$configName])) {
            return self::$cache[$configName];
        }
        
        $configFile = self::$configPath . '/' . $configName . '.php';
        
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Configuration file not found: {$configFile}");
        }
        
        $config = require $configFile;
        
        if (!is_array($config)) {
            throw new \RuntimeException("Configuration file must return an array: {$configFile}");
        }
        
        // Cache the configuration
        self::$cache[$configName] = $config;
        
        return $config;
    }
    
    /**
     * Get a specific configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'api.rate_limit' or 'limits.max_file_size')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $configName = array_shift($parts);
        
        try {
            $config = self::load($configName);
        } catch (\RuntimeException $e) {
            return $default;
        }
        
        // Navigate through nested array using remaining parts
        $value = $config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
    
    /**
     * Check if a configuration key exists
     */
    public static function has(string $key): bool
    {
        $parts = explode('.', $key);
        $configName = array_shift($parts);
        
        try {
            $config = self::load($configName);
        } catch (\RuntimeException $e) {
            return false;
        }
        
        $value = $config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return false;
            }
            $value = $value[$part];
        }
        
        return true;
    }
    
    /**
     * Clear configuration cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
    
    /**
     * Get all loaded configurations (for debugging)
     */
    public static function getCache(): array
    {
        return self::$cache;
    }
}