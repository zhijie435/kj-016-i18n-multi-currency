<?php

namespace App\Exceptions;

class NotFoundException extends BaseException
{
    protected int $statusCode = 404;
    protected string $errorCode = 'NOT_FOUND';

    public function __construct(string $resource = 'Resource', array $context = [])
    {
        $message = $resource . ' not found';
        parent::__construct($message, 404, 'NOT_FOUND', $context);
    }
}
