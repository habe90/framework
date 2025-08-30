<?php

namespace TrueFrame\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    /**
     * Create a new not found exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'Not Found', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}