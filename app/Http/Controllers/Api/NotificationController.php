<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notification summary for the authenticated user
     */
    public function summary(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;

            // Count unread messages
            $unreadMessages = Message::whereHas('conversation', function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();

            // Get recent unread conversations
            $recentConversations = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->whereHas('messages', function ($query) use ($userId) {
                $query->where('sender_id', '!=', $userId)
                      ->where('is_read', false);
            })
            ->with(['buyer', 'seller', 'listing', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($conversation) use ($userId) {
                $otherParty = $conversation->buyer_id === $userId ?
                    $conversation->seller : $conversation->buyer;

                return [
                    'id' => $conversation->id,
                    'listing_title' => $conversation->listing->title,
                    'other_party_name' => $otherParty->full_name,
                    'last_message' => $conversation->lastMessage->content,
                    'sent_at' => $conversation->lastMessage->created_at,
                    'unread_count' => $conversation->messages()
                        ->where('sender_id', '!=', $userId)
                        ->where('is_read', false)
                        ->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_unread_messages' => $unreadMessages,
                    'unread_conversations' => $recentConversations->count(),
                    'recent_conversations' => $recentConversations,
                    'has_notifications' => $unreadMessages > 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed notifications for messages
     */
    public function messages(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;

            $notifications = Message::whereHas('conversation', function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->with(['sender', 'conversation.listing'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $notifications->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'conversation_id' => $message->conversation_id,
                        'content' => $message->content,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->full_name,
                            'company' => $message->sender->company_name,
                        ],
                        'listing' => [
                            'id' => $message->conversation->listing->id,
                            'title' => $message->conversation->listing->title,
                        ],
                        'sent_at' => $message->created_at,
                        'type' => 'message',
                    ];
                }),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch message notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_ids' => 'array',
                'message_ids.*' => 'integer|exists:messages,id',
                'conversation_id' => 'integer|exists:conversations,id',
                'mark_all' => 'boolean',
            ]);

            $userId = Auth::user()->profile->id;

            if ($validated['mark_all'] ?? false) {
                // Mark all unread messages as read
                Message::whereHas('conversation', function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'All notifications marked as read',
                ]);
            }

            if (isset($validated['conversation_id'])) {
                // Mark all messages in a conversation as read
                Message::where('conversation_id', $validated['conversation_id'])
                    ->where('sender_id', '!=', $userId)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'Conversation messages marked as read',
                ]);
            }

            if (isset($validated['message_ids'])) {
                // Mark specific messages as read
                Message::whereIn('id', $validated['message_ids'])
                    ->where('sender_id', '!=', $userId)
                    ->update(['is_read' => true]);

                return response()->json([
                    'success' => true,
                    'message' => 'Selected messages marked as read',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No valid action specified',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
