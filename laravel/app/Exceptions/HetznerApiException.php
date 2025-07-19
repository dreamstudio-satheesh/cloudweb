<?php

namespace App\Exceptions;

use Exception;

class HetznerApiException extends Exception
{
    protected $statusCode;

    public function __construct($message = "", $statusCode = 500, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}