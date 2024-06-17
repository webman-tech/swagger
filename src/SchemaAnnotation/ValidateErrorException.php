<?php

namespace WebmanTech\Swagger\SchemaAnnotation;

use Throwable;

class ValidateErrorException extends \RuntimeException
{
    public array $errors = [];

    public function __construct(string $message = "validate error", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function withErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }
}
