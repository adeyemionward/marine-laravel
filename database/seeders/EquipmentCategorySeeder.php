<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentCategory;

class EquipmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Marine Engines',
                'slug' => 'marine-engines',
                'description' => 'High-performance marine engines for various vessel types',
                'icon_name' => 'engine',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Navigation Equipment',
                'slug' => 'navigation-equipment', 
                'description' => 'GPS, radar, and navigation systems for marine vessels',
                'icon_name' => 'compass',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Safety Equipment',
                'slug' => 'safety-equipment',
                'description' => 'Life jackets, flares, and emergency safety gear',
                'icon_name' => 'shield',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Communication Systems',
                'slug' => 'communication-systems',
                'description' => 'VHF radios, satellite communication equipment',
                'icon_name' => 'radio',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Propellers & Shafts',
                'slug' => 'propellers-shafts',
                'description' => 'Marine propellers, drive shafts, and transmission systems',
                'icon_name' => 'propeller',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Hull & Deck Equipment',
                'slug' => 'hull-deck-equipment',
                'description' => 'Anchors, windlasses, cleats, and deck hardware',
                'icon_name' => 'anchor',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Electrical Systems',
                'slug' => 'electrical-systems',
                'description' => 'Marine batteries, inverters, and electrical components',
                'icon_name' => 'battery',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Plumbing & Sanitation',
                'slug' => 'plumbing-sanitation',
                'description' => 'Bilge pumps, toilets, and water system components',
                'icon_name' => 'water',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            EquipmentCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}