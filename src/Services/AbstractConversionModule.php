<?php

namespace Convertre\Services;

use Convertre\Utils\Logger;
use Convertre\Utils\FileHandler;
use Convertre\Services\ConversionResult;
use Convertre\Exceptions\ConversionException;

/**
 * AbstractConversionModule - Base class for all conversion modules
 * Simple, functional foundation - each module extends this
 */
abstract class AbstractConversionModule
{
    protected string $fromFormat;
    protected string $toFormat;
    protected string $toolName;
    
    public function __construct(string $fromFormat, string $toFormat, string $toolName)
    {
        $this->fromFormat = strtolower($fromFormat);
        $this->toFormat = strtolower($toFormat);
        $this->toolName = $toolName;
    }
    
    /**
     * Main conversion method - each module implements this
     */
    abstract protected function executeConversion(string $inputFile, string $outputFile): bool;
    
    /**
     * Check if required tool is available - each module implements this
     */
    abstract protected function isToolAvailable(): bool;
    
    /**
     * Get tool-specific command timeout
     */
    protected function getTimeout(): int
    {
        return $this->toolName === 'libreoffice' ? 300 : 60; // 5 min for docs, 1 min for images
    }
    
    /**
     * Main public conversion method
     */
    public function convert(string $inputFile, string $outputFile): ConversionResult
    {
        $startTime = microtime(true);
        $originalName = basename($inputFile);
        
        Logger::conversionStart($this->fromFormat, $this->toFormat, $originalName);
        
        try {
            // Check tool availability
            if (!$this->isToolAvailable()) {
                throw new ConversionException(
                    "Tool '{$this->toolName}' not available",
                    $this->fromFormat,
                    $this->toFormat,
                    $originalName
                );
            }
            
            // Check input file exists
            if (!file_exists($inputFile)) {
                throw new ConversionException(
                    "Input file not found",
                    $this->fromFormat,
                    $this->toFormat,
                    $originalName
                );
            }
            
            // Execute the actual conversion
            $success = $this->executeConversion($inputFile, $outputFile);
            
            if (!$success) {
                throw new ConversionException(
                    "Conversion process failed",
                    $this->fromFormat,
                    $this->toFormat,
                    $originalName
                );
            }
            
            // Check output file was created
            if (!file_exists($outputFile)) {
                throw new ConversionException(
                    "Output file not generated",
                    $this->fromFormat,
                    $this->toFormat,
                    $originalName
                );
            }
            
            $processingTime = microtime(true) - $startTime;
            
            Logger::conversionSuccess($this->fromFormat, $this->toFormat, $originalName, $processingTime);
            
            return ConversionResult::success(
                $outputFile,
                $inputFile,
                $this->fromFormat,
                $this->toFormat,
                $processingTime
            );
            
        } catch (ConversionException $e) {
            $processingTime = microtime(true) - $startTime;
            
            Logger::conversionFailed($this->fromFormat, $this->toFormat, $originalName, $e->getMessage());
            
            return ConversionResult::failure(
                $e->getMessage(),
                $inputFile,
                $this->fromFormat,
                $this->toFormat
            );
        }
    }
    
    /**
     * Cleanup temporary files
     */
    protected function cleanup(array $filesToDelete): void
    {
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
                Logger::debug("Cleaned up temporary file: " . basename($file));
            }
        }
    }
    
    /**
     * Execute shell command safely with timeout
     */
    protected function executeCommand(string $command, ?int $timeout = null): array
    {
        $timeout = $timeout ?? $this->getTimeout();
        
        Logger::debug("Executing command", ['command' => $command, 'timeout' => $timeout]);
        
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($command, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return ['success' => false, 'output' => '', 'error' => 'Failed to start process'];
        }
        
        // Close stdin
        fclose($pipes[0]);
        
        // Set non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        
        $output = '';
        $error = '';
        $start = time();
        
        while (time() - $start < $timeout) {
            $status = proc_get_status($process);
            
            if (!$status['running']) {
                break;
            }
            
            // Read output
            $output .= stream_get_contents($pipes[1]);
            $error .= stream_get_contents($pipes[2]);
            
            usleep(100000); // 0.1 second
        }
        
        // Final read
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        return [
            'success' => $exitCode === 0,
            'output' => $output,
            'error' => $error,
            'exit_code' => $exitCode
        ];
    }
    
    /**
     * Get supported formats for this module
     */
    public function getFromFormat(): string { return $this->fromFormat; }
    public function getToFormat(): string { return $this->toFormat; }
    public function getToolName(): string { return $this->toolName; }
    
    /**
     * Check if this module can handle the conversion
     */
    public function canConvert(string $from, string $to): bool
    {
        return strtolower($from) === $this->fromFormat && strtolower($to) === $this->toFormat;
    }
}