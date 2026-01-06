<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\EquipmentListing;
use App\Events\MessageSent;
use App\Mail\NewMessageNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            $userId = Auth::user()->id;

            $conversations = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['buyer.profile', 'seller.profile', 'listing', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($userId) {
                $otherParty = $conversation->buyer_id === $userId
                    ? $conversation->seller
                    : $conversation->buyer;

                $unreadCount = $conversation->messages()
                    ->where('sender_id', '!=', $userId)
                    ->whereNull('read_at')
                    ->count();

                // Handle images safely
                $images = $conversation->listing->images;
                if (is_string($images)) {
                    $images = json_decode($images, true) ?? [];
                } elseif (!is_array($images)) {
                    $images = [];
                }

                return [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => $conversation->listing->price,
                        'currency' => $conversation->listing->currency,
                        'images' => $images,
                    ],
                    'other_user' => [
                        'id' => $otherParty->id,
                        'name' => $otherParty->name,
                        'company' => $otherParty->profile->company_name ?? null,
                        'role' => $otherParty->role->name ?? 'user',
                        'avatar' => $otherParty->profile->avatar_url ?? null,
                        'isOnline' => false,
                        'isVerified' => $otherParty->profile->is_verified ?? false,
                    ],
                    'lastMessage' => $conversation->lastMessage ? [
                        'content' => $conversation->lastMessage->content,
                        'type' => $conversation->lastMessage->type,
                        'senderId' => $conversation->lastMessage->sender_id,
                        'timestamp' => $conversation->lastMessage->created_at,
                        'status' => $conversation->lastMessage->read_at ? 'read' : 'sent',
                    ] : null,
                    'unreadCount' => $unreadCount,
                    'isArchived' => !$conversation->is_active,
                    'updated_at' => $conversation->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or get existing conversation for a listing
     */
    public function createOrGet(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'listing_id' => 'required|exists:equipment_listings,id',
                'initial_message' => 'nullable|string|max:1000',
            ]);

            $userId = Auth::user()->id;
            $listing = EquipmentListing::findOrFail($validated['listing_id']);

            // Prevent users from messaging themselves
            if ($listing->seller_id === $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send a message to yourself',
                ], 400);
            }

            // Check if conversation already exists
            $conversation = Conversation::where('listing_id', $validated['listing_id'])
                ->where('buyer_id', $userId)
                ->where('seller_id', $listing->seller_id)
                ->first();

            if (!$conversation) {
                // Create new conversation
                $conversation = Conversation::create([
                    'listing_id' => $validated['listing_id'],
                    'buyer_id' => $userId,
                    'seller_id' => $listing->seller_id,
                ]);
            }

            // Send initial message if provided
            if (!empty($validated['initial_message'])) {
                $message=  Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $userId,
                    'content' => $validated['initial_message'],
                ]);

                //Send email notification to the receiver (seller)
                $receiver = User::find($listing->seller_id);
                $sender = Auth::user();

                if ($receiver && $receiver->email) {
                    try {
                        Mail::to($receiver->email)->send(new NewMessageNotification($sender, $message));
                    } catch (\Exception $mailEx) {
                        // Log mail error but don't fail the request
                        \Log::error('Mail failed: ' . $mailEx->getMessage());
                    }
                }

                // Update conversation timestamp
                $conversation->touch();
            }

            // Load the conversation with relationships
            $conversation->load(['buyer', 'seller', 'listing', 'messages.sender']);

            // Handle images safely
            $images = $conversation->listing->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?? [];
            } elseif (!is_array($images)) {
                $images = [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversation created successfully',
                'data' => [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => $conversation->listing->price,
                        'currency' => $conversation->listing->currency,
                        'images' => $images,
                    ],
                    'buyer' => [
                        'id' => $conversation->buyer->id,
                        'name' => $conversation->buyer->name,
                        'company' => $conversation->buyer->profile->company_name ?? null,
                    ],
                    'seller' => [
                        'id' => $conversation->seller->id,
                        'name' => $conversation->seller->name,
                        'company' => $conversation->seller->profile->company_name ?? null,
                    ],
                    'messages' => $conversation->messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'sender_id' => $message->sender_id,
                            'sender_name' => $message->sender->name ?? 'Unknown',
                            'read_at' => $message->read_at,
                            'created_at' => $message->created_at,
                        ];
                    }),
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific conversation with all messages
     */
    public function show($id): JsonResponse
    {
        try {
            $userId = Auth::user()->id;

            $conversation = Conversation::where('id', $id)
                ->where(function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->with(['buyer.profile', 'seller.profile', 'listing', 'messages.sender'])
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or access denied',
                ], 404);
            }

            // Mark messages as read for the current user
            $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $otherParty = $conversation->buyer_id === $userId
                ? $conversation->seller
                : $conversation->buyer;

            // Handle images safely
            $images = $conversation->listing->images;
            if (is_string($images)) {
                $images = json_decode($images, true) ?? [];
            } elseif (!is_array($images)) {
                $images = [];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => $conversation->listing->price,
                        'currency' => $conversation->listing->currency,
                        'images' => $images,
                    ],
                    'other_user' => [
                        'id' => $otherParty->id,
                        'name' => $otherParty->name,
                        'company' => $otherParty->profile->company_name ?? null,
                        'avatar' => $otherParty->profile->avatar_url ?? null,
                        'isOnline' => false,
                        'isVerified' => $otherParty->profile->is_verified ?? false,
                    ],
                    'messages' => $conversation->messages->sortBy('created_at')->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'type' => $message->type,
                            'sender_id' => $message->sender_id,
                            'sender_name' => $message->sender->name ?? 'Unknown',
                            'offer_price' => $message->offer_price,
                            'offer_currency' => $message->offer_currency,
                            'read_at' => $message->read_at,
                            'created_at' => $message->created_at,
                        ];
                    }),
                    'unreadCount' => 0,
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message in a conversation
     */
    public function sendMessage(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'message_type' => 'sometimes|string|in:text,offer,attachment,system',
                'offer_amount' => 'nullable|numeric|min:0',
                'offer_currency' => 'nullable|string|size:3',
            ]);

            $userId = Auth::user()->id;

            $conversation = Conversation::where('id', $id)
                ->where(function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or access denied',
                ], 404);
            }

            $messageData = [
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['content'],
                'type' => $validated['message_type'] ?? 'text',
            ];

            if (isset($validated['offer_amount'])) {
                $messageData['offer_price'] = $validated['offer_amount'];
                $messageData['offer_currency'] = $validated['offer_currency'] ?? 'NGN';
            }

            $message = Message::create($messageData);

            $sender = Auth::user();
            $receiverId = $conversation->buyer_id == $userId
                ? $conversation->seller_id
                : $conversation->buyer_id;

            $receiver = User::find($receiverId);

            if ($receiver && $receiver->email) {
                try {
                    Mail::to($receiver->email)->send(new NewMessageNotification($sender, $message));
                } catch (\Exception $mailEx) {
                    \Log::error('Mail failed: ' . $mailEx->getMessage());
                }
            }

            $conversation->touch();
            $message->load('sender');

            try {
                broadcast(new MessageSent($message));
            } catch (\Exception $broadcastEx) {
                \Log::error('Broadcast failed: ' . $broadcastEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name ?? 'Unknown',
                    'offer_price' => $message->offer_price,
                    'offer_currency' => $message->offer_currency,
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $userId = Auth::user()->id;

            $conversation = Conversation::where('id', $id)
                ->where(function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or access denied',
                ], 404);
            }

            $updatedCount = $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read',
                'data' => [
                    'updated_count' => $updatedCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Archive a conversation
     */
    public function archive($id): JsonResponse
    {
        try {
            $userId = Auth::user()->id;

            $conversation = Conversation::where('id', $id)
                ->where(function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found or access denied',
                ], 404);
            }

            $conversation->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archived successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            $unreadCount = Message::whereHas('conversation', function ($query) use ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
                });
            })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'count' => $unreadCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}