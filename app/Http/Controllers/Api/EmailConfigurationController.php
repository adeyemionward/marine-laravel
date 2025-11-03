<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailConfigurationController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $settings = EmailSetting::first();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'driver' => 'required|in:smtp,gmail,outlook,custom',
                'host' => 'required_if:driver,smtp,custom|string',
                'port' => 'required_if:driver,smtp,custom|integer|min:1|max:65535',
                'username' => 'required|string',
                'password' => 'required|string',
                'encryption' => 'required|in:tls,ssl,null',
                'from_email' => 'required|email',
                'from_name' => 'required|string|max:255',
                'use_tls' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Set default host and port based on driver
            if ($data['driver'] === 'gmail') {
                $data['host'] = 'smtp.gmail.com';
                $data['port'] = 587;
                $data['encryption'] = 'tls';
            } elseif ($data['driver'] === 'outlook') {
                $data['host'] = 'smtp-mail.outlook.com';
                $data['port'] = 587;
                $data['encryption'] = 'tls';
            }

            // Deactivate existing settings
            EmailSetting::query()->update(['is_active' => false]);

            // Create or update settings
            $settings = EmailSetting::create(array_merge($data, ['is_active' => false]));

            return response()->json([
                'success' => true,
                'message' => 'Email configuration saved successfully',
                'data' => $settings,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save email configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $settings = EmailSetting::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'driver' => 'sometimes|in:smtp,gmail,outlook,custom',
                'host' => 'sometimes|string',
                'port' => 'sometimes|integer|min:1|max:65535',
                'username' => 'sometimes|string',
                'password' => 'sometimes|string',
                'encryption' => 'sometimes|in:tls,ssl,null',
                'from_email' => 'sometimes|email',
                'from_name' => 'sometimes|string|max:255',
                'use_tls' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // If activating this configuration, deactivate others
            if (isset($data['is_active']) && $data['is_active']) {
                EmailSetting::where('id', '!=', $id)->update(['is_active' => false]);
            }

            $settings->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Email configuration updated successfully',
                'data' => $settings->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testConfiguration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_email' => 'required|email',
                'settings_id' => 'sometimes|integer|exists:email_settings,id',
                // Allow testing with provided settings without saving
                'driver' => 'required_without:settings_id|in:smtp,gmail,outlook,custom',
                'host' => 'required_without:settings_id|string',
                'port' => 'required_without:settings_id|integer',
                'username' => 'required_without:settings_id|string',
                'password' => 'required_without:settings_id|string',
                'encryption' => 'required_without:settings_id|in:tls,ssl,null',
                'from_email' => 'required_without:settings_id|email',
                'from_name' => 'required_without:settings_id|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $testEmail = $request->test_email;

            // Get settings either from database or request
            if ($request->filled('settings_id')) {
                $settings = EmailSetting::findOrFail($request->settings_id);
                $config = [
                    'driver' => $settings->driver,
                    'host' => $settings->host,
                    'port' => $settings->port,
                    'username' => $settings->username,
                    'password' => $settings->password,
                    'encryption' => $settings->encryption,
                    'from_email' => $settings->from_email,
                    'from_name' => $settings->from_name,
                ];
            } else {
                $config = $request->only([
                    'driver', 'host', 'port', 'username', 'password',
                    'encryption', 'from_email', 'from_name'
                ]);
            }

            // Set default host and port for predefined drivers
            if ($config['driver'] === 'gmail') {
                $config['host'] = 'smtp.gmail.com';
                $config['port'] = 587;
                $config['encryption'] = 'tls';
            } elseif ($config['driver'] === 'outlook') {
                $config['host'] = 'smtp-mail.outlook.com';
                $config['port'] = 587;
                $config['encryption'] = 'tls';
            }

            // Configure mail for testing
            config([
                'mail.mailers.test_smtp' => [
                    'transport' => 'smtp',
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'timeout' => null,
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                ],
                'mail.from' => [
                    'address' => $config['from_email'],
                    'name' => $config['from_name'],
                ],
            ]);

            // Send test email with better error handling
            try {
                Mail::mailer('test_smtp')->send('emails.test', [], function ($message) use ($testEmail, $config) {
                    $message->to($testEmail)
                        ->subject('SMTP Configuration Test - Marine.ng')
                        ->from($config['from_email'], $config['from_name']);
                });
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                throw new \Exception('SMTP Connection Failed: ' . $e->getMessage() . '. Please check your network connection and SMTP settings.');
            } catch (\Exception $e) {
                throw new \Exception('Email sending failed: ' . $e->getMessage());
            }

            // Update test results if settings exist
            if ($request->filled('settings_id')) {
                $settings->update([
                    'tested_at' => now(),
                    'test_result' => 'success',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);
        } catch (\Exception $e) {
            // Update test results if settings exist
            if ($request->filled('settings_id')) {
                $settings = EmailSetting::find($request->settings_id);
                if ($settings) {
                    $settings->update([
                        'tested_at' => now(),
                        'test_result' => 'failed: ' . $e->getMessage(),
                    ]);
                }
            }

            $errorMessage = $e->getMessage();
            $suggestions = [];

            // Provide specific suggestions based on error type
            if (strpos($errorMessage, 'Connection could not be established') !== false) {
                $suggestions[] = 'Check if your server can make outgoing connections on port 587';
                $suggestions[] = 'Verify your firewall settings allow SMTP connections';
                $suggestions[] = 'Try using port 465 with SSL instead of port 587 with TLS';
            }

            if (strpos($errorMessage, 'gmail.com') !== false) {
                $suggestions[] = 'Ensure 2-Factor Authentication is enabled in your Gmail account';
                $suggestions[] = 'Use an App Password instead of your regular Gmail password';
                $suggestions[] = 'Check if "Less secure app access" is enabled (not recommended)';
            }

            return response()->json([
                'success' => false,
                'message' => 'Test email failed',
                'error' => $errorMessage,
                'suggestions' => $suggestions
            ], 422);
        }
    }

    public function getStatus(): JsonResponse
    {
        try {
            $activeSettings = EmailSetting::active()->first();
            $totalConfigurations = EmailSetting::count();

            $status = [
                'is_configured' => !is_null($activeSettings),
                'active_driver' => $activeSettings->driver ?? null,
                'total_configurations' => $totalConfigurations,
                'last_tested' => $activeSettings->tested_at ?? null,
                'test_status' => $activeSettings->last_test_status ?? 'not_tested',
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get email configuration status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $settings = EmailSetting::findOrFail($id);
            $settings->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email configuration deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete email configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function test($id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $settings = EmailSetting::findOrFail($id);
            $testEmail = $request->test_email;

            $config = [
                'driver' => $settings->driver,
                'host' => $settings->host,
                'port' => $settings->port,
                'username' => $settings->username,
                'password' => $settings->password,
                'encryption' => $settings->encryption,
                'from_email' => $settings->from_email,
                'from_name' => $settings->from_name,
            ];

            // Set default host and port for predefined drivers
            if ($config['driver'] === 'gmail') {
                $config['host'] = 'smtp.gmail.com';
                $config['port'] = 587;
                $config['encryption'] = 'tls';
            } elseif ($config['driver'] === 'outlook') {
                $config['host'] = 'smtp-mail.outlook.com';
                $config['port'] = 587;
                $config['encryption'] = 'tls';
            }

            // Configure mail for testing
            config([
                'mail.mailers.test_smtp' => [
                    'transport' => 'smtp',
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'timeout' => null,
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                ],
                'mail.from' => [
                    'address' => $config['from_email'],
                    'name' => $config['from_name'],
                ],
            ]);

            // Send test email with better error handling
            try {
                Mail::mailer('test_smtp')->send('emails.test', [], function ($message) use ($testEmail, $config) {
                    $message->to($testEmail)
                        ->subject('SMTP Configuration Test - Marine.ng')
                        ->from($config['from_email'], $config['from_name']);
                });
            } catch (\Swift_TransportException $e) {
                throw new \Exception('SMTP Connection Failed: ' . $e->getMessage() . '. Please check your network connection and SMTP settings.');
            } catch (\Exception $e) {
                throw new \Exception('Email sending failed: ' . $e->getMessage());
            }

            // Update test results
            $settings->update([
                'tested_at' => now(),
                'test_result' => 'success',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);
        } catch (\Exception $e) {
            // Update test results
            $settings = EmailSetting::find($id);
            if ($settings) {
                $settings->update([
                    'tested_at' => now(),
                    'test_result' => 'failed: ' . $e->getMessage(),
                ]);
            }

            $errorMessage = $e->getMessage();
            $suggestions = [];

            // Provide specific suggestions based on error type
            if (strpos($errorMessage, 'Connection could not be established') !== false) {
                $suggestions[] = 'Check if your server can make outgoing connections on port 587';
                $suggestions[] = 'Verify your firewall settings allow SMTP connections';
                $suggestions[] = 'Try using port 465 with SSL instead of port 587 with TLS';
            }

            if (strpos($errorMessage, 'gmail.com') !== false) {
                $suggestions[] = 'Ensure 2-Factor Authentication is enabled in your Gmail account';
                $suggestions[] = 'Use an App Password instead of your regular Gmail password';
                $suggestions[] = 'Check if "Less secure app access" is enabled (not recommended)';
            }

            return response()->json([
                'success' => false,
                'message' => 'Test email failed',
                'error' => $errorMessage,
                'suggestions' => $suggestions
            ], 422);
        }
    }
}