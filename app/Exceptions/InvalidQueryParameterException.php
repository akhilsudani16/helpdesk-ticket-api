<?php

namespace App\Exceptions;

use Exception;

class InvalidQueryParameterException extends Exception
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'Invalid query parameter.')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render()
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], 400);
    }
}
