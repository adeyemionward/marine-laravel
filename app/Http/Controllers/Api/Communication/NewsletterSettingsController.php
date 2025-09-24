<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSettings;
use Illuminate\Http\Request;

class NewsletterSettingsController extends Controller
{
    public function index()
    {
        $settings = NewsletterSettings::first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = NewsletterSettings::create([
                'auto_send_enabled' => false,
                'send_frequency' => 'weekly',
                'send_time' => '09:00:00',
                'send_days' => [1, 2, 3, 4, 5], // Monday to Friday
                'open_tracking' => true,
                'click_tracking' => true,
                'bounce_tracking' => true,
            ]);
        }

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'auto_send_enabled' => 'boolean',
            'send_frequency' => 'sometimes|in:daily,weekly,monthly',
            'send_time' => 'sometimes|date_format:H:i:s',
            'send_days' => 'nullable|array',
            'send_days.*' => 'integer|between:1,7',
            'send_day_of_month' => 'nullable|integer|between:1,31',
            'open_tracking' => 'boolean',
            'click_tracking' => 'boolean',
            'bounce_tracking' => 'boolean',
            'unsubscribe_url' => 'nullable|url',
            'footer_text' => 'nullable|string',
        ]);

        $settings = NewsletterSettings::first();

        if (!$settings) {
            $settings = NewsletterSettings::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'message' => 'Newsletter settings updated successfully',
            'data' => $settings,
        ]);
    }

    public function getAutomationStatus()
    {
        $settings = NewsletterSettings::first();

        return response()->json([
            'automation_enabled' => $settings ? $settings->auto_send_enabled : false,
            'frequency' => $settings ? $settings->send_frequency : 'weekly',
            'next_send' => $this->calculateNextSend($settings),
        ]);
    }

    public function toggleAutomation(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $settings = NewsletterSettings::first();

        if (!$settings) {
            $settings = NewsletterSettings::create([
                'auto_send_enabled' => $validated['enabled'],
                'send_frequency' => 'weekly',
                'send_time' => '09:00:00',
                'send_days' => [1, 2, 3, 4, 5],
                'open_tracking' => true,
                'click_tracking' => true,
                'bounce_tracking' => true,
            ]);
        } else {
            $settings->update(['auto_send_enabled' => $validated['enabled']]);
        }

        return response()->json([
            'message' => 'Automation settings updated successfully',
            'data' => $settings,
        ]);
    }

    private function calculateNextSend($settings)
    {
        if (!$settings || !$settings->auto_send_enabled) {
            return null;
        }

        $now = now();
        $sendTime = $settings->send_time;

        switch ($settings->send_frequency) {
            case 'daily':
                $nextSend = $now->copy()->addDay()->setTimeFromTimeString($sendTime);
                break;

            case 'weekly':
                $sendDays = $settings->send_days ?? [1]; // Default to Monday
                $today = $now->dayOfWeek;
                $nextDay = null;

                foreach ($sendDays as $day) {
                    if ($day > $today) {
                        $nextDay = $day;
                        break;
                    }
                }

                if (!$nextDay) {
                    $nextDay = min($sendDays);
                    $nextSend = $now->copy()->addWeek()->startOfWeek()->addDays($nextDay - 1)->setTimeFromTimeString($sendTime);
                } else {
                    $nextSend = $now->copy()->startOfWeek()->addDays($nextDay - 1)->setTimeFromTimeString($sendTime);
                }
                break;

            case 'monthly':
                $sendDay = $settings->send_day_of_month ?? 1;
                $nextSend = $now->copy()->addMonth()->startOfMonth()->addDays($sendDay - 1)->setTimeFromTimeString($sendTime);
                break;

            default:
                $nextSend = null;
        }

        return $nextSend ? $nextSend->toISOString() : null;
    }
}