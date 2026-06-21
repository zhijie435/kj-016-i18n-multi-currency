<?php

namespace App\Exceptions;

class BusinessException extends BaseException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'BUSINESS_ERROR';

    public function __construct(string $message = 'Business operation failed', int $statusCode = 400, string $errorCode = 'BUSINESS_ERROR', array $context = [])
    {
        parent::__construct($message, $statusCode, $errorCode, $context);
    }
}
