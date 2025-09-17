<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some test users and listings
        $buyer = \App\Models\UserProfile::where('role', '!=', 'seller')->first();
        $seller = \App\Models\UserProfile::where('role', 'seller')->first();
        $listing = \App\Models\EquipmentListing::first();

        if (!$buyer || !$seller || !$listing) {
            $this->command->info('Skipping test messages - need users and listings first');
            return;
        }

        // Create a test conversation
        $conversation = \App\Models\Conversation::create([
            'listing_id' => $listing->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        // Create some test messages
        $messages = [
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $buyer->id,
                'content' => 'Hi! I\'m interested in your ' . $listing->title . '. Is it still available?',
                'read_at' => now()->subHours(2),
                'created_at' => now()->subHours(2),
            ],
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $seller->id,
                'content' => 'Yes, it\'s still available! It\'s in excellent condition. Would you like to schedule a viewing?',
                'read_at' => now()->subHours(1),
                'created_at' => now()->subHours(1),
            ],
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $buyer->id,
                'content' => 'That would be great! What times work for you this week?',
                'read_at' => null, // Unread message for notification testing
                'created_at' => now()->subMinutes(30),
            ],
            [
                'conversation_id' => $conversation->id,
                'sender_id' => $buyer->id,
                'content' => 'Also, is the price negotiable?',
                'read_at' => null, // Another unread message
                'created_at' => now()->subMinutes(15),
            ],
        ];

        foreach ($messages as $messageData) {
            \App\Models\Message::create($messageData);
        }

        // Update conversation timestamp
        $conversation->touch();

        $this->command->info('Created test conversation with ' . count($messages) . ' messages');
    }
}
