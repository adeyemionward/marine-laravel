<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\EmailConfig;
use Illuminate\Http\Request;
use App\Models\EmailConfiguration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class EmailConfigController extends Controller
{


    public function index()
    {
        $config = EmailConfig::all();
        return response()->json($config);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver'      => 'required|string|in:gmail,outlook,custom',
            'host'   => 'required|string',
            'port'   => 'required|integer',
            'username'    => 'required|email',
            'password'    => 'required|string',
            'from_email'  => 'required|email',
            'from_name'   => 'nullable|string|max:255',
            'encryption'  => 'nullable|string|in:tls,ssl,null',
            'enable_smtp' => 'boolean',
        ]);

        // encrypt password before saving
        $validated['password'] = Crypt::encryptString($validated['password']);

        // only one config allowed, so upsert
        $config = EmailConfig::updateOrCreate(
            ['driver' => $validated['driver']]
            , [
                'smtp_host' => $validated['host'],
                'smtp_port' => $validated['port'],
                'username' => $validated['username'],
                'password' => $validated['password'],
                'from_email' => $validated['from_email'],
                'from_name' => $validated['from_name'],
                'encryption' => $validated['encryption'],
                'enable_smtp' => $validated['enable_smtp'],
            ]);

        return response()->json([
            'message' => 'Email configuration saved successfully',
            'data'    => $config,
        ], 201);
    }

    public function show($driver)
    {
        $config = EmailConfig::where('driver', $driver)->first();

        if (!$config) {
            return response()->json(['message' => "No configuration found for driver: $driver"], 200);
        }

        return response()->json($config);
    }


    public function update(Request $request, $driver)
    {
        $validated = $request->validate([
            'smtp_host'   => 'sometimes|string',
            'smtp_port'   => 'sometimes|integer',
            'username'    => 'sometimes|email',
            'password'    => 'sometimes|string',
            'from_email'  => 'sometimes|email',
            'from_name'   => 'nullable|string|max:255',
            'encryption'  => 'nullable|string|in:tls,ssl,null',
            'enable_smtp' => 'sometimes|boolean',
        ]);

        $config = EmailConfig::where('driver', $driver)->first();

        if (!$config) {
            return response()->json(['message' => "No configuration found for driver: $driver"], 404);
        }

        // If password is provided, encrypt it
        if (isset($validated['password'])) {
            $validated['password'] = Crypt::encryptString($validated['password']);
        }

        // Update with proper SMTP settings for known drivers
        if ($driver === 'gmail') {
            $validated['smtp_host'] = 'smtp.gmail.com';
            $validated['smtp_port'] = 587;
            $validated['encryption'] = 'tls';
        } elseif ($driver === 'outlook') {
            $validated['smtp_host'] = 'smtp.office365.com';
            $validated['smtp_port'] = 587;
            $validated['encryption'] = 'tls';
        }

        $config->update($validated);

        return response()->json([
            'message' => 'Email configuration updated successfully',
            'data'    => $config,
        ]);
    }

    public function test(Request $request, $driver)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $config = EmailConfig::where('driver', $driver)->first();

        if (!$config) {
            return response()->json(['message' => "No configuration found for driver: $driver"], 404);
        }

        // Determine SMTP settings based on driver
        switch ($config->driver) {
            case 'gmail':
                $smtpHost = 'smtp.gmail.com';
                $smtpPort = 587;
                $encryption = 'tls';
                break;

            case 'outlook':
                $smtpHost = 'smtp.office365.com';
                $smtpPort = 587;
                $encryption = 'tls';
                break;

            case 'custom':
            default:
                $smtpHost = $config->smtp_host;
                $smtpPort = $config->smtp_port;
                $encryption = $config->encryption ?? 'tls';
                break;
        }

        // temporarily set mail config
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $smtpHost,
            'mail.mailers.smtp.port' => $smtpPort,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => $config->username,
            'mail.mailers.smtp.password' => Crypt::decryptString($config->password),
            'mail.from.address' => $config->from_email,
            'mail.from.name' => $config->from_name ?? 'Marine.ng System',
        ]);

        try {
            Mail::raw('This is a test email from Marine.ng SMTP configuration.', function ($message) use ($validated, $config) {
                $message->to($validated['test_email'])
                    ->subject('Test Email Configuration - Marine.ng');
            });

            return response()->json([
                'message' => 'Test email sent successfully',
                'sent_to' => $validated['test_email'],
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort
            ]);
        } catch (\Exception $e) {
            // Provide helpful error messages for common issues
            $errorMessage = $e->getMessage();
            $helpfulHint = '';

            if (strpos($errorMessage, 'Authentication unsuccessful') !== false ||
                strpos($errorMessage, 'basic authentication is disabled') !== false) {
                $helpfulHint = ' For Gmail, use an App Password instead of your regular password. Go to Google Account settings > Security > 2-Step Verification > App passwords.';
            }

            return response()->json([
                'message' => 'Failed to send test email',
                'error' => $errorMessage,
                'hint' => $helpfulHint,
                'smtp_settings' => [
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'encryption' => $encryption
                ]
            ], 500);
        }
    }
}
