<?php

namespace App\Exceptions;

class ValidationException extends BaseException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_ERROR';

    protected array $errors = [];

    public function __construct(string $message = 'Validation failed', array $errors = [], array $context = [])
    {
        parent::__construct($message, 422, 'VALIDATION_ERROR', $context);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return parent::toArray() + ($this->errors ? ['errors' => $this->errors] : []);
    }
}
