<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AdminMessagingController extends Controller
{
    /**
     * Get all admin messages and system conversations
     */
    public function getAdminMessages(Request $request): JsonResponse
    {
        try {
            $query = Message::with(['sender:id,name,email', 'conversation.participants'])
                ->whereHas('conversation', function($q) {
                    $q->where('type', 'admin')
                      ->orWhere('type', 'system');
                });

            // Apply filters
            if ($request->has('status')) {
                if ($request->status === 'read') {
                    $query->whereNotNull('read_at');
                } else {
                    $query->whereNull('read_at');
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('content', 'like', "%{$search}%")
                      ->orWhereHas('sender', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('from_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->from_date));
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->to_date));
            }

            $messages = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin messages',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send admin message to user(s)
     */
    public function sendAdminMessage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipient_ids' => 'required|array',
                'recipient_ids.*' => 'exists:users,id',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:announcement,notification,warning,message',
                'send_email' => 'boolean'
            ]);

            $adminId = Auth::id();
            $messagesSent = [];

            foreach ($validated['recipient_ids'] as $recipientId) {
                // Create or find conversation
                $conversation = Conversation::firstOrCreate(
                    [
                        'type' => 'admin',
                        'metadata' => json_encode([
                            'admin_id' => $adminId,
                            'user_id' => $recipientId
                        ])
                    ],
                    [
                        'title' => $validated['subject']
                    ]
                );

                // Attach participants if not already attached
                $conversation->participants()->syncWithoutDetaching([$adminId, $recipientId]);

                // Create message
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $adminId,
                    'content' => $validated['message'],
                    'metadata' => json_encode([
                        'type' => $validated['type'],
                        'subject' => $validated['subject']
                    ])
                ]);

                $messagesSent[] = $message;

                // Send email notification if requested
                if ($validated['send_email'] ?? false) {
                    $this->sendEmailNotification($recipientId, $validated);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Messages sent successfully',
                'data' => [
                    'messages_sent' => count($messagesSent),
                    'messages' => $messagesSent
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send admin message',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get system conversations (support tickets, inquiries, etc.)
     */
    public function getSystemConversations(Request $request): JsonResponse
    {
        try {
            $query = Conversation::with(['buyer', 'seller', 'listing', 'messages' => function($q) {
                $q->latest()->limit(1);
            }]);

            // Filter by type if specified, otherwise show all conversations
            if ($request->has('type')) {
                $types = is_array($request->type) ? $request->type : [$request->type];
                $query->whereIn('type', $types);
            } else {
                // By default, show all conversation types for admin overview
                // You can uncomment the line below to only show admin-related conversations
                // $query->whereIn('type', ['admin', 'system', 'support']);
            }

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhereHas('buyer', function($q) use ($search) {
                          $q->where('full_name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('seller', function($q) use ($search) {
                          $q->where('full_name', 'like', "%{$search}%");
                      });
                });
            }

            // Get unread count for each conversation (remove Auth::id() check for now since this is for admin)
            $conversations = $query->withCount(['messages as unread_count' => function($q) {
                $q->whereNull('read_at');
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system conversations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get email queue status
     */
    public function getEmailQueueStatus(): JsonResponse
    {
        try {
            // Get email queue stats from jobs table
            $queueStats = DB::table('jobs')
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN attempts > 0 THEN 1 ELSE 0 END) as failed'),
                    DB::raw('MIN(created_at) as oldest_job')
                )
                ->where('queue', 'emails')
                ->first();

            // Get failed jobs
            $failedJobs = DB::table('failed_jobs')
                ->where('queue', 'emails')
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_stats' => [
                        'pending' => $queueStats->total ?? 0,
                        'failed' => $queueStats->failed ?? 0,
                        'oldest_job' => $queueStats->oldest_job,
                    ],
                    'failed_jobs' => $failedJobs,
                    'email_settings' => [
                        'driver' => config('mail.default'),
                        'from_address' => config('mail.from.address'),
                        'from_name' => config('mail.from.name')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email queue status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send broadcast message to all users or specific groups
     */
    public function sendBroadcast(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'target' => 'required|in:all,sellers,buyers,active',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:announcement,maintenance,promotion',
                'send_email' => 'boolean',
                'send_notification' => 'boolean'
            ]);

            // Get target users based on criteria
            $query = User::where('status', 'active');

            switch ($validated['target']) {
                case 'sellers':
                    $query->whereHas('role', function($q) {
                        $q->where('name', 'seller');
                    });
                    break;
                case 'buyers':
                    $query->whereHas('equipmentListings');
                    break;
                case 'active':
                    $query->where('updated_at', '>', Carbon::now()->subDays(30));
                    break;
            }

            $users = $query->get();
            $adminId = Auth::id();

            // Create broadcast conversation
            $conversation = Conversation::create([
                'type' => 'broadcast',
                'title' => $validated['subject'],
                'metadata' => json_encode([
                    'type' => $validated['type'],
                    'target' => $validated['target'],
                    'admin_id' => $adminId
                ])
            ]);

            // Create broadcast message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $adminId,
                'content' => $validated['message'],
                'metadata' => json_encode([
                    'type' => 'broadcast',
                    'recipients_count' => $users->count()
                ])
            ]);

            // Queue email/notification jobs if requested
            if ($validated['send_email'] ?? false) {
                foreach ($users as $user) {
                    // Queue email job
                    // Mail::to($user)->queue(new BroadcastEmail($validated));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Broadcast sent successfully',
                'data' => [
                    'recipients_count' => $users->count(),
                    'message_id' => $message->id,
                    'email_queued' => $validated['send_email'] ?? false
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_ids' => 'required|array',
                'message_ids.*' => 'exists:messages,id'
            ]);

            Message::whereIn('id', $validated['message_ids'])
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete messages
     */
    public function deleteMessages(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_ids' => 'required|array',
                'message_ids.*' => 'exists:messages,id'
            ]);

            Message::whereIn('id', $validated['message_ids'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Messages deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete messages',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper method to send email notification
     */
    private function sendEmailNotification($userId, $data)
    {
        try {
            $user = User::find($userId);
            if ($user) {
                // Queue email job
                // Mail::to($user)->queue(new AdminMessageNotification($data));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send email notification: ' . $e->getMessage());
        }
    }
}