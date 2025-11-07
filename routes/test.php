<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::get('/test-promote', function () {
    $user = User::find(1);
    $user->promoteToSeller(['business_name' => 'Test Business']);
    return 'User promoted to seller';
});