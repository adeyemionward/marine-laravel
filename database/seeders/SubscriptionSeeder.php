<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\UserProfile;
use App\Enums\UserRole;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = SubscriptionPlan::all();
        $sellers = UserProfile::where('role', UserRole::SELLER)->get();

        $freePlan = $plans->where('tier', 'freemium')->first();
        $premiumPlan = $plans->where('tier', 'premium')->first();
        $enterprisePlan = $plans->where('tier', 'enterprise')->first();

        // Assign subscriptions to sellers
        foreach ($sellers as $index => $seller) {
            // Different subscription patterns
            switch ($index % 3) {
                case 0:
                    // Active premium subscription
                    Subscription::create([
                        'user_id' => $seller->id,
                        'plan_id' => $premiumPlan->id,
                        'status' => 'active',
                        'started_at' => now()->subDays(15),
                        'expires_at' => now()->addDays(15),
                        'auto_renew' => true,
                    ]);
                    break;

                case 1:
                    // Enterprise subscription
                    Subscription::create([
                        'user_id' => $seller->id,
                        'plan_id' => $enterprisePlan->id,
                        'status' => 'active',
                        'started_at' => now()->subDays(5),
                        'expires_at' => now()->addDays(25),
                        'auto_renew' => true,
                    ]);
                    break;

                case 2:
                    // Freemium plan
                    Subscription::create([
                        'user_id' => $seller->id,
                        'plan_id' => $freePlan->id,
                        'status' => 'active',
                        'started_at' => now()->subDays(10),
                        'expires_at' => now()->addDays(20),
                        'auto_renew' => false,
                    ]);
                    break;
            }
        }

        // Add some cancelled/expired subscriptions for testing
        $randomSeller = $sellers->random();
        Subscription::create([
            'user_id' => $randomSeller->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'cancelled',
            'started_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
            'cancelled_at' => now()->subDays(35),
            'auto_renew' => false,
        ]);
    }
}