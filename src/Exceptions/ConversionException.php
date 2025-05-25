<?php

namespace Convertre\Exceptions;

class ConversionException extends \Exception
{
    private string $fromFormat;
    private string $toFormat;
    private string $filename;
    
    public function __construct(
        string $message,
        string $fromFormat = "",
        string $toFormat = "",
        string $filename = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->fromFormat = $fromFormat;
        $this->toFormat = $toFormat;
        $this->filename = $filename;
    }
    
    public function getFromFormat(): string
    {
        return $this->fromFormat;
    }
    
    public function getToFormat(): string
    {
        return $this->toFormat;
    }
    
    public function getFilename(): string
    {
        return $this->filename;
    }
    
    public function getConversionContext(): array
    {
        return [
            "from_format" => $this->fromFormat,
            "to_format" => $this->toFormat,
            "filename" => $this->filename
        ];
    }
}