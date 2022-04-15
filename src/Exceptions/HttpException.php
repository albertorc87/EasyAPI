<?php

namespace EasyAPI\Exceptions;
use Exception;

class HttpException extends Exception
{
    public function __construct($message = 'Internal Server Error', $code = 500, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}