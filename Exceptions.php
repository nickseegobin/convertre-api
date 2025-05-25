<?php

namespace Convertre\Exceptions;

/**
 * ConversionException - Thrown when file conversion fails
 */
class ConversionException extends \Exception
{
    private string $fromFormat;
    private string $toFormat;
    private string $filename;
    
    public function __construct(
        string $message,
        string $fromFormat = '',
        string $toFormat = '',
        string $filename = '',
        int $code = 0,
        \Throwable $previous = null
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
            'from_format' => $this->fromFormat,
            'to_format' => $this->toFormat,
            'filename' => $this->filename
        ];
    }
}

/**
 * ValidationException - Thrown when input validation fails
 */
class ValidationException extends \Exception
{
    private array $validationErrors;
    private string $field;
    
    public function __construct(
        string $message,
        string $field = '',
        array $validationErrors = [],
        int $code = 0,
        \Throwable $previous = null
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
    
    public function addValidationError(string $field, string $error): void
    {
        $this->validationErrors[$field] = $error;
    }
    
    public function getValidationContext(): array
    {
        return [
            'field' => $this->field,
            'errors' => $this->validationErrors
        ];
    }
}

/**
 * AuthenticationException - Thrown when authentication fails
 */
class AuthenticationException extends \Exception
{
    private string $authMethod;
    private string $identifier;
    
    public function __construct(
        string $message,
        string $authMethod = 'api_key',
        string $identifier = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->authMethod = $authMethod;
        $this->identifier = $identifier;
    }
    
    public function getAuthMethod(): string
    {
        return $this->authMethod;
    }
    
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    
    public function getAuthContext(): array
    {
        return [
            'auth_method' => $this->authMethod,
            'identifier' => $this->identifier
        ];
    }
}