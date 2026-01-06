<?php

/**
 * Quick script to promote a user to seller
 *
 * Usage: php promote_user_to_seller.php
 * This will prompt for the user's email and promote them to seller
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "Enter the email of the user to promote to seller: ";
$email = trim(fgets(STDIN));

$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found with email: {$email}\n";
    exit(1);
}

echo "Found user: {$user->name} ({$user->email})\n";
echo "Current role: " . ($user->getRoleName() ?? 'No role') . "\n";
echo "Is seller: " . ($user->isSeller() ? 'Yes' : 'No') . "\n\n";

echo "Do you want to promote this user to seller? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes') {
    echo "Operation cancelled.\n";
    exit(0);
}

echo "Business name (press Enter to use user's name): ";
$businessName = trim(fgets(STDIN));
if (empty($businessName)) {
    $businessName = $user->name;
}

$sellerData = [
    'business_name' => $businessName,
    'business_type' => 'Individual',
    'verification_status' => 'approved',
    'verified_at' => now(),
    'status' => 'active',
];

$promoted = $user->promoteToSeller($sellerData);

if ($promoted) {
    echo "\n✅ Success! User has been promoted to seller.\n";
    echo "Role: " . ($user->getRoleName() ?? 'No role') . "\n";
    echo "Is seller: " . ($user->isSeller() ? 'Yes' : 'No') . "\n";
    echo "Seller profile created: Yes\n";
} else {
    echo "\n❌ Failed to promote user to seller. Check logs for details.\n";
    exit(1);
}
