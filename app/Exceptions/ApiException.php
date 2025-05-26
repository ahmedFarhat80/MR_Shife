<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $errors;
    protected $statusCode;

    /**
     * Create a new API exception instance.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     */
    public function __construct(string $message = 'An error occurred', $errors = null, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->errors = $errors;
        $this->statusCode = $statusCode;
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
        ];

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Create a validation error exception.
     *
     * @param mixed $errors
     * @param string $message
     * @return static
     */
    public static function validationError($errors, string $message = 'Validation Error'): static
    {
        return new static($message, $errors, 422);
    }

    /**
     * Create an unauthorized exception.
     *
     * @param string $message
     * @return static
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return new static($message, null, 401);
    }

    /**
     * Create a forbidden exception.
     *
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return new static($message, null, 403);
    }

    /**
     * Create a not found exception.
     *
     * @param string $message
     * @return static
     */
    public static function notFound(string $message = 'Not Found'): static
    {
        return new static($message, null, 404);
    }
}
