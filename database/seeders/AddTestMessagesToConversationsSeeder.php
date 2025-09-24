<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserProfile;

class AddTestMessagesToConversationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all system conversations
        $conversations = Conversation::where('type', 'system')->get();

        if ($conversations->isEmpty()) {
            // Get existing conversations to add messages to
            $conversations = Conversation::limit(3)->get();
        }

        // Add multiple messages to each conversation
        foreach ($conversations as $conversation) {
            $userId = $conversation->buyer_id;

            // Add user messages
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => 'Hello, I need help with my account settings.',
                'type' => 'text',
                'created_at' => now()->subHours(5)
            ]);

            // Add admin response
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 1, // Admin user ID
                'content' => 'Hello! I\'d be happy to help you with your account settings. What specific issue are you experiencing?',
                'type' => 'text',
                'created_at' => now()->subHours(4)
            ]);

            // Add user follow-up
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => 'I can\'t update my profile picture. It keeps showing an error.',
                'type' => 'text',
                'created_at' => now()->subHours(3)
            ]);

            // Add admin response
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 1,
                'content' => 'Let me check that for you. Can you tell me what error message you\'re seeing? Also, please ensure your image is under 5MB and in JPG or PNG format.',
                'type' => 'text',
                'created_at' => now()->subHours(2)
            ]);

            // Add user response
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'content' => 'It says "Invalid file format". I\'m uploading a JPG file that\'s only 2MB.',
                'type' => 'text',
                'created_at' => now()->subHours(1)
            ]);

            // Add final admin response
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => 1,
                'content' => 'I\'ve identified the issue. There was a temporary problem with our image upload service. Please try again now - it should be working. If you still experience issues, please let me know!',
                'type' => 'text',
                'created_at' => now()->subMinutes(30)
            ]);

            echo "Added 6 test messages to conversation ID: {$conversation->id}\n";
        }

        echo "Test messages added successfully!\n";
    }
}