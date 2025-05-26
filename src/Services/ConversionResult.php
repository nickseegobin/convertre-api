<?php

namespace Convertre\Services;

/**
 * ConversionResult - Simple result container
 * Holds conversion results - success or failure
 */
class ConversionResult
{
    private bool $success;
    private string $outputFile;
    private string $originalFile;
    private string $fromFormat;
    private string $toFormat;
    private float $processingTime;
    private string $errorMessage;
    
    public function __construct(
        bool $success,
        string $outputFile = '',
        string $originalFile = '',
        string $fromFormat = '',
        string $toFormat = '',
        float $processingTime = 0.0,
        string $errorMessage = ''
    ) {
        $this->success = $success;
        $this->outputFile = $outputFile;
        $this->originalFile = $originalFile;
        $this->fromFormat = $fromFormat;
        $this->toFormat = $toFormat;
        $this->processingTime = $processingTime;
        $this->errorMessage = $errorMessage;
    }
    
    // Simple getters
    public function isSuccess(): bool { return $this->success; }
    public function getOutputFile(): string { return $this->outputFile; }
    public function getOriginalFile(): string { return $this->originalFile; }
    public function getFromFormat(): string { return $this->fromFormat; }
    public function getToFormat(): string { return $this->toFormat; }
    public function getProcessingTime(): float { return $this->processingTime; }
    public function getErrorMessage(): string { return $this->errorMessage; }
    
    // Quick factory methods
    public static function success(
        string $outputFile, 
        string $originalFile, 
        string $fromFormat, 
        string $toFormat, 
        float $processingTime
    ): self {
        return new self(true, $outputFile, $originalFile, $fromFormat, $toFormat, $processingTime);
    }
    
    public static function failure(
        string $errorMessage, 
        string $originalFile = '', 
        string $fromFormat = '', 
        string $toFormat = ''
    ): self {
        return new self(false, '', $originalFile, $fromFormat, $toFormat, 0.0, $errorMessage);
    }
}