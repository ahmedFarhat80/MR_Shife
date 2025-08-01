<?php

if (!function_exists('uploadImage')) {
    /**
     * Upload an image to the specified path
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folderPath
     * @param string|null $prefix
     * @param string $disk
     * @param bool $returnFullUrl Whether to return the full URL or just the path
     *
     * @return string
     */
    function uploadImage($file, $folderPath, $prefix = null, $disk = 'public', $returnFullUrl = false)
    {
        // Ensure the folder path ends without a slash
        $folderPath = rtrim($folderPath, '/');

        // Generate a unique file name
        $fileName = ($prefix ? $prefix . '_' : '') . time() . '.' . $file->getClientOriginalExtension();

        // Store the image
        $file->storeAs($folderPath, $fileName, ['disk' => $disk]);

        // Get the relative path to the stored image
        $path = $folderPath . '/' . $fileName;

        // Return full URL if requested
        if ($returnFullUrl) {
            return asset('storage/' . $path);
        }

        // Return the relative path
        return $path;
    }
}

if (!function_exists('deleteImage')) {
    /**
     * Delete an image from the specified disk
     *
     * @param string $filePath
     * @param string $disk
     *
     * @return bool
     */
    function deleteImage($filePath, $disk = 'public')
    {
        return \Illuminate\Support\Facades\Storage::disk($disk)->delete($filePath);
    }
}

if (!function_exists('getFullImageUrl')) {
    /**
     * Get the full URL for an image path
     *
     * @param string $path
     * @return string|null
     */
    function getFullImageUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        // If the path already starts with http:// or https://, return it as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // If the path starts with /storage, remove the leading slash
        if (strpos($path, '/storage/') === 0) {
            $path = substr($path, 9); // Remove '/storage/' from the beginning
        }

        // If the path doesn't start with storage/, add it
        if (strpos($path, 'storage/') !== 0 && strpos($path, 'public/') !== 0) {
            $path = 'storage/' . $path;
        }

        return asset($path);
    }
}
