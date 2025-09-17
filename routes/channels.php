<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel for user profile notifications
Broadcast::channel('App.Models.UserProfile.{id}', function ($user, $id) {
    return (int) $user->profile->id === (int) $id;
});

// Channel for messaging - users can only access conversations they're part of
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->buyer_id === $user->profile->id || 
           $conversation->seller_id === $user->profile->id;
});

// Channel for user's private notifications and messages
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->profile->id === (int) $userId;
});

// Global channel for system announcements (optional)
Broadcast::channel('system.announcements', function ($user) {
    return true; // All authenticated users can listen
});
