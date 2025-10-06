<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * File Upload Controller
 * Handles image and file uploads using Laravel's native storage system
 */
class FileUploadController extends Controller
{
    private $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Get upload signature for direct client uploads
     */
    public function getUploadSignature(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'folder' => 'string|in:equipment,profiles,documents,banners',
                'public_id' => 'string|max:255',
                'eager' => 'string',
                'tags' => 'string'
            ]);

            $folder = $request->get('folder', 'general');
            $options = $request->only(['public_id', 'eager', 'tags']);

            $signature = $this->fileStorageService->createUploadSignature($folder, $options);

            return response()->json([
                'success' => true,
                'data' => $signature
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload signature',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload single image via server
     */
    public function uploadImage(Request $request): JsonResponse
    {
        // Increase PHP upload limits for large banner images
        ini_set('upload_max_filesize', '20M');
        ini_set('post_max_size', '20M');
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '300');

        \Log::info('FileUploadController::uploadImage START', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('image'),
            'folder' => $request->get('folder'),
            'request_data' => $request->all(),
            'php_upload_limits' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit')
            ]
        ]);

        try {

            try {
                $request->validate([
                    'image' => 'required|image|max:10240', // 10MB max
                    'folder' => 'string|in:equipment,profiles,documents,banners',
                    'public_id' => 'string|max:255',
                    'tags' => 'string'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('FileUploadController validation failed', [
                    'errors' => $e->errors(),
                    'request_data' => $request->all()
                ]);
                throw $e;
            }

            $folder = $request->get('folder', 'general');
            $options = $request->only(['public_id', 'tags']);
            
            if ($request->hasFile('image')) {
                \Log::info('FileUploadController: About to call fileStorageService->uploadImage', [
                    'folder' => $folder,
                    'options' => $options,
                    'service_exists' => isset($this->fileStorageService),
                    'file_details' => [
                        'name' => $request->file('image')->getClientOriginalName(),
                        'size' => $request->file('image')->getSize(),
                        'mime' => $request->file('image')->getMimeType()
                    ]
                ]);

                $result = $this->fileStorageService->uploadImage(
                    $request->file('image'),
                    $folder,
                    $options
                );

                \Log::info('FileUploadController: fileStorageService->uploadImage completed', [
                    'result' => $result
                ]);

                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'data' => $result['data']
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Upload failed',
                        'error' => $result['error']
                    ], 400);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file provided'
            ], 400);
        } catch (\Exception $e) {
            \Log::error('FileUploadController::uploadImage error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images via server
     */
    public function uploadMultipleImages(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'image|max:10240',
                'folder' => 'string|in:equipment,profiles,documents,banners',
                'tags' => 'string'
            ]);

            $folder = $request->get('folder', 'general');
            $options = $request->only(['tags']);

            $result = $this->fileStorageService->uploadMultipleImages(
                $request->file('images'),
                $folder,
                $options
            );

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'errors' => $result['errors'] ?? []
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image from storage
     */
    public function deleteImage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_id' => 'required|string'
            ]);

            $result = $this->fileStorageService->deleteImage($request->public_id);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple images from storage
     */
    public function deleteMultipleImages(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_ids' => 'required|array|min:1',
                'public_ids.*' => 'string'
            ]);

            $result = $this->fileStorageService->deleteMultipleImages($request->public_ids);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get optimized URL for an image
     */
    public function getOptimizedUrl(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_id' => 'required|string',
                'transformation' => 'string|in:thumbnail,small,medium,large,hero,profile',
                'options' => 'array'
            ]);

            $transformation = $request->get('transformation', 'medium');
            $options = $request->get('options', []);

            $url = $this->fileStorageService->getOptimizedUrl(
                $request->public_id,
                $transformation,
                $options
            );

            if ($url) {
                return response()->json([
                    'success' => true,
                    'data' => ['url' => $url]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate URL'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get multiple optimized URLs for an image
     */
    public function getMultipleUrls(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_id' => 'required|string',
                'transformations' => 'array',
                'transformations.*' => 'string|in:thumbnail,small,medium,large,hero,profile'
            ]);

            $transformations = $request->get('transformations', ['thumbnail', 'medium', 'large']);

            $urls = $this->fileStorageService->getMultipleUrls($request->public_id, $transformations);

            return response()->json([
                'success' => true,
                'data' => $urls
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate URLs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}