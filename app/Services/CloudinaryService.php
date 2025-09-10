<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;
use Cloudinary\Transformation\Format;
use Cloudinary\Transformation\Delivery;
use Exception;

class CloudinaryService
{
    private $cloudinary;
    private $config;

    public function __construct()
    {
        $this->config = config('cloudinary');
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
            ],
            'url' => [
                'secure' => $this->config['secure']
            ]
        ]);
    }

    /**
     * Upload a single image to Cloudinary
     */
    public function uploadImage($file, $folder = 'general', $options = [])
    {
        try {
            $uploadOptions = array_merge([
                'folder' => $this->config['folders'][$folder] ?? "marine/{$folder}",
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'unique_filename' => true,
                'overwrite' => false,
            ], $options);

            // Handle different file input types
            if (is_string($file)) {
                // File path or base64
                $result = $this->cloudinary->uploadApi()->upload($file, $uploadOptions);
            } elseif (method_exists($file, 'getRealPath')) {
                // Laravel uploaded file
                $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), $uploadOptions);
            } else {
                throw new Exception('Invalid file type provided');
            }

            return [
                'success' => true,
                'data' => [
                    'public_id' => $result['public_id'],
                    'url' => $result['secure_url'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'format' => $result['format'],
                    'bytes' => $result['bytes'],
                    'created_at' => $result['created_at']
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
     * Delete an image from Cloudinary
     */
    public function deleteImage($publicId)
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            
            return [
                'success' => $result['result'] === 'ok',
                'data' => $result
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
    public function deleteMultipleImages($publicIds)
    {
        try {
            $result = $this->cloudinary->uploadApi()->deleteResources($publicIds);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate optimized URL with transformations
     */
    public function getOptimizedUrl($publicId, $transformation = 'medium', $customOptions = [])
    {
        if (!$publicId) {
            return null;
        }

        try {
            $transformations = $this->config['transformations'][$transformation] ?? $this->config['transformations']['medium'];
            $options = array_merge($transformations, $customOptions);

            return $this->cloudinary->image($publicId)
                ->resize(Resize::fill($options['width'], $options['height']))
                ->quality(Quality::auto())
                ->delivery(Delivery::format(Format::auto()))
                ->toUrl();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate multiple URLs with different transformations
     */
    public function getMultipleUrls($publicId, $transformations = ['thumbnail', 'medium', 'large'])
    {
        $urls = [];
        
        foreach ($transformations as $transformation) {
            $urls[$transformation] = $this->getOptimizedUrl($publicId, $transformation);
        }

        return $urls;
    }

    /**
     * Get image details from Cloudinary
     */
    public function getImageDetails($publicId)
    {
        try {
            $result = $this->cloudinary->adminApi()->asset($publicId);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create an upload widget signature (for direct frontend uploads)
     */
    public function createUploadSignature($folder = 'general', $options = [])
    {
        $timestamp = time();
        $params = array_merge([
            'timestamp' => $timestamp,
            'folder' => $this->config['folders'][$folder] ?? "marine/{$folder}",
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ], $options);

        ksort($params);
        $paramString = '';
        foreach ($params as $key => $value) {
            $paramString .= $key . '=' . $value . '&';
        }
        $paramString = rtrim($paramString, '&');

        $signature = sha1($paramString . $this->config['api_secret']);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'api_key' => $this->config['api_key'],
            'cloud_name' => $this->config['cloud_name'],
            'upload_preset' => $this->config['upload_preset'] ?? null
        ];
    }
}