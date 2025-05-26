<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApiResponseService
{
    /**
     * Return a success JSON response.
     *
     * @param string $message
     * @param mixed|null $data
     * @param array $meta
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(string $message = 'Success', $data = null, array $meta = [], int $code = 200): JsonResponse
    {
        return $this->formatResponse(true, $message, $data, $meta, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param mixed|null $errors
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function error(string $message = 'Error', $errors = null, int $code = 400): JsonResponse
    {
        return $this->formatResponse(false, $message, null, [], $code, $errors);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param mixed $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationError($errors, ?string $message = null): JsonResponse
    {
        $message = $message ?? __('general.validation_failed');
        return $this->error($message, $errors, 422);
    }

    /**
     * Format the response.
     *
     * @param bool $success
     * @param string $message
     * @param mixed|null $data
     * @param array $meta
     * @param int $code
     * @param mixed|null $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function formatResponse(bool $success, string $message, $data = null, array $meta = [], int $code = 200, $errors = null): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        // Add data if provided
        if ($data !== null) {
            $response['data'] = $this->parseData($data);
        }

        // Add meta data if provided
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        // Add errors if provided
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Parse the data based on its type.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function parseData($data)
    {
        // If it's a JsonResource, get the underlying resource
        if ($data instanceof JsonResource) {
            return $data->response()->getData()->data;
        }

        // If it's a ResourceCollection, get the underlying collection
        if ($data instanceof ResourceCollection) {
            return $data->response()->getData()->data;
        }

        // If it's a LengthAwarePaginator, format it properly
        if ($data instanceof LengthAwarePaginator) {
            return [
                'items' => $data->items(),
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
            ];
        }

        // If it's a Collection, convert it to array
        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return $data;
    }
}
