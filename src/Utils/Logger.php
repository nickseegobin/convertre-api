<?php

namespace Convertre\Utils;

/**
 * Logger - Consistent logging throughout the application
 * 
 * Simple file-based logging system for debugging and monitoring
 * Logs to /storage/logs/ directory with daily rotation
 */
class Logger
{
    private static string $logPath;
    private static bool $initialized = false;
    
    // Log levels
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    
    /**
     * Initialize the logger with the log directory path
     */
    public static function init(string $logPath): void
    {
        self::$logPath = rtrim($logPath, '/');
        
        // Create log directory if it doesn't exist
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        
        self::$initialized = true;
    }
    
    /**
     * Write a log entry
     * 
     * @param string $level Log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$initialized) {
            throw new \RuntimeException('Logger not initialized. Call Logger::init() first.');
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logPath . '/convertre-' . date('Y-m-d') . '.log';
        
        // Format the log entry
        $logEntry = sprintf(
            "[%s] %s: %s",
            $timestamp,
            $level,
            $message
        );
        
        // Add context if provided
        if (!empty($context)) {
            $contextString = json_encode($context, JSON_UNESCAPED_SLASHES);
            $logEntry .= " | Context: {$contextString}";
        }
        
        // Add request ID if available for request tracing
        if (isset($_SERVER['REQUEST_ID'])) {
            $logEntry .= " | Request: {$_SERVER['REQUEST_ID']}";
        }
        
        $logEntry .= PHP_EOL;
        
        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log conversion start
     */
    public static function conversionStart(string $fromFormat, string $toFormat, string $filename): void
    {
        self::info("Conversion started", [
            'from_format' => $fromFormat,
            'to_format' => $toFormat,
            'filename' => $filename
        ]);
    }
    
    /**
     * Log conversion success
     */
    public static function conversionSuccess(string $fromFormat, string $toFormat, string $filename, float $processingTime): void
    {
        self::info("Conversion completed successfully", [
            'from_format' => $fromFormat,
            'to_format' => $toFormat,
            'filename' => $filename,
            'processing_time' => $processingTime . 's'
        ]);
    }
    
    /**
     * Log conversion failure
     */
    public static function conversionFailed(string $fromFormat, string $toFormat, string $filename, string $errorMessage): void
    {
        self::error("Conversion failed", [
            'from_format' => $fromFormat,
            'to_format' => $toFormat,
            'filename' => $filename,
            'error' => $errorMessage
        ]);
    }
    
    /**
     * Log API request
     */
    public static function apiRequest(string $endpoint, string $method, array $params = []): void
    {
        self::info("API request", [
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => $params
        ]);
    }
    
    /**
     * Log authentication events
     */
    public static function authEvent(string $event, string $details = '', bool $success = true): void
    {
        $level = $success ? self::INFO : self::WARNING;
        self::log($level, "Authentication: {$event}", [
            'details' => $details,
            'success' => $success
        ]);
    }
    
    /**
     * Log file operations
     */
    public static function fileOperation(string $operation, string $filename, bool $success = true): void
    {
        $level = $success ? self::DEBUG : self::ERROR;
        self::log($level, "File operation: {$operation}", [
            'filename' => $filename,
            'success' => $success
        ]);
    }
    
    /**
     * Clean up old log files (older than specified days)
     */
    public static function cleanup(int $daysToKeep = 30): int
    {
        if (!self::$initialized) {
            return 0;
        }
        
        $deletedCount = 0;
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        $logFiles = glob(self::$logPath . '/convertre-*.log');
        
        foreach ($logFiles as $logFile) {
            $filename = basename($logFile);
            if (preg_match('/convertre-(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDate) {
                    if (unlink($logFile)) {
                        $deletedCount++;
                    }
                }
            }
        }
        
        if ($deletedCount > 0) {
            self::info("Log cleanup completed", [
                'deleted_files' => $deletedCount,
                'days_kept' => $daysToKeep
            ]);
        }
        
        return $deletedCount;
    }
}