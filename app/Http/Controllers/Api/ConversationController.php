<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\EquipmentListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;

            $conversations = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['buyer', 'seller', 'listing', 'lastMessage'])
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

                return [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => $conversation->listing->price,
                        'currency' => $conversation->listing->currency,
                        'images' => json_decode($conversation->listing->images ?? '[]', true),
                    ],
                    'other_party' => [
                        'id' => $otherParty->id,
                        'name' => $otherParty->full_name,
                        'company' => $otherParty->company_name,
                        'role' => $otherParty->role,
                    ],
                    'last_message' => $conversation->lastMessage ? [
                        'content' => $conversation->lastMessage->content,
                        'sender_id' => $conversation->lastMessage->sender_id,
                        'created_at' => $conversation->lastMessage->created_at,
                        'read_at' => $conversation->lastMessage->read_at,
                    ] : null,
                    'unread_count' => $unreadCount,
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

            $userId = Auth::user()->profile->id;
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
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $userId,
                    'content' => $validated['initial_message'],
                ]);

                // Update conversation timestamp
                $conversation->touch();
            }

            // Load the conversation with relationships
            $conversation->load(['buyer', 'seller', 'listing', 'messages.sender']);

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
                        'images' => json_decode($conversation->listing->images ?? '[]', true),
                    ],
                    'buyer' => [
                        'id' => $conversation->buyer->id,
                        'name' => $conversation->buyer->full_name,
                        'company' => $conversation->buyer->company_name,
                    ],
                    'seller' => [
                        'id' => $conversation->seller->id,
                        'name' => $conversation->seller->full_name,
                        'company' => $conversation->seller->company_name,
                    ],
                    'messages' => $conversation->messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'sender_id' => $message->sender_id,
                            'sender_name' => $message->sender->full_name,
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
            $userId = Auth::user()->profile->id;

            $conversation = Conversation::where('id', $id)
                ->where(function ($query) use ($userId) {
                    $query->where('buyer_id', $userId)
                          ->orWhere('seller_id', $userId);
                })
                ->with(['buyer', 'seller', 'listing', 'messages.sender'])
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

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $conversation->id,
                    'listing' => [
                        'id' => $conversation->listing->id,
                        'title' => $conversation->listing->title,
                        'price' => $conversation->listing->price,
                        'currency' => $conversation->listing->currency,
                        'images' => json_decode($conversation->listing->images ?? '[]', true),
                    ],
                    'buyer' => [
                        'id' => $conversation->buyer->id,
                        'name' => $conversation->buyer->full_name,
                        'company' => $conversation->buyer->company_name,
                    ],
                    'seller' => [
                        'id' => $conversation->seller->id,
                        'name' => $conversation->seller->full_name,
                        'company' => $conversation->seller->company_name,
                    ],
                    'messages' => $conversation->messages->map(function ($message) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'sender_id' => $message->sender_id,
                            'sender_name' => $message->sender->full_name,
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
            ]);

            $userId = Auth::user()->profile->id;

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

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['content'],
            ]);

            // Update conversation timestamp
            $conversation->touch();

            // Load sender relationship
            $message->load('sender');

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->full_name,
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
}