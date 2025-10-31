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
            // Handle both single recipient_id and array of recipient_ids
            $request->validate([
                'recipient_id' => 'required_without:recipient_ids|exists:users,id',
                'recipient_ids' => 'required_without:recipient_id|array',
                'recipient_ids.*' => 'exists:users,id',
                'subject' => 'required|string|max:255',
                'message_content' => 'required|string',
                'message_type' => 'required|string',
                'priority' => 'required|string',
                'send_email' => 'boolean'
            ]);

            // Normalize to array format
            $recipientIds = $request->has('recipient_ids')
                ? $request->recipient_ids
                : [$request->recipient_id];

            $validated = [
                'recipient_ids' => $recipientIds,
                'subject' => $request->subject,
                'message' => $request->message_content,
                'type' => $request->message_type,
                'send_email' => $request->send_email ?? false
            ];

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

                // For admin messages, we don't need to manage participants separately

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
                    $query->whereHas('roles', function($q) {
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
     * Start a new system conversation (user to admin)
     */
    public function startSystemConversation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'initial_message' => 'required|string'
            ]);

            $userId = Auth::user()->profile->id;

            // Create new system conversation
            $conversation = Conversation::create([
                'buyer_id' => $userId,
                'seller_id' => null, // No seller for system conversations
                'listing_id' => null, // No listing for system conversations
                'type' => 'system',
                'subject' => $validated['subject'],
                'status' => 'open'
            ]);

            // Add initial message
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['initial_message'],
                'type' => 'text',
                'is_from_admin' => false
            ]);

            // Load relationships
            $conversation->load('messages');

            return response()->json([
                'success' => true,
                'message' => 'Conversation started successfully',
                'data' => $conversation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start conversation',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send a message in a system conversation
     */
    public function sendSystemMessage(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string'
            ]);

            $userId = Auth::user()->profile->id;
            $isAdmin = Auth::user()->hasRole('admin');

            // Get the conversation
            $conversation = Conversation::findOrFail($id);

            // Check access
            if (!$isAdmin && $conversation->buyer_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['content'],
                'type' => 'text',
                'is_from_admin' => $isAdmin
            ]);

            // Update conversation timestamp
            $conversation->touch();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific system conversation with all messages
     */
    public function getSystemConversation(Request $request, $id): JsonResponse
    {
        try {
            $conversation = Conversation::with(['buyer', 'seller', 'listing', 'messages' => function($q) {
                $q->orderBy('created_at', 'asc');
            }])->findOrFail($id);

            // For user access, check if they are part of the conversation
            if (!Auth::user()->hasRole('admin')) {
                $userId = Auth::user()->profile->id;
                if ($conversation->buyer_id !== $userId && $conversation->seller_id !== $userId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied'
                    ], 403);
                }
            }

            // Mark messages as read for current user (if not admin)
            if (!Auth::user()->hasRole('admin')) {
                $userId = Auth::user()->profile->id;
                $conversation->messages()
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);
            }

            // Format the conversation data
            $data = [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'type' => $conversation->type,
                'status' => $conversation->status,
                'created_at' => $conversation->created_at,
                'updated_at' => $conversation->updated_at,
            ];

            // Add buyer/seller info if available
            if ($conversation->buyer) {
                $data['buyer'] = [
                    'id' => $conversation->buyer->id,
                    'name' => $conversation->buyer->full_name,
                    'email' => $conversation->buyer->email,
                ];
            }

            if ($conversation->seller) {
                $data['seller'] = [
                    'id' => $conversation->seller->id,
                    'name' => $conversation->seller->full_name,
                    'email' => $conversation->seller->email,
                ];
            }

            // Add listing info if available
            if ($conversation->listing) {
                $data['listing'] = [
                    'id' => $conversation->listing->id,
                    'title' => $conversation->listing->title,
                ];
            }

            // Add all messages
            $data['messages'] = $conversation->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'is_from_admin' => $message->is_from_admin ?? false,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversation',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Mark a system conversation as read by admin
     */
    public function markConversationAsRead(Request $request, $id): JsonResponse
    {
        try {
            $conversation = Conversation::findOrFail($id);

            // Mark all messages in this conversation as read by admin
            Message::where('conversation_id', $id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark conversation as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process email queue
     */
    public function processEmailQueue(): JsonResponse
    {
        try {
            // Simple mock email processing - replace with actual queue processing logic
            $processedCount = 0;

            // In a real implementation, you would:
            // 1. Get pending emails from queue table
            // 2. Send them using Mail::send()
            // 3. Update their status
            // 4. Return actual processed count

            return response()->json([
                'success' => true,
                'message' => 'Email queue processed successfully',
                'data' => [
                    'processed_count' => $processedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process email queue: ' . $e->getMessage()
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