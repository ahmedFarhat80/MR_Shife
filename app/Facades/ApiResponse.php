<?php

namespace App\Facades;

use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Http\JsonResponse success(string $message = 'Success', mixed $data = null, array $meta = [], int $code = 200)
 * @method static \Illuminate\Http\JsonResponse error(string $message = 'Error', mixed $errors = null, int $code = 400)
 * @method static \Illuminate\Http\JsonResponse validationError(mixed $errors, string $message = 'Validation Error')
 * 
 * @see \App\Services\ApiResponseService
 */
class ApiResponse extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ApiResponseService::class;
    }
}
