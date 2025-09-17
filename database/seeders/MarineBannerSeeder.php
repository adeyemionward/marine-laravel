<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\EquipmentCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarineBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some categories for targeting
        $boatCategory = EquipmentCategory::where('slug', 'boats')->first();
        $engineCategory = EquipmentCategory::where('slug', 'marine-engines')->first();
        $safetyCategory = EquipmentCategory::where('slug', 'safety-equipment')->first();

        $banners = [
            // Hero Banners
            [
                'title' => 'Premium Marine Equipment Sale',
                'description' => 'Up to 30% off on premium boats, engines, and marine equipment. Limited time offer!',
                'position' => Banner::POSITION_HERO,
                'banner_type' => Banner::TYPE_PROMOTIONAL,
                'banner_size' => Banner::SIZE_FULL_WIDTH,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/1920x400/0066cc/ffffff?text=Premium+Marine+Equipment+Sale',
                'link_url' => '/equipment?sale=true',
                'button_text' => 'Shop Sale',
                'button_color' => '#ff6600',
                'background_color' => '#0066cc',
                'text_color' => '#ffffff',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 10,
                'sort_order' => 1,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'dimensions' => ['width' => 1920, 'height' => 400],
                'mobile_dimensions' => ['width' => 375, 'height' => 200],
                'overlay_settings' => ['enabled' => true, 'opacity' => 0.3, 'color' => '#000000'],
                'user_target' => 'all',
            ],
            [
                'title' => 'New Boat Collection 2024',
                'description' => 'Discover our latest collection of premium boats and yachts.',
                'position' => Banner::POSITION_HERO,
                'banner_type' => Banner::TYPE_FEATURED,
                'banner_size' => Banner::SIZE_FULL_WIDTH,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/1920x400/004080/ffffff?text=New+Boat+Collection+2024',
                'link_url' => '/equipment?category=boats&year=2024',
                'button_text' => 'View Collection',
                'button_color' => '#ffffff',
                'background_color' => '#004080',
                'text_color' => '#ffffff',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 9,
                'sort_order' => 2,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'target_category_id' => $boatCategory?->id,
                'start_date' => now(),
                'end_date' => now()->addDays(60),
                'dimensions' => ['width' => 1920, 'height' => 400],
                'mobile_dimensions' => ['width' => 375, 'height' => 200],
            ],

            // Category Row Banners
            [
                'title' => 'Boats & Yachts',
                'description' => 'Explore our premium boat collection',
                'position' => Banner::POSITION_CATEGORY_ROW,
                'banner_type' => Banner::TYPE_CATEGORY,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x200/0066cc/ffffff?text=Boats',
                'link_url' => '/equipment?category=boats',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 8,
                'sort_order' => 1,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'target_category_id' => $boatCategory?->id,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 200],
                'mobile_dimensions' => ['width' => 150, 'height' => 120],
            ],
            [
                'title' => 'Marine Engines',
                'description' => 'High-performance marine engines',
                'position' => Banner::POSITION_CATEGORY_ROW,
                'banner_type' => Banner::TYPE_CATEGORY,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x200/cc6600/ffffff?text=Engines',
                'link_url' => '/equipment?category=marine-engines',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 8,
                'sort_order' => 2,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'target_category_id' => $engineCategory?->id,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 200],
                'mobile_dimensions' => ['width' => 150, 'height' => 120],
            ],
            [
                'title' => 'Safety Equipment',
                'description' => 'Essential marine safety gear',
                'position' => Banner::POSITION_CATEGORY_ROW,
                'banner_type' => Banner::TYPE_CATEGORY,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x200/cc0000/ffffff?text=Safety',
                'link_url' => '/equipment?category=safety-equipment',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 8,
                'sort_order' => 3,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'target_category_id' => $safetyCategory?->id,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 200],
                'mobile_dimensions' => ['width' => 150, 'height' => 120],
            ],

            // Product Promotion Banners
            [
                'title' => 'Flash Sale - Marine Electronics',
                'description' => '48-hour flash sale on navigation and communication equipment',
                'position' => Banner::POSITION_PRODUCT_PROMOTION,
                'banner_type' => Banner::TYPE_PROMOTIONAL,
                'banner_size' => Banner::SIZE_MEDIUM,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/600x300/ff3300/ffffff?text=Flash+Sale+Electronics',
                'link_url' => '/equipment?category=electronics&sale=true',
                'button_text' => 'Shop Now',
                'button_color' => '#ffffff',
                'background_color' => '#ff3300',
                'text_color' => '#ffffff',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 7,
                'sort_order' => 1,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'start_date' => now(),
                'end_date' => now()->addDays(2),
                'dimensions' => ['width' => 600, 'height' => 300],
                'mobile_dimensions' => ['width' => 375, 'height' => 200],
                'max_impressions' => 10000,
                'max_clicks' => 500,
            ],

            // Sidebar Banners
            [
                'title' => 'Marine Insurance',
                'description' => 'Protect your investment with comprehensive marine insurance',
                'position' => Banner::POSITION_SIDEBAR,
                'banner_type' => Banner::TYPE_SERVICE,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x250/006600/ffffff?text=Marine+Insurance',
                'link_url' => '/services/insurance',
                'button_text' => 'Get Quote',
                'button_color' => '#006600',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 5,
                'sort_order' => 1,
                'show_on_mobile' => false,
                'show_on_desktop' => true,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 250],
            ],
            [
                'title' => 'Equipment Financing',
                'description' => 'Flexible financing options for marine equipment purchases',
                'position' => Banner::POSITION_SIDEBAR,
                'banner_type' => Banner::TYPE_SERVICE,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_HOMEPAGE,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x250/660066/ffffff?text=Equipment+Financing',
                'link_url' => '/services/financing',
                'button_text' => 'Learn More',
                'button_color' => '#660066',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 5,
                'sort_order' => 2,
                'show_on_mobile' => false,
                'show_on_desktop' => true,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 250],
            ],

            // Category Page Banner
            [
                'title' => 'Professional Boat Maintenance',
                'description' => 'Expert maintenance services for all boat types',
                'position' => Banner::POSITION_LISTING_TOP,
                'banner_type' => Banner::TYPE_SERVICE,
                'banner_size' => Banner::SIZE_LARGE,
                'display_context' => Banner::CONTEXT_CATEGORY,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/800x200/336699/ffffff?text=Boat+Maintenance+Services',
                'link_url' => '/services/maintenance',
                'button_text' => 'Book Service',
                'button_color' => '#336699',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 6,
                'sort_order' => 1,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'target_category_id' => $boatCategory?->id,
                'start_date' => now(),
                'dimensions' => ['width' => 800, 'height' => 200],
                'mobile_dimensions' => ['width' => 375, 'height' => 150],
            ],

            // Detail Page Sidebar Banner
            [
                'title' => 'Extended Warranty',
                'description' => 'Extend your equipment warranty for peace of mind',
                'position' => Banner::POSITION_DETAIL_SIDEBAR,
                'banner_type' => Banner::TYPE_SERVICE,
                'banner_size' => Banner::SIZE_SMALL,
                'display_context' => Banner::CONTEXT_LISTING_DETAIL,
                'media_type' => 'image',
                'media_url' => 'https://via.placeholder.com/300x200/993366/ffffff?text=Extended+Warranty',
                'link_url' => '/services/warranty',
                'button_text' => 'Get Warranty',
                'button_color' => '#993366',
                'status' => 'active',
                'purchase_status' => 'paid',
                'priority' => 4,
                'sort_order' => 1,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
                'start_date' => now(),
                'dimensions' => ['width' => 300, 'height' => 200],
                'mobile_dimensions' => ['width' => 300, 'height' => 150],
            ],
        ];

        foreach ($banners as $bannerData) {
            // Add default user_target if not set
            if (!isset($bannerData['user_target'])) {
                $bannerData['user_target'] = 'all';
            }
            // Add default created_by (admin user)
            if (!isset($bannerData['created_by'])) {
                $bannerData['created_by'] = 1; // Default to admin user
            }
            Banner::create($bannerData);
        }
    }
}