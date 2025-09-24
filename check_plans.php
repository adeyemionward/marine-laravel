<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubscriptionPlan;

echo "=== SUBSCRIPTION PLANS IN DATABASE ===\n\n";

$plans = SubscriptionPlan::all();

if ($plans->isEmpty()) {
    echo "No subscription plans found!\n";
} else {
    foreach ($plans as $plan) {
        echo "ID: " . $plan->id . "\n";
        echo "Name: " . $plan->name . "\n";
        echo "Price: " . $plan->price . " " . ($plan->currency ?? 'NGN') . "\n";
        echo "Active: " . ($plan->is_active ? 'Yes' : 'No') . "\n";
        echo "Created: " . $plan->created_at . "\n";
        echo "---\n";
    }

    echo "\nTotal Plans: " . $plans->count() . "\n";
    echo "Active Plans: " . $plans->where('is_active', true)->count() . "\n";
}