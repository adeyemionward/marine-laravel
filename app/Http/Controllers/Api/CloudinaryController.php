<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CloudinaryController extends Controller
{
    private $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
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

            $signature = $this->cloudinaryService->createUploadSignature($folder, $options);

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
        try {
            $request->validate([
                'image' => 'required|image|max:10240', // 10MB max
                'folder' => 'string|in:equipment,profiles,documents,banners',
                'public_id' => 'string|max:255',
                'tags' => 'string'
            ]);

            $folder = $request->get('folder', 'general');
            $options = $request->only(['public_id', 'tags']);
            
            if ($request->hasFile('image')) {
                $result = $this->cloudinaryService->uploadImage(
                    $request->file('image'),
                    $folder,
                    $options
                );

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

            $result = $this->cloudinaryService->uploadMultipleImages(
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
     * Delete image from Cloudinary
     */
    public function deleteImage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_id' => 'required|string'
            ]);

            $result = $this->cloudinaryService->deleteImage($request->public_id);

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
     * Delete multiple images from Cloudinary
     */
    public function deleteMultipleImages(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'public_ids' => 'required|array|min:1',
                'public_ids.*' => 'string'
            ]);

            $result = $this->cloudinaryService->deleteMultipleImages($request->public_ids);

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

            $url = $this->cloudinaryService->getOptimizedUrl(
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

            $urls = $this->cloudinaryService->getMultipleUrls($request->public_id, $transformations);

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