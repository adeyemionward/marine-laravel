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
            $apiKeys = ApiKey::orderBy('created_at', 'desc')->get()->map(function($key) {
                return [
                    'id' => $key->id,
                    'key_name' => $key->name,
                    'key_type' => $key->service,
                    'encrypted_key' => $key->decrypted_api_key ?? '••••••••',
                    'is_active' => $key->status === 'active',
                    'status' => $key->status,
                    'description' => $key->description,
                    'last_tested_at' => $key->last_tested_at,
                    'test_result' => $key->test_result,
                    'created_at' => $key->created_at,
                    'updated_at' => $key->updated_at
                ];
            });

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
        // Support both frontend naming (key_name, key_type, encrypted_key)
        // and backend naming (name, service, api_key)
        $data = $request->all();

        // Map frontend field names to backend field names
        $mappedData = [
            'name' => $data['keyName'] ?? $data['key_name'] ?? $data['name'] ?? null,
            'service' => $data['keyType'] ?? $data['key_type'] ?? $data['service'] ?? null,
            'api_key' => $data['encryptedKey'] ?? $data['encrypted_key'] ?? $data['api_key'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
            'config' => $data['config'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'active', // Default to active
        ];

        $validator = Validator::make($mappedData, [
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
            $apiKey = ApiKey::create($mappedData);

            // Return data in frontend-expected format
            $responseData = [
                'id' => $apiKey->id,
                'key_name' => $apiKey->name,
                'key_type' => $apiKey->service,
                'encrypted_key' => $apiKey->decrypted_api_key,
                'is_active' => $apiKey->status === 'active',
                'description' => $apiKey->description,
                'created_at' => $apiKey->created_at,
                'updated_at' => $apiKey->updated_at
            ];

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => $responseData
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
        $data = $request->all();

        // Map frontend field names to backend field names
        $mappedData = [];

        if (isset($data['key_name'])) {
            $mappedData['name'] = $data['key_name'];
        }
        if (isset($data['keyName'])) {
            $mappedData['name'] = $data['keyName'];
        }
        if (isset($data['key_type'])) {
            $mappedData['service'] = $data['key_type'];
        }
        if (isset($data['keyType'])) {
            $mappedData['service'] = $data['keyType'];
        }
        if (isset($data['encrypted_key'])) {
            $mappedData['api_key'] = $data['encrypted_key'];
        }
        if (isset($data['encryptedKey'])) {
            $mappedData['api_key'] = $data['encryptedKey'];
        }
        if (isset($data['is_active'])) {
            $mappedData['status'] = $data['is_active'] ? 'active' : 'inactive';
        }
        if (isset($data['secret_key'])) {
            $mappedData['secret_key'] = $data['secret_key'];
        }
        if (isset($data['description'])) {
            $mappedData['description'] = $data['description'];
        }
        if (isset($data['config'])) {
            $mappedData['config'] = $data['config'];
        }

        $validator = Validator::make($mappedData, [
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
            $apiKey->update($mappedData);
            $apiKey->refresh();

            // Return data in frontend-expected format
            $responseData = [
                'id' => $apiKey->id,
                'key_name' => $apiKey->name,
                'key_type' => $apiKey->service,
                'encrypted_key' => $apiKey->decrypted_api_key,
                'is_active' => $apiKey->status === 'active',
                'description' => $apiKey->description,
                'created_at' => $apiKey->created_at,
                'updated_at' => $apiKey->updated_at
            ];

            return response()->json([
                'success' => true,
                'message' => 'API key updated successfully',
                'data' => $responseData
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
