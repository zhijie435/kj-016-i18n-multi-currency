<?php

namespace App\Exceptions;

class UnauthorizedException extends BaseException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';

    public function __construct(string $message = 'Unauthorized', array $context = [])
    {
        parent::__construct($message, 401, 'UNAUTHORIZED', $context);
    }
}
