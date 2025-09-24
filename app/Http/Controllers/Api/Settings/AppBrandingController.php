<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppBrandingController extends Controller
{
    public function getConfig()
    {
        $config = AppBranding::first();

        return response()->json([
            'status' => 'success',
            'config' => $config
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

        $config = AppBranding::first();
        $config->app_name = $request->app_name;
        $config->save();

        return response()->json(['status' => 'success', 'message' => 'App name updated']);
    }


    /**
     * Upload Logo (Primary or Admin)
     */
    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('logo');
        $path = $file->store('logos', 'public');
        $type = request('app_name');
        $config = AppBranding::first();
        if ($type === 'primary') {
            $config->primary_logo = '/storage/' . $path;
        } elseif ($type === 'admin') {
            $config->admin_logo = '/storage/' . $path;
        }
        $config->save();

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($type) . ' logo uploaded successfully',
            'logo_url' => '/storage/' . $path
        ]);
    }

    /**
     * Reset Logo (Primary or Admin)
     */
    public function resetLogo($type)
    {
        $config = AppBranding::first();
        if ($type === 'primary') {
            $config->primary_logo = 'default';
        } elseif ($type === 'admin') {
            $config->admin_logo = 'default';
        }
        $config->save();

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($type) . ' logo reset to default'
        ]);
    }
}
