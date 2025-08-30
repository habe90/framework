<?php

namespace TrueFrame\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception
{
    /**
     * The validation errors.
     *
     * @var array<string, array<string>>
     */
    protected array $errors;

    /**
     * Create a new validation exception instance.
     *
     * @param array<string, array<string>> $errors
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(array $errors, string $message = 'The given data was invalid.', int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array<string, array<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}