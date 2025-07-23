<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ImageHelper
{
    /**
     * Default storage disk
     */
    const DEFAULT_DISK = 'public';

    /**
     * Image directories configuration
     */
    const DIRECTORIES = [
        'merchant_logos' => 'merchant_logos',
        'merchant_documents' => 'merchant_documents',
        'product_images' => 'product_images',
        'category_images' => 'category_images',
        'user_avatars' => 'user_avatars',
        'banners' => 'banners',
        'temp' => 'temp',
    ];

    /**
     * Image size configurations
     */
    const SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
        'logo' => ['width' => 400, 'height' => 400],
        'banner' => ['width' => 1920, 'height' => 600],
    ];

    /**
     * Allowed image types
     */
    const ALLOWED_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Maximum file size in KB
     */
    const MAX_SIZE = 5120; // 5MB

    /**
     * Upload image with multiple size variants
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param array $sizes
     * @param string $disk
     * @param string|null $oldPath
     * @return array
     */
    public static function upload(
        UploadedFile $file,
        string $directory,
        array $sizes = ['original'],
        string $disk = self::DEFAULT_DISK,
        ?string $oldPath = null
    ): array {
        try {
            // Validate file
            self::validateFile($file);

            // Delete old file if provided
            if ($oldPath) {
                self::delete($oldPath, $disk);
            }

            // Generate unique filename
            $filename = self::generateFilename($file);
            $directoryPath = self::getDirectoryPath($directory);

            $uploadedFiles = [];

            foreach ($sizes as $sizeKey) {
                if ($sizeKey === 'original') {
                    // Store original file
                    $path = $file->storeAs($directoryPath, $filename, $disk);
                    $uploadedFiles['original'] = $path;
                } else {
                    // Create resized version
                    $resizedPath = self::createResizedImage($file, $directoryPath, $filename, $sizeKey, $disk);
                    if ($resizedPath) {
                        $uploadedFiles[$sizeKey] = $resizedPath;
                    }
                }
            }

            return [
                'success' => true,
                'files' => $uploadedFiles,
                'main_path' => $uploadedFiles['original'] ?? $uploadedFiles[array_key_first($uploadedFiles)],
                'message' => 'Image uploaded successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
                'files' => [],
            ];
        }
    }

    /**
     * Upload single image (simplified version)
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $disk
     * @param string|null $oldPath
     * @return array
     */
    public static function uploadSingle(
        UploadedFile $file,
        string $directory,
        string $disk = self::DEFAULT_DISK,
        ?string $oldPath = null
    ): array {
        $result = self::upload($file, $directory, ['original'], $disk, $oldPath);

        if ($result['success']) {
            return [
                'success' => true,
                'path' => $result['main_path'],
                'message' => $result['message'],
            ];
        }

        return $result;
    }

    /**
     * Upload image with automatic resizing
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $sizeType
     * @param string $disk
     * @param string|null $oldPath
     * @return array
     */
    public static function uploadWithResize(
        UploadedFile $file,
        string $directory,
        string $sizeType = 'medium',
        string $disk = self::DEFAULT_DISK,
        ?string $oldPath = null
    ): array {
        return self::upload($file, $directory, ['original', $sizeType], $disk, $oldPath);
    }

    /**
     * Delete image(s)
     *
     * @param string|array $paths
     * @param string $disk
     * @return bool
     */
    public static function delete($paths, string $disk = self::DEFAULT_DISK): bool
    {
        try {
            if (is_string($paths)) {
                $paths = [$paths];
            }

            foreach ($paths as $path) {
                if ($path && Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }

                // Also try to delete resized versions
                self::deleteResizedVersions($path, $disk);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get image URL
     *
     * @param string|null $path
     * @param string $disk
     * @param string|null $default
     * @return string|null
     */
    public static function getUrl(?string $path, string $disk = self::DEFAULT_DISK, ?string $default = null): ?string
    {
        if (!$path) {
            return $default;
        }

        if ($disk === 'public') {
            // Normalize path separators for URLs (always use forward slashes)
            $normalizedPath = str_replace('\\', '/', $path);

            // Check if file exists
            $fullPath = storage_path('app/public/' . $normalizedPath);
            if (!file_exists($fullPath)) {
                return $default;
            }

            // Return full URL with domain using current app URL
            return config('app.url') . '/storage/' . $normalizedPath;
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Get multiple image URLs
     *
     * @param array $paths
     * @param string $disk
     * @return array
     */
    public static function getUrls(array $paths, string $disk = self::DEFAULT_DISK): array
    {
        $urls = [];
        foreach ($paths as $key => $path) {
            $urls[$key] = self::getUrl($path, $disk);
        }
        return $urls;
    }

    /**
     * Check if image exists
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public static function exists(string $path, string $disk = self::DEFAULT_DISK): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get image size information
     *
     * @param string $path
     * @param string $disk
     * @return array|null
     */
    public static function getImageInfo(string $path, string $disk = self::DEFAULT_DISK): ?array
    {
        try {
            if (!self::exists($path, $disk)) {
                return null;
            }

            $fullPath = Storage::disk($disk)->path($path);
            $imageSize = getimagesize($fullPath);

            if (!$imageSize) {
                return null;
            }

            return [
                'width' => $imageSize[0],
                'height' => $imageSize[1],
                'type' => $imageSize[2],
                'mime' => $imageSize['mime'],
                'size' => Storage::disk($disk)->size($path),
                'url' => self::getUrl($path, $disk),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private static function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > self::MAX_SIZE * 1024) {
            throw new \Exception('File size exceeds maximum allowed size of ' . self::MAX_SIZE . 'KB');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_TYPES)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_TYPES));
        }

        // Check if it's actually an image
        if (!getimagesize($file->getPathname())) {
            throw new \Exception('File is not a valid image');
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    private static function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get directory path
     *
     * @param string $directory
     * @return string
     */
    private static function getDirectoryPath(string $directory): string
    {
        return self::DIRECTORIES[$directory] ?? $directory;
    }

    /**
     * Create resized image
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @param string $sizeKey
     * @param string $disk
     * @return string|null
     */
    private static function createResizedImage(
        UploadedFile $file,
        string $directory,
        string $filename,
        string $sizeKey,
        string $disk
    ): ?string {
        try {
            if (!isset(self::SIZES[$sizeKey])) {
                return null;
            }

            $size = self::SIZES[$sizeKey];
            $resizedFilename = pathinfo($filename, PATHINFO_FILENAME) . "_{$sizeKey}." . pathinfo($filename, PATHINFO_EXTENSION);

            // Create resized image using Intervention Image (if available)
            if (class_exists('Intervention\Image\Facades\Image')) {
                $image = Image::make($file->getPathname())
                    ->resize($size['width'], $size['height'], function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });

                $resizedPath = $directory . '/' . $resizedFilename;
                $fullPath = Storage::disk($disk)->path($resizedPath);

                // Ensure directory exists
                $dirPath = dirname($fullPath);
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0755, true);
                }

                $image->save($fullPath);
                return $resizedPath;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to create resized image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete resized versions of an image
     *
     * @param string $originalPath
     * @param string $disk
     */
    private static function deleteResizedVersions(string $originalPath, string $disk): void
    {
        try {
            $pathInfo = pathinfo($originalPath);
            $directory = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'];

            foreach (array_keys(self::SIZES) as $sizeKey) {
                $resizedPath = $directory . '/' . $filename . "_{$sizeKey}.{$extension}";
                if (Storage::disk($disk)->exists($resizedPath)) {
                    Storage::disk($disk)->delete($resizedPath);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to delete resized versions: ' . $e->getMessage());
        }
    }

    /**
     * Get available image directories
     *
     * @return array
     */
    public static function getDirectories(): array
    {
        return self::DIRECTORIES;
    }

    /**
     * Get available image sizes
     *
     * @return array
     */
    public static function getSizes(): array
    {
        return self::SIZES;
    }

    /**
     * Get allowed file types
     *
     * @return array
     */
    public static function getAllowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Get maximum file size in KB
     *
     * @return int
     */
    public static function getMaxSize(): int
    {
        return self::MAX_SIZE;
    }
}
