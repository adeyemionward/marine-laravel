<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class FileStorageService
{
    /**
     * Upload a single image using Laravel file storage
     */
    public function uploadImage($file, $folder = 'general', $options = [])
    {
        try {
            \Log::info('FileStorageService::uploadImage called', [
                'folder' => $folder,
                'file_info' => [
                    'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'unknown',
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : 'unknown',
                    'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : 'unknown'
                ]
            ]);

            // Generate unique filename
            $originalName = method_exists($file, 'getClientOriginalName')
                ? $file->getClientOriginalName()
                : 'image.jpg';
            $extension = method_exists($file, 'getClientOriginalExtension')
                ? $file->getClientOriginalExtension()
                : 'jpg';

            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
                . '_' . time()
                . '_' . Str::random(8)
                . '.' . $extension;

            // Determine storage path
            $storagePath = "images/{$folder}/{$filename}";

            // Store the file
            if (method_exists($file, 'storeAs')) {
                // Laravel UploadedFile
                $path = $file->storeAs("images/{$folder}", $filename, 'public');
            } else {
                // Handle other file types
                $path = Storage::disk('public')->put($storagePath, file_get_contents($file));
            }

            // Get file details
            $fullPath = Storage::disk('public')->path($storagePath);
            $fileSize = Storage::disk('public')->size($storagePath);

            // Generate absolute URL for the file
            // Use config app.url to ensure correct domain on production
            $url = Storage::disk('public')->url($storagePath);

            // Ensure URL is absolute (important for GoDaddy hosting)
            if (!str_starts_with($url, 'http')) {
                $baseUrl = rtrim(config('app.url'), '/');
                $url = $baseUrl . $url;
            }

            // Get image dimensions
            $width = null;
            $height = null;
            try {
                if (file_exists($fullPath)) {
                    $imageInfo = getimagesize($fullPath);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
            } catch (Exception $e) {
                \Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
            }

            \Log::info('FileStorageService: upload successful', [
                'path' => $storagePath,
                'url' => $url
            ]);

            return [
                'success' => true,
                'data' => [
                    'public_id' => $storagePath, // Keep for compatibility
                    'path' => $storagePath,
                    'url' => $url,
                    'width' => $width,
                    'height' => $height,
                    'format' => $extension,
                    'bytes' => $fileSize,
                    'created_at' => now()->toIso8601String()
                ]
            ];
        } catch (Exception $e) {
            \Log::error('FileStorageService: upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload a single file (documents, PDFs, images, etc.)
     */
    public function uploadFile($file, $folder = 'general', $options = [])
    {
        try {
            \Log::info('FileStorageService::uploadFile called', [
                'folder' => $folder,
                'file_info' => [
                    'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'unknown',
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : 'unknown',
                    'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : 'unknown'
                ]
            ]);

            // Generate unique filename
            $originalName = method_exists($file, 'getClientOriginalName')
                ? $file->getClientOriginalName()
                : 'file.pdf';
            $extension = method_exists($file, 'getClientOriginalExtension')
                ? $file->getClientOriginalExtension()
                : 'pdf';

            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME))
                . '_' . time()
                . '_' . Str::random(8)
                . '.' . $extension;

            // Determine storage path
            $storagePath = "{$folder}/{$filename}";

            // Store the file
            if (method_exists($file, 'storeAs')) {
                // Laravel UploadedFile
                $path = $file->storeAs($folder, $filename, 'public');
            } else {
                // Handle other file types
                $path = Storage::disk('public')->put($storagePath, file_get_contents($file));
            }

            // Get file details
            $fullPath = Storage::disk('public')->path($storagePath);
            $fileSize = Storage::disk('public')->size($storagePath);

            // Generate absolute URL for the file
            $url = Storage::disk('public')->url($storagePath);

            // Ensure URL is absolute (important for GoDaddy hosting)
            if (!str_starts_with($url, 'http')) {
                $baseUrl = rtrim(config('app.url'), '/');
                $url = $baseUrl . $url;
            }

            // Try to get image dimensions if it's an image
            $width = null;
            $height = null;
            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);

            if ($isImage) {
                try {
                    if (file_exists($fullPath)) {
                        $imageInfo = getimagesize($fullPath);
                        if ($imageInfo) {
                            $width = $imageInfo[0];
                            $height = $imageInfo[1];
                        }
                    }
                } catch (Exception $e) {
                    \Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
                }
            }

            \Log::info('FileStorageService: file upload successful', [
                'path' => $storagePath,
                'url' => $url
            ]);

            return [
                'success' => true,
                'data' => [
                    'public_id' => $storagePath, // Keep for compatibility
                    'path' => $storagePath,
                    'url' => $url,
                    'width' => $width,
                    'height' => $height,
                    'format' => $extension,
                    'bytes' => $fileSize,
                    'created_at' => now()->toIso8601String()
                ]
            ];
        } catch (Exception $e) {
            \Log::error('FileStorageService: file upload failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleImages($files, $folder = 'general', $options = [])
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            $result = $this->uploadImage($file, $folder, $options);

            if ($result['success']) {
                $results[] = $result['data'];
            } else {
                $errors[] = [
                    'index' => $index,
                    'error' => $result['error']
                ];
            }
        }

        return [
            'success' => empty($errors),
            'data' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Delete an image from storage
     */
    public function deleteImage($path)
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return [
                    'success' => true,
                    'data' => ['result' => 'ok']
                ];
            }

            return [
                'success' => false,
                'error' => 'File not found'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete multiple images
     */
    public function deleteMultipleImages($paths)
    {
        try {
            $deleted = [];
            $failed = [];

            foreach ($paths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    $deleted[] = $path;
                } else {
                    $failed[] = $path;
                }
            }

            return [
                'success' => empty($failed),
                'data' => [
                    'deleted' => $deleted,
                    'failed' => $failed
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get optimized URL (for compatibility with Cloudinary)
     * In Laravel storage, we just return the storage URL
     */
    public function getOptimizedUrl($path, $transformation = 'medium', $customOptions = [])
    {
        if (!$path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                $url = Storage::disk('public')->url($path);

                // Ensure URL is absolute (important for GoDaddy hosting)
                if (!str_starts_with($url, 'http')) {
                    $baseUrl = rtrim(config('app.url'), '/');
                    $url = $baseUrl . $url;
                }

                return $url;
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate multiple URLs (for compatibility)
     */
    public function getMultipleUrls($path, $transformations = ['thumbnail', 'medium', 'large'])
    {
        $url = $this->getOptimizedUrl($path);
        $urls = [];

        foreach ($transformations as $transformation) {
            $urls[$transformation] = $url;
        }

        return $urls;
    }

    /**
     * Get image details
     */
    public function getImageDetails($path)
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }

            $fullPath = Storage::disk('public')->path($path);
            $url = Storage::disk('public')->url($path);
            $size = Storage::disk('public')->size($path);

            $width = null;
            $height = null;
            $format = pathinfo($path, PATHINFO_EXTENSION);

            if (file_exists($fullPath)) {
                $imageInfo = getimagesize($fullPath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'path' => $path,
                    'url' => $url,
                    'bytes' => $size,
                    'format' => $format,
                    'width' => $width,
                    'height' => $height
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create upload signature (for compatibility - not needed for local storage)
     */
    public function createUploadSignature($folder = 'general', $options = [])
    {
        // Local storage doesn't need signatures
        return [
            'folder' => $folder,
            'timestamp' => time()
        ];
    }
}
