<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BannerPricing;

class BannerPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingData = [
            // Header Banner Pricing
            [
                'banner_type' => 'header',
                'position' => 'top',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 50000.00, // ₦50,000 per month
                'premium_multiplier' => 1.5,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 10],
                    ['min_duration' => 6, 'discount_percentage' => 20],
                    ['min_duration' => 12, 'discount_percentage' => 30],
                ],
                'is_active' => true,
                'max_concurrent' => 1,
                'description' => 'Premium header banner placement - most visible position',
                'specifications' => [
                    'width' => '100%',
                    'height' => '100px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],
            [
                'banner_type' => 'header',
                'position' => 'top',
                'duration_type' => 'weekly',
                'duration_value' => 1,
                'base_price' => 15000.00, // ₦15,000 per week
                'premium_multiplier' => 1.5,
                'discount_tiers' => [
                    ['min_duration' => 4, 'discount_percentage' => 10],
                    ['min_duration' => 8, 'discount_percentage' => 20],
                ],
                'is_active' => true,
                'max_concurrent' => 1,
                'description' => 'Weekly header banner placement',
                'specifications' => [
                    'width' => '100%',
                    'height' => '100px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],
            
            // Hero Banner Pricing
            [
                'banner_type' => 'hero',
                'position' => 'middle',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 80000.00, // ₦80,000 per month
                'premium_multiplier' => 1.3,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 15],
                    ['min_duration' => 6, 'discount_percentage' => 25],
                    ['min_duration' => 12, 'discount_percentage' => 35],
                ],
                'is_active' => true,
                'max_concurrent' => 1,
                'description' => 'Hero section banner - prime real estate on homepage',
                'specifications' => [
                    'width' => '100%',
                    'height' => '400px',
                    'formats' => ['jpg', 'png', 'gif', 'mp4'],
                    'max_size' => '5MB',
                ],
            ],
            [
                'banner_type' => 'hero',
                'position' => 'middle',
                'duration_type' => 'weekly',
                'duration_value' => 1,
                'base_price' => 25000.00, // ₦25,000 per week
                'premium_multiplier' => 1.3,
                'discount_tiers' => [
                    ['min_duration' => 4, 'discount_percentage' => 15],
                    ['min_duration' => 8, 'discount_percentage' => 25],
                ],
                'is_active' => true,
                'max_concurrent' => 1,
                'description' => 'Weekly hero section banner',
                'specifications' => [
                    'width' => '100%',
                    'height' => '400px',
                    'formats' => ['jpg', 'png', 'gif', 'mp4'],
                    'max_size' => '5MB',
                ],
            ],
            
            // Sidebar Banner Pricing
            [
                'banner_type' => 'sidebar',
                'position' => 'middle',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 30000.00, // ₦30,000 per month
                'premium_multiplier' => 1.2,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 10],
                    ['min_duration' => 6, 'discount_percentage' => 18],
                    ['min_duration' => 12, 'discount_percentage' => 25],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Sidebar banner placement',
                'specifications' => [
                    'width' => '300px',
                    'height' => '250px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '1MB',
                ],
            ],

            // Bottom Left Banner Pricing
            [
                'banner_type' => 'bottom_left',
                'position' => 'bottom',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 30000.00, // ₦30,000 per month
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 10],
                    ['min_duration' => 6, 'discount_percentage' => 18],
                    ['min_duration' => 12, 'discount_percentage' => 25],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Bottom left banner placement - 1/3 of bottom section',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],
            [
                'banner_type' => 'bottom_left',
                'position' => 'bottom',
                'duration_type' => 'weekly',
                'duration_value' => 1,
                'base_price' => 8000.00, // ₦8,000 per week
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 4, 'discount_percentage' => 10],
                    ['min_duration' => 8, 'discount_percentage' => 18],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Weekly bottom left banner placement',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],

            // Bottom Middle Banner Pricing
            [
                'banner_type' => 'bottom_middle',
                'position' => 'bottom',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 35000.00, // ₦35,000 per month
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 10],
                    ['min_duration' => 6, 'discount_percentage' => 18],
                    ['min_duration' => 12, 'discount_percentage' => 25],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Bottom middle banner placement - center of bottom section',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],
            [
                'banner_type' => 'bottom_middle',
                'position' => 'bottom',
                'duration_type' => 'weekly',
                'duration_value' => 1,
                'base_price' => 10000.00, // ₦10,000 per week
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 4, 'discount_percentage' => 10],
                    ['min_duration' => 8, 'discount_percentage' => 18],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Weekly bottom middle banner placement',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],

            // Bottom Right Banner Pricing
            [
                'banner_type' => 'bottom_right',
                'position' => 'bottom',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 30000.00, // ₦30,000 per month
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 10],
                    ['min_duration' => 6, 'discount_percentage' => 18],
                    ['min_duration' => 12, 'discount_percentage' => 25],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Bottom right banner placement - 1/3 of bottom section',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],
            [
                'banner_type' => 'bottom_right',
                'position' => 'bottom',
                'duration_type' => 'weekly',
                'duration_value' => 1,
                'base_price' => 8000.00, // ₦8,000 per week
                'premium_multiplier' => 1.15,
                'discount_tiers' => [
                    ['min_duration' => 4, 'discount_percentage' => 10],
                    ['min_duration' => 8, 'discount_percentage' => 18],
                ],
                'is_active' => true,
                'max_concurrent' => 2,
                'description' => 'Weekly bottom right banner placement',
                'specifications' => [
                    'width' => '33.33%',
                    'height' => '200px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '2MB',
                ],
            ],

            // Footer Banner Pricing
            [
                'banner_type' => 'footer',
                'position' => 'bottom',
                'duration_type' => 'monthly',
                'duration_value' => 1,
                'base_price' => 25000.00, // ₦25,000 per month
                'premium_multiplier' => 1.1,
                'discount_tiers' => [
                    ['min_duration' => 3, 'discount_percentage' => 8],
                    ['min_duration' => 6, 'discount_percentage' => 15],
                    ['min_duration' => 12, 'discount_percentage' => 20],
                ],
                'is_active' => true,
                'max_concurrent' => 3,
                'description' => 'Footer banner placement',
                'specifications' => [
                    'width' => '100%',
                    'height' => '80px',
                    'formats' => ['jpg', 'png', 'gif'],
                    'max_size' => '1MB',
                ],
            ],
        ];

        foreach ($pricingData as $pricing) {
            BannerPricing::create($pricing);
        }
    }
}
