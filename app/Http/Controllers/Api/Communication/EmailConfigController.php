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
            'smtp_host'   => 'required|string',
            'smtp_port'   => 'required|integer',
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
            , $validated);

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


    public function test(Request $request)
    {
            $validated = $request->validate([
                'test_email' => 'required|email',
            ]);

            $config = EmailConfig::first();
            if (!$config) {
                return response()->json(['message' => 'No SMTP configuration found'], 404);
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
                'mail.from.name' => $config->from_name ?? 'System',
            ]);

            try {
                Mail::raw('This is a test email from Marine.ng SMTP configuration.', function ($message) use ($validated) {
                    $message->to($validated['test_email'])->subject('Test Email Configuration');
                });

                return response()->json(['message' => 'Test email sent successfully']);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to send test email',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

    }
