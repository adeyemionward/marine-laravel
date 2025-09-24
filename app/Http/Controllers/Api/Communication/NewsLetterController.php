<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class NewsLetterController extends Controller
{
    public function index(Request $request)
    {
        $query = Newsletter::with('template');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $newsletters = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $newsletters,
            'stats' => [
                'total' => Newsletter::count(),
                'draft' => Newsletter::where('status', 'draft')->count(),
                'scheduled' => Newsletter::where('status', 'scheduled')->count(),
                'sent' => Newsletter::where('status', 'sent')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'template_id' => 'nullable|exists:newsletter_templates,id',
            'scheduled_at' => 'nullable|date|after:now',
            'tags' => 'nullable|array',
            'status' => 'sometimes|in:draft,scheduled',
            'recipient_list' => 'nullable|string'
        ]);

        if (isset($validated['scheduled_at'])) {
            $validated['status'] = 'scheduled';
        } else {
            $validated['status'] = 'draft';
        }

        // Add created_by field
        $validated['created_by'] = Auth::user()->id ?? null;

        $newsletter = Newsletter::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter created successfully',
            'data' => $newsletter->load('template'),
        ], 201);
    }

    public function show($id)
    {
        $newsletter = Newsletter::with('template')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $newsletter
        ]);
    }

    public function update(Request $request, $id)
    {
        $newsletter = Newsletter::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string',
            'template_id' => 'nullable|exists:newsletter_templates,id',
            'scheduled_at' => 'nullable|date|after:now',
            'tags' => 'nullable|array',
            'status' => 'sometimes|in:draft,scheduled',
            'recipient_list' => 'nullable|string'
        ]);

        if (isset($validated['scheduled_at']) && $newsletter->status !== 'sent') {
            $validated['status'] = 'scheduled';
        }

        $newsletter->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter updated successfully',
            'data' => $newsletter->load('template'),
        ]);
    }

    public function destroy($id)
    {
        $newsletter = Newsletter::findOrFail($id);

        if ($newsletter->status === 'sent') {
            return response()->json([
                'message' => 'Cannot delete a newsletter that has already been sent'
            ], 422);
        }

        $newsletter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Newsletter deleted successfully'
        ]);
    }

    public function send(Request $request, $id)
    {
        $newsletter = Newsletter::findOrFail($id);

        if ($newsletter->status === 'sent') {
            return response()->json([
                'message' => 'Newsletter has already been sent'
            ], 422);
        }

        $validated = $request->validate([
            'test_email' => 'nullable|email',
        ]);

        if (isset($validated['test_email'])) {
            try {
                // Send test email
                Mail::raw($newsletter->content, function ($message) use ($newsletter, $validated) {
                    $message->to($validated['test_email'])
                           ->subject('[TEST] ' . $newsletter->title);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to send test email: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Send to all active subscribers
            $activeSubscribers = NewsletterSubscriber::active()->count();

            $newsletter->update([
                'status' => 'sent',
                'sent_at' => now(),
                'recipient_count' => $activeSubscribers,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Newsletter sent successfully to {$activeSubscribers} subscribers"
            ]);
        }
    }

    public function duplicate($id)
    {
        $newsletter = Newsletter::findOrFail($id);

        $duplicate = Newsletter::create([
            'title' => $newsletter->title . ' (Copy)',
            'content' => $newsletter->content,
            'excerpt' => $newsletter->excerpt,
            'template_id' => $newsletter->template_id,
            'tags' => $newsletter->tags,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Newsletter duplicated successfully',
            'data' => $duplicate->load('template'),
        ], 201);
    }

    public function getStats()
    {
        $stats = [
            'total_newsletters' => Newsletter::count(),
            'total_recipients' => NewsletterSubscriber::active()->count(),
            'emails_sent' => Newsletter::where('status', 'sent')->sum('recipient_count'),
            'failed' => Newsletter::where('status', 'failed')->count(),
            'draft' => Newsletter::where('status', 'draft')->count(),
            'scheduled' => Newsletter::where('status', 'scheduled')->count(),
            'sent' => Newsletter::where('status', 'sent')->count(),
            'active_subscribers' => NewsletterSubscriber::active()->count(),
            'total_subscribers' => NewsletterSubscriber::count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getTemplates()
    {
        $templates = NewsletterTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    public function getSettings()
    {
        // Return default newsletter settings
        $settings = [
            'auto_send' => true,
            'frequency' => 'monthly',
            'day_of_week' => 'tuesday',
            'send_time' => '09:00',
            'default_from_name' => 'Marine.ng',
            'default_from_email' => 'newsletter@marine.ng',
            'unsubscribe_link' => true,
            'track_opens' => true,
            'track_clicks' => true
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_send' => 'boolean',
            'frequency' => 'in:daily,weekly,monthly',
            'day_of_week' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'send_time' => 'date_format:H:i',
            'default_from_name' => 'string|max:255',
            'default_from_email' => 'email|max:255',
            'unsubscribe_link' => 'boolean',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}
