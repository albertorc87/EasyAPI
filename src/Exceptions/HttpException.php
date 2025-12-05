<?php

namespace EasyAPI\Exceptions;
use Exception;

/**
 * This exception is controlled for App class and return the message and
 * status http code sent in this exception
 */
class HttpException extends Exception
{
    public function __construct($message = 'Internal Server Error', $status_http_code = 500, ?Exception $previous = null) {
        parent::__construct($message, $status_http_code, $previous);
    }
}