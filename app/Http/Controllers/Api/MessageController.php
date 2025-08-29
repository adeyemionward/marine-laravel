<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function conversations(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $conversations = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['buyer', 'seller', 'listing', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $conversations->map(function ($conversation) use ($userId) {
                    $otherParty = $conversation->buyer_id === $userId ? 
                        $conversation->seller : $conversation->buyer;
                        
                    return [
                        'id' => $conversation->id,
                        'listing' => [
                            'id' => $conversation->listing->id,
                            'title' => $conversation->listing->title,
                            'price' => $conversation->listing->formatted_price,
                        ],
                        'other_party' => [
                            'id' => $otherParty->id,
                            'name' => $otherParty->full_name,
                            'company' => $otherParty->company_name,
                            'is_verified' => $otherParty->is_verified,
                        ],
                        'last_message' => $conversation->lastMessage ? [
                            'content' => $conversation->lastMessage->content,
                            'sent_at' => $conversation->lastMessage->created_at,
                            'is_read' => $conversation->lastMessage->is_read,
                            'is_mine' => $conversation->lastMessage->sender_id === $userId,
                        ] : null,
                        'unread_count' => $conversation->messages()
                            ->where('sender_id', '!=', $userId)
                            ->where('is_read', false)
                            ->count(),
                        'updated_at' => $conversation->updated_at,
                    ];
                }),
                'meta' => [
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'last_page' => $conversations->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $conversation = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })
            ->with(['buyer', 'seller', 'listing'])
            ->findOrFail($id);

            $messages = Message::where('conversation_id', $id)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->paginate(50);

            // Mark messages as read
            Message::where('conversation_id', $id)
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => [
                        'id' => $conversation->id,
                        'listing' => [
                            'id' => $conversation->listing->id,
                            'title' => $conversation->listing->title,
                            'price' => $conversation->listing->formatted_price,
                        ],
                        'participants' => [
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
                        ],
                    ],
                    'messages' => $messages->map(function ($message) use ($userId) {
                        return [
                            'id' => $message->id,
                            'content' => $message->content,
                            'sent_at' => $message->created_at,
                            'is_read' => $message->is_read,
                            'is_mine' => $message->sender_id === $userId,
                            'sender' => [
                                'id' => $message->sender->id,
                                'name' => $message->sender->full_name,
                            ],
                        ];
                    }),
                ],
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'listing_id' => 'required|exists:equipment_listings,id',
                'message' => 'required|string|max:1000',
            ]);

            $userId = Auth::user()->profile->id;
            $listing = \App\Models\EquipmentListing::findOrFail($validated['listing_id']);

            // Check if conversation already exists
            $conversation = Conversation::where('listing_id', $listing->id)
                ->where('buyer_id', $userId)
                ->where('seller_id', $listing->seller_id)
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'listing_id' => $listing->id,
                    'buyer_id' => $userId,
                    'seller_id' => $listing->seller_id,
                ]);
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['message'],
            ]);

            $conversation->touch(); // Update conversation timestamp

            return response()->json([
                'success' => true,
                'message' => 'Conversation started successfully',
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendMessage(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $userId = Auth::user()->profile->id;
            
            $conversation = Conversation::where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                      ->orWhere('seller_id', $userId);
            })->findOrFail($id);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => $validated['message'],
            ]);

            $conversation->touch(); // Update conversation timestamp

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message_id' => $message->id,
                    'sent_at' => $message->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead($id): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $message = Message::where('sender_id', '!=', $userId)
                ->findOrFail($id);

            $message->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}