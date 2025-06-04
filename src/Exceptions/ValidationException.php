<?php

namespace Convertre\Exceptions;

class ValidationException extends \Exception
{
    private array $validationErrors;
    private string $field;
    
    public function __construct(
        string $message,
        string $field = "",
        array $validationErrors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->field = $field;
        $this->validationErrors = $validationErrors;
    }
    
    public function getField(): string
    {
        return $this->field;
    }
    
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}