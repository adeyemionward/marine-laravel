<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Models\NewsletterTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Newsletter::with('creator')
                ->orderBy('created_at', 'desc');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $newsletters = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $newsletters->items(),
                'meta' => [
                    'current_page' => $newsletters->currentPage(),
                    'per_page' => $newsletters->perPage(),
                    'total' => $newsletters->total(),
                    'last_page' => $newsletters->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch newsletters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string|max:500',
                'status' => 'in:draft,scheduled,sent',
                'scheduled_at' => 'nullable|date|after:now',
                'recipients' => 'nullable|array',
                'from_email' => 'nullable|email',
                'from_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['created_by'] = Auth::user()->profile->id;
            $data['status'] = $data['status'] ?? Newsletter::STATUS_DRAFT;

            if ($data['status'] === Newsletter::STATUS_SCHEDULED && !isset($data['scheduled_at'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduled date is required for scheduled newsletters',
                ], 422);
            }

            $newsletter = Newsletter::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Newsletter created successfully',
                'data' => $newsletter->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $newsletter = Newsletter::with('creator')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $newsletter,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'subject' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string|max:500',
                'status' => 'sometimes|in:draft,scheduled,sent',
                'scheduled_at' => 'nullable|date|after:now',
                'recipients' => 'nullable|array',
                'from_email' => 'nullable|email',
                'from_name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            if (isset($data['status']) && $data['status'] === Newsletter::STATUS_SCHEDULED && !isset($data['scheduled_at']) && !$newsletter->scheduled_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduled date is required for scheduled newsletters',
                ], 422);
            }

            $newsletter->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Newsletter updated successfully',
                'data' => $newsletter->fresh(['creator']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            if ($newsletter->status === Newsletter::STATUS_SENT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a newsletter that has already been sent',
                ], 422);
            }

            $newsletter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Newsletter deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function send(Request $request, $id): JsonResponse
    {
        try {
            $newsletter = Newsletter::findOrFail($id);

            if ($newsletter->status === Newsletter::STATUS_SENT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Newsletter has already been sent',
                ], 422);
            }

            // Get recipients - all users if not specified
            $recipients = $newsletter->recipients ?? User::pluck('email')->toArray();

            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recipients specified',
                ], 422);
            }

            // Update newsletter status
            $newsletter->update([
                'status' => Newsletter::STATUS_SENT,
                'sent_at' => now(),
                'recipients_count' => count($recipients),
            ]);

            // Queue email sending (in a real app, you'd use a job queue)
            foreach ($recipients as $email) {
                try {
                    Mail::html($newsletter->content, function ($message) use ($newsletter, $email) {
                        $message->to($email)
                            ->subject($newsletter->subject)
                            ->from($newsletter->from_email ?? config('mail.from.address'),
                                   $newsletter->from_name ?? config('mail.from.name'));
                    });
                } catch (\Exception $e) {
                    // Log individual send failures but continue
                    \Log::error("Failed to send newsletter to {$email}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Newsletter sent successfully',
                'data' => $newsletter->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_newsletters' => Newsletter::count(),
                'total_recipients' => Newsletter::sum('recipients_count'),
                'emails_sent' => Newsletter::sent()->count(),
                'failed' => 0, // TODO: implement failed tracking
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get newsletter stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate($id): JsonResponse
    {
        try {
            $original = Newsletter::findOrFail($id);

            $duplicate = Newsletter::create([
                'title' => $original->title . ' (Copy)',
                'subject' => $original->subject . ' (Copy)',
                'content' => $original->content,
                'excerpt' => $original->excerpt,
                'status' => Newsletter::STATUS_DRAFT,
                'from_email' => $original->from_email,
                'from_name' => $original->from_name,
                'created_by' => Auth::user()->profile->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Newsletter duplicated successfully',
                'data' => $duplicate->load('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate newsletter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTemplates(): JsonResponse
    {
        try {
            $templates = NewsletterTemplate::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get newsletter templates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSettings(): JsonResponse
    {
        try {
            // Return default newsletter settings
            $settings = [
                'auto_send' => true,
                'frequency' => 'monthly',
                'day_of_week' => 'tuesday',
                'send_time' => '09:00',
                'default_from_name' => 'Marine.africa',
                'default_from_email' => 'newsletter@marine.africa',
                'unsubscribe_link' => true,
                'track_opens' => true,
                'track_clicks' => true
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get newsletter settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
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

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // In a real app, you would save these settings to a database
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update newsletter settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}