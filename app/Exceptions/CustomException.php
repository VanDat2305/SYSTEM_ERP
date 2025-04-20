<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message = "Something went wrong", int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
