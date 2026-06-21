<?php

namespace App\Exceptions;

use Exception;

abstract class BaseException extends Exception
{
    protected int $statusCode = 400;
    protected string $errorCode = 'BASE_ERROR';
    protected array $context = [];

    public function __construct(string $message = '', int $statusCode = 0, string $errorCode = '', array $context = [])
    {
        parent::__construct($message);

        if ($statusCode > 0) {
            $this->statusCode = $statusCode;
        }
        if ($errorCode !== '') {
            $this->errorCode = $errorCode;
        }
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
        ] + ($this->context ? ['context' => $this->context] : []);
    }
}
