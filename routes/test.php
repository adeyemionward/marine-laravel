<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;

// Simple test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Test route working'
    ]);
});

// Test the AdminController directly
Route::get('/test-admin', function () {
    $controller = new AdminController();
    return $controller->publicSettings();
});