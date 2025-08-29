<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Enums\SubscriptionTier;
use App\Enums\BillingCycle;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Freemium Plan',
                'tier' => SubscriptionTier::FREEMIUM,
                'description' => 'Free plan with basic features for individual sellers',
                'price' => 0.00,
                'billing_cycle' => BillingCycle::MONTHLY,
                'features' => [
                    'Up to 2 equipment listings',
                    'Basic seller profile',
                    'Email support',
                    'Standard listing visibility',
                ],
                'limits' => [
                    'listings' => 2,
                    'images_per_listing' => 5,
                    'featured_listings' => 0,
                ],
                'max_listings' => 2,
                'max_images_per_listing' => 5,
                'priority_support' => false,
                'analytics_access' => false,
                'custom_branding' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium Plan',
                'tier' => SubscriptionTier::PREMIUM,
                'description' => 'Ideal for established marine equipment dealers',
                'price' => 7500.00,
                'billing_cycle' => BillingCycle::MONTHLY,
                'features' => [
                    'Up to 25 equipment listings',
                    'Enhanced seller profile with company branding',
                    'Priority email support',
                    'Featured listing slots (2 per month)',
                    'Advanced analytics dashboard',
                    'Bulk upload capabilities',
                ],
                'limits' => [
                    'listings' => 25,
                    'images_per_listing' => 20,
                    'featured_listings' => 2,
                ],
                'max_listings' => 25,
                'max_images_per_listing' => 20,
                'priority_support' => true,
                'analytics_access' => true,
                'custom_branding' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise Plan',
                'tier' => SubscriptionTier::ENTERPRISE,
                'description' => 'Comprehensive solution for large marine equipment companies',
                'price' => 20000.00,
                'billing_cycle' => BillingCycle::MONTHLY,
                'features' => [
                    'Unlimited equipment listings',
                    'Full company profile customization',
                    'Dedicated account manager',
                    'Unlimited featured listings',
                    'Advanced analytics & reporting',
                    'API access for integrations',
                    'Custom branding options',
                    'Priority customer support (24/7)',
                ],
                'limits' => [
                    'listings' => -1, // Unlimited
                    'images_per_listing' => 50,
                    'featured_listings' => -1, // Unlimited
                ],
                'max_listings' => -1, // Unlimited
                'max_images_per_listing' => 50,
                'priority_support' => true,
                'analytics_access' => true,
                'custom_branding' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['tier' => $plan['tier'], 'name' => $plan['name']],
                $plan
            );
        }
    }
}