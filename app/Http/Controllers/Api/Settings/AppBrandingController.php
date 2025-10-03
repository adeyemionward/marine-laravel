<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppBranding;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppBrandingController extends Controller
{
    private $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        $this->fileStorageService = $fileStorageService;
    }

    public function getAppName()
    {
        $config = AppBranding::first();

        // Transform the data to match frontend expectations
        $data = $config ? [
            'app_name' => $config->app_name,
            'logoUrl' => $config->primary_logo,
            'adminLogoUrl' => $config->admin_logo,
            'logoAlt' => $config->app_name . ' Logo',
            'adminLogoAlt' => $config->app_name . ' Admin Logo',
        ] : null;

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get public branding settings (no auth required)
     */
    public function getPublicBranding()
    {
        $config = AppBranding::first();

        $data = [
            'appName' => $config->app_name ?? 'Marine.ng',
            'logoUrl' => $config->primary_logo ?? '/assets/images/marine.ng_logo_2026-1754742671588.png',
            'adminLogoUrl' => $config->admin_logo ?? '/assets/images/marine.ng_logo_2026-1754742671588.png',
            'logoAlt' => ($config->app_name ?? 'Marine.ng') . ' Logo',
            'adminLogoAlt' => ($config->app_name ?? 'Marine.ng') . ' Admin Logo',
        ];

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Update Application Name
     */
   public function updateAppName(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255'
        ]);

        $app = AppBranding::first();

        if ($app) {
            $app->app_name = $request->app_name;
            $app->save();
        } else {
            $app = AppBranding::create([
                'app_name' => $request->app_name
            ]);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'App name updated',
            'data' => $app
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo_type' => 'required|string|in:primary,admin',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:10240', // 10MB max
            'folder' => 'sometimes|string|in:equipment,profiles,documents,banners,general,logos'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $folder = $request->get('folder', 'logos');
            $options = $request->only(['public_id', 'tags']);

            if (!$request->hasFile('logo')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No logo file provided'
                ], 400);
            }

            // Upload the file
            $result = $this->fileStorageService->uploadImage(
                $request->file('logo'),
                $folder,
                $options
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Upload failed',
                    'error' => $result['error']
                ], 400);
            }

            // Save to AppBranding
            $config = AppBranding::firstOrCreate([]);
            $type = $request->logo_type;

            if ($type === 'primary') {
                $config->primary_logo = $result['data']['url'];
            } elseif ($type === 'admin') {
                $config->admin_logo = $result['data']['url'];
            }
            $config->save();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => ucfirst($type) . ' logo uploaded successfully',
                'logo_url' => $result['data']['url'],
                'details' => $result['data']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset Logo (Primary or Admin)
     */
    public function resetLogo(Request $request)
    {
        $config = AppBranding::first();
        $type = $request->logo_type;
        if ($type === 'primary') {
            $config->primary_logo = NULL;
        } elseif ($type === 'admin') {
            $config->admin_logo = NULL;
        }
        $config->save();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => ucfirst($type) . ' logo reset'
        ]);
    }
}
