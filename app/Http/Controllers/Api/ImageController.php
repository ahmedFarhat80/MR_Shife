<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ImageHelper;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Upload single image
     */
    public function uploadSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:' . implode(',', ImageHelper::getAllowedTypes()) . '|max:' . ImageHelper::getMaxSize(),
            'directory' => 'required|string|in:' . implode(',', array_keys(ImageHelper::getDirectories())),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $result = ImageHelper::uploadSingle(
            $request->file('image'),
            $request->directory
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            __('api.image_uploaded_successfully'),
            [
                'path' => $result['path'],
                'url' => ImageHelper::getUrl($result['path']),
                'info' => ImageHelper::getImageInfo($result['path']),
            ]
        );
    }

    /**
     * Upload image with multiple sizes
     */
    public function uploadWithSizes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:' . implode(',', ImageHelper::getAllowedTypes()) . '|max:' . ImageHelper::getMaxSize(),
            'directory' => 'required|string|in:' . implode(',', array_keys(ImageHelper::getDirectories())),
            'sizes' => 'nullable|array',
            'sizes.*' => 'string|in:' . implode(',', array_keys(ImageHelper::getSizes())),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $sizes = $request->sizes ?? ['original', 'medium', 'thumbnail'];

        $result = ImageHelper::upload(
            $request->file('image'),
            $request->directory,
            $sizes
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            __('api.image_uploaded_successfully'),
            [
                'files' => $result['files'],
                'main_path' => $result['main_path'],
                'urls' => ImageHelper::getUrls($result['files']),
                'info' => ImageHelper::getImageInfo($result['main_path']),
            ]
        );
    }

    /**
     * Delete image
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        if (!ImageHelper::exists($request->path)) {
            return $this->apiResponse->error(__('api.image_not_found'), [], 404);
        }

        $deleted = ImageHelper::delete($request->path);

        if (!$deleted) {
            return $this->apiResponse->error(__('api.failed_to_delete_image'));
        }

        return $this->apiResponse->success(__('api.image_deleted_successfully'));
    }

    /**
     * Get image information
     */
    public function getInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $info = ImageHelper::getImageInfo($request->path);

        if (!$info) {
            return $this->apiResponse->error(__('api.image_not_found'), [], 404);
        }

        return $this->apiResponse->success(
            __('api.data_retrieved'),
            $info
        );
    }

    /**
     * Get available configurations
     */
    public function getConfig()
    {
        return $this->apiResponse->success(
            __('api.data_retrieved'),
            [
                'directories' => ImageHelper::getDirectories(),
                'sizes' => ImageHelper::getSizes(),
                'allowed_types' => ImageHelper::getAllowedTypes(),
                'max_size_kb' => ImageHelper::getMaxSize(),
            ]
        );
    }
} 