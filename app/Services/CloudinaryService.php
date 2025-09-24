<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;
use Cloudinary\Transformation\Format;
use Cloudinary\Transformation\Delivery;
use GuzzleHttp\Client;
use Exception;

class CloudinaryService
{
    private $cloudinary;
    private $config;

    public function __construct()
    {
        $this->config = config('cloudinary');

        // Configuration array for Cloudinary
        $cloudinaryConfig = [
            'cloud' => [
                'cloud_name' => $this->config['cloud_name'],
                'api_key' => $this->config['api_key'],
                'api_secret' => $this->config['api_secret'],
            ],
            'url' => [
                'secure' => $this->config['secure']
            ]
        ];

        // Add HTTP client options for local development to handle SSL issues
        if (app()->environment('local')) {
            $cloudinaryConfig['http'] = [
                'verify' => false, // Disable SSL verification for local development
                'timeout' => 30,
                'connect_timeout' => 10
            ];
        }

        $this->cloudinary = new Cloudinary($cloudinaryConfig);
    }

    /**
     * Upload a single image to Cloudinary
     */
    public function uploadImage($file, $folder = 'general', $options = [])
    {
        try {
            \Log::info('CloudinaryService::uploadImage called', [
                'folder' => $folder,
                'file_info' => [
                    'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'unknown',
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : 'unknown',
                    'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : 'unknown',
                    'real_path' => method_exists($file, 'getRealPath') ? $file->getRealPath() : 'unknown'
                ]
            ]);

            // Set cURL options to handle SSL issues in local development
            if (app()->environment('local')) {
                curl_setopt_array(curl_init(), [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
            }

            $uploadOptions = array_merge([
                'folder' => $this->config['folders'][$folder] ?? "marine/{$folder}",
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'unique_filename' => true,
                'overwrite' => false,
            ], $options);

            \Log::info('CloudinaryService upload options', ['options' => $uploadOptions]);

            // Handle different file input types
            if (is_string($file)) {
                \Log::info('CloudinaryService: uploading string/path file');
                // File path or base64
                $result = $this->uploadWithCustomCurl($file, $uploadOptions);
            } elseif (method_exists($file, 'getRealPath')) {
                \Log::info('CloudinaryService: uploading Laravel UploadedFile', [
                    'real_path' => $file->getRealPath(),
                    'path_exists' => file_exists($file->getRealPath())
                ]);
                // Laravel uploaded file
                $result = $this->uploadWithCustomCurl($file->getRealPath(), $uploadOptions);
            } else {
                \Log::error('CloudinaryService: Invalid file type', ['file_type' => gettype($file)]);
                throw new Exception('Invalid file type provided');
            }

            \Log::info('CloudinaryService: upload successful', ['result' => $result]);

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
            \Log::error('CloudinaryService: upload failed', [
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
     * Upload file using custom cURL to handle SSL issues in development
     */
    private function uploadWithCustomCurl($filePath, $uploadOptions)
    {
        \Log::info('CloudinaryService: Using custom cURL upload', [
            'file_path' => $filePath,
            'options' => $uploadOptions
        ]);

        $cloudName = $this->config['cloud_name'];
        $apiKey = $this->config['api_key'];
        $apiSecret = $this->config['api_secret'];

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        $timestamp = time();

        // Convert boolean values to strings for Cloudinary API
        $cleanedOptions = [];
        foreach ($uploadOptions as $key => $value) {
            if (is_bool($value)) {
                $cleanedOptions[$key] = $value ? 'true' : 'false';
            } else {
                $cleanedOptions[$key] = $value;
            }
        }

        // Parameters that should NOT be included in signature
        $excludeFromSignature = ['api_key', 'file', 'signature', 'fetch_format', 'quality'];

        // Prepare signature parameters (only include upload options, not transformation options)
        $signatureParams = ['timestamp' => $timestamp];
        foreach ($cleanedOptions as $key => $value) {
            if (!in_array($key, $excludeFromSignature)) {
                $signatureParams[$key] = $value;
            }
        }

        // Generate signature
        ksort($signatureParams);
        $paramString = '';
        foreach ($signatureParams as $key => $value) {
            $paramString .= $key . '=' . $value . '&';
        }
        $paramString = rtrim($paramString, '&');
        $signature = sha1($paramString . $apiSecret);

        // Prepare final form data (includes all parameters)
        $postData = [
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
        $postData = array_merge($postData, $cleanedOptions);

        \Log::info('CloudinaryService: Generated signature data', [
            'signature_params' => array_keys($signatureParams),
            'param_string' => $paramString,
            'signature' => $signature
        ]);

        // Add file to post data
        $postData['file'] = new \CURLFile($filePath);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Disable SSL verification for local development
        if (app()->environment('local')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Log::error('CloudinaryService: cURL error', ['error' => $error]);
            throw new Exception('Upload failed: ' . $error);
        }

        if ($httpCode !== 200) {
            \Log::error('CloudinaryService: HTTP error', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new Exception('Upload failed with HTTP code: ' . $httpCode);
        }

        $result = json_decode($response, true);

        if (!$result) {
            \Log::error('CloudinaryService: Invalid JSON response', ['response' => $response]);
            throw new Exception('Invalid response from Cloudinary');
        }

        \Log::info('CloudinaryService: Custom cURL upload successful', ['result' => $result]);

        return $result;
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