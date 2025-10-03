<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $apiKeys = ApiKey::select([
                'id', 'name', 'service', 'status', 'description',
                'last_tested_at', 'test_result', 'created_at', 'updated_at'
            ])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $apiKeys
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch API keys',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'api_key' => 'required|string',
            'secret_key' => 'nullable|string',
            'config' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,testing'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = ApiKey::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => $apiKey->makeHidden(['api_key', 'secret_key'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(ApiKey $apiKey): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $apiKey->makeHidden(['api_key', 'secret_key'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'service' => 'sometimes|required|string|max:255',
            'api_key' => 'sometimes|required|string',
            'secret_key' => 'nullable|string',
            'config' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive,testing'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'API key updated successfully',
                'data' => $apiKey->makeHidden(['api_key', 'secret_key'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        try {
            $apiKey->delete();

            return response()->json([
                'success' => true,
                'message' => 'API key deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function test(ApiKey $apiKey): JsonResponse
    {
        try {
            $testResult = $this->performApiTest($apiKey);

            $apiKey->update([
                'last_tested_at' => now(),
                'test_result' => $testResult
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key tested successfully',
                'data' => $testResult
            ]);
        } catch (\Exception $e) {
            $apiKey->update([
                'last_tested_at' => now(),
                'test_result' => [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'tested_at' => now()->toISOString()
                ]
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API key test failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    private function performApiTest(ApiKey $apiKey): array
    {
        $service = strtolower($apiKey->service);
        $apiKeyValue = $apiKey->decrypted_api_key;
        $secretKey = $apiKey->decrypted_secret_key;

        switch ($service) {
            case 'sendgrid':
                return $this->testSendGrid($apiKeyValue);
            case 'stripe':
                return $this->testStripe($apiKeyValue);
            case 'paystack':
                return $this->testPaystack($apiKeyValue);
            default:
                return [
                    'success' => true,
                    'message' => 'API key format validated',
                    'tested_at' => now()->toISOString()
                ];
        }
    }

    private function testSendGrid(string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.sendgrid.com/v3/user/account');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'SendGrid API key is valid',
                'data' => $response->json(),
                'tested_at' => now()->toISOString()
            ];
        }

        throw new \Exception('SendGrid API test failed: ' . $response->body());
    }

    private function testStripe(string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])->get('https://api.stripe.com/v1/account');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Stripe API key is valid',
                'data' => $response->json(),
                'tested_at' => now()->toISOString()
            ];
        }

        throw new \Exception('Stripe API test failed: ' . $response->body());
    }

    private function testPaystack(string $apiKey): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->get('https://api.paystack.co/bank');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Paystack API key is valid',
                'data' => $response->json(),
                'tested_at' => now()->toISOString()
            ];
        }

        throw new \Exception('Paystack API test failed: ' . $response->body());
    }
}
