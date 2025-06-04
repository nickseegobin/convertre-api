<?php

namespace Convertre\Exceptions;

class AuthenticationException extends \Exception
{
    private string $authMethod;
    private string $identifier;
    
    public function __construct(
        string $message,
        string $authMethod = "api_key",
        string $identifier = "",
        int $code = 0,
        ?\Throwable $previous = null
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
}