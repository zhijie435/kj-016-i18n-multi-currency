<?php

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    protected int $statusCode = 403;
    protected string $errorCode = 'FORBIDDEN';

    public function __construct(string $message = 'Permission denied', array $context = [])
    {
        parent::__construct($message, 403, 'FORBIDDEN', $context);
    }
}
