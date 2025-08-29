<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EquipmentListing;
use App\Models\EquipmentCategory;
use App\Models\UserProfile;
use App\Enums\EquipmentCondition;
use App\Enums\ListingStatus;
use App\Enums\UserRole;

class EquipmentListingSeeder extends Seeder
{
    public function run(): void
    {
        $categories = EquipmentCategory::all();
        $sellers = UserProfile::where('role', UserRole::SELLER)->get();

        $equipmentData = [
            [
                'category' => 'Marine Engines',
                'title' => 'Caterpillar C18 Marine Engine - 715HP',
                'description' => 'High-performance Caterpillar C18 marine engine with 715 horsepower. Excellent for commercial fishing vessels and medium-sized boats. Recently serviced with new injectors and turbocharger. Complete with engine management system and warranty.',
                'brand' => 'Caterpillar',
                'model' => 'C18',
                'year' => 2020,
                'condition' => EquipmentCondition::EXCELLENT,
                'price' => 12500000.00,
                'specifications' => [
                    'Power' => '715 HP @ 2300 RPM',
                    'Displacement' => '18.1 L',
                    'Cylinders' => '6',
                    'Cooling' => 'Freshwater Cooled',
                    'Weight' => '1,950 kg',
                    'Transmission' => 'ZF 360A',
                ],
                'features' => ['Electronic Engine Management', 'Turbocharger', 'Heat Exchanger', 'Raw Water Pump'],
                'tags' => ['caterpillar', 'marine engine', 'commercial', '715hp'],
                'allows_test_drive' => true,
            ],
            [
                'category' => 'Navigation Equipment',
                'title' => 'Furuno NavNet TZtouch3 16" Multifunction Display',
                'description' => 'Advanced multifunction navigation display with built-in GPS/WAAS receiver, fish finder, radar interface, and chart plotting capabilities. Perfect for professional marine navigation.',
                'brand' => 'Furuno',
                'model' => 'TZT16F',
                'year' => 2022,
                'condition' => EquipmentCondition::NEW,
                'price' => 850000.00,
                'specifications' => [
                    'Screen Size' => '15.6"',
                    'Resolution' => '1920 x 1080',
                    'GPS' => 'Built-in GPS/WAAS',
                    'Fish Finder' => '1kW TruEcho CHIRP',
                    'Connectivity' => 'Ethernet, NMEA 2000, WiFi',
                ],
                'features' => ['TruEcho CHIRP Sonar', 'WiFi Connectivity', 'Smartphone Integration', 'Weather Overlay'],
                'tags' => ['furuno', 'gps', 'navigation', 'fish finder'],
                'allows_test_drive' => false,
            ],
            [
                'category' => 'Safety Equipment',
                'title' => 'SOLAS Life Raft - 25 Person Capacity',
                'description' => 'SOLAS approved inflatable life raft suitable for commercial vessels. Includes survival equipment, emergency rations, and signaling devices. Recently inspected and certified.',
                'brand' => 'Survitec',
                'model' => 'SOLAS-25P',
                'year' => 2021,
                'condition' => EquipmentCondition::GOOD,
                'price' => 2200000.00,
                'specifications' => [
                    'Capacity' => '25 persons',
                    'Compliance' => 'SOLAS/IMO Standards',
                    'Container Type' => 'Fiberglass',
                    'Weight' => '145 kg',
                    'Dimensions' => '95 x 75 x 40 cm',
                ],
                'features' => ['Emergency Rations', 'Fresh Water', 'Signaling Equipment', 'First Aid Kit'],
                'tags' => ['life raft', 'safety', 'solas', 'emergency'],
                'allows_test_drive' => false,
            ],
            [
                'category' => 'Propellers & Shafts',
                'title' => 'Bronze Propeller - 28" x 22" 4-Blade',
                'description' => 'High-quality manganese bronze propeller perfect for displacement hulls. Excellent condition with minor surface scratches. Suitable for engines 200-400 HP.',
                'brand' => 'Michigan Wheel',
                'model' => 'Dyna-Quad',
                'year' => 2019,
                'condition' => EquipmentCondition::GOOD,
                'price' => 485000.00,
                'specifications' => [
                    'Diameter' => '28 inches',
                    'Pitch' => '22 inches',
                    'Blades' => '4',
                    'Material' => 'Manganese Bronze',
                    'Bore' => '2.5 inches',
                    'Rotation' => 'Right Hand',
                ],
                'features' => ['Balanced', 'Polished Finish', 'Keyway Cut', 'Hub Included'],
                'tags' => ['propeller', 'bronze', '4-blade', 'michigan wheel'],
                'allows_test_drive' => true,
            ],
            [
                'category' => 'Communication Systems',
                'title' => 'Icom IC-M506 VHF Marine Radio with AIS',
                'description' => 'Professional VHF marine radio with built-in AIS receiver and GPS. Features Class D DSC, emergency functions, and crystal-clear communication.',
                'brand' => 'Icom',
                'model' => 'IC-M506',
                'year' => 2023,
                'condition' => EquipmentCondition::NEW,
                'price' => 185000.00,
                'specifications' => [
                    'Channels' => 'All International/USCG',
                    'Power Output' => '25W',
                    'AIS' => 'Built-in AIS Receiver',
                    'GPS' => 'Internal GPS',
                    'Display' => '2.3" Color LCD',
                ],
                'features' => ['DSC Class D', 'Man Overboard', 'Noise Cancelling', 'Active Noise Control'],
                'tags' => ['vhf radio', 'icom', 'ais', 'marine communication'],
                'allows_test_drive' => false,
            ],
            [
                'category' => 'Electrical Systems',
                'title' => 'Victron MultiPlus 12/3000/120 Inverter/Charger',
                'description' => 'Professional marine inverter/charger system with advanced battery management. Perfect for yachts and commercial vessels requiring reliable shore power connection.',
                'brand' => 'Victron Energy',
                'model' => 'MultiPlus 12/3000/120',
                'year' => 2022,
                'condition' => EquipmentCondition::EXCELLENT,
                'price' => 425000.00,
                'specifications' => [
                    'Input Voltage' => '12V DC',
                    'Output Power' => '3000W continuous',
                    'Charger Current' => '120A',
                    'Efficiency' => '94%',
                    'Protection' => 'IP21',
                ],
                'features' => ['PowerAssist Technology', 'Remote Monitoring', 'Battery Temperature Sensor', 'Adaptive Charging'],
                'tags' => ['inverter', 'charger', 'victron', 'marine power'],
                'allows_test_drive' => false,
            ],
            [
                'category' => 'Hull & Deck Equipment',
                'title' => 'Lewmar V700 Electric Windlass - 700W',
                'description' => 'Heavy-duty electric anchor windlass suitable for boats up to 12 meters. Includes chain gypsy and rope drum. Recently overhauled with new motor brushes.',
                'brand' => 'Lewmar',
                'model' => 'V700',
                'year' => 2020,
                'condition' => EquipmentCondition::GOOD,
                'price' => 320000.00,
                'specifications' => [
                    'Motor Power' => '700W',
                    'Pulling Power' => '700 kg',
                    'Chain Size' => '8-10mm',
                    'Rope Diameter' => '12-16mm',
                    'Voltage' => '12V DC',
                ],
                'features' => ['Remote Control', 'Manual Override', 'Chain Counter', 'Weatherproof'],
                'tags' => ['windlass', 'anchor', 'lewmar', 'electric'],
                'allows_test_drive' => true,
            ],
            [
                'category' => 'Plumbing & Sanitation',
                'title' => 'Rule-Mate 1100 GPH Automatic Bilge Pump',
                'description' => 'Reliable automatic bilge pump with built-in float switch. Perfect for continuous bilge monitoring and water removal. Includes installation hardware and manual.',
                'brand' => 'Rule',
                'model' => 'Rule-Mate 1100',
                'year' => 2023,
                'condition' => EquipmentCondition::NEW,
                'price' => 45000.00,
                'specifications' => [
                    'Flow Rate' => '1100 GPH',
                    'Voltage' => '12V DC',
                    'Current Draw' => '4.2A',
                    'Outlet' => '1-1/8" hose',
                    'Dimensions' => '8" x 3" x 6"',
                ],
                'features' => ['Automatic Float Switch', 'Ignition Protection', 'Corrosion Resistant', 'Quick Disconnect'],
                'tags' => ['bilge pump', 'automatic', 'rule', 'water pump'],
                'allows_test_drive' => false,
            ],
        ];

        foreach ($equipmentData as $index => $equipment) {
            $category = $categories->where('name', $equipment['category'])->first();
            $seller = $sellers->random();

            $listing = EquipmentListing::create([
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'title' => $equipment['title'],
                'description' => $equipment['description'],
                'brand' => $equipment['brand'],
                'model' => $equipment['model'],
                'year' => $equipment['year'],
                'condition' => $equipment['condition'],
                'price' => $equipment['price'],
                'currency' => 'NGN',
                'is_price_negotiable' => rand(0, 1) ? true : false,
                'is_poa' => false,
                'specifications' => json_encode($equipment['specifications']),
                'features' => json_encode($equipment['features']),
                'location_state' => $seller->state,
                'location_city' => $seller->city,
                'location_address' => $seller->address,
                'latitude' => $this->getRandomLatitude($seller->state),
                'longitude' => $this->getRandomLongitude($seller->state),
                'hide_address' => false,
                'delivery_available' => rand(0, 1) ? true : false,
                'delivery_radius' => rand(0, 1) ? rand(50, 500) : null,
                'delivery_fee' => rand(0, 1) ? rand(5000, 50000) : null,
                'contact_phone' => $seller->phone,
                'contact_email' => $seller->user->email,
                'contact_whatsapp' => $seller->phone,
                'contact_methods' => json_encode(['phone', 'email', 'whatsapp']),
                'availability_hours' => json_encode([
                    'monday' => '08:00-18:00',
                    'tuesday' => '08:00-18:00',
                    'wednesday' => '08:00-18:00',
                    'thursday' => '08:00-18:00',
                    'friday' => '08:00-18:00',
                    'saturday' => '09:00-17:00',
                ]),
                'allows_inspection' => true,
                'allows_test_drive' => $equipment['allows_test_drive'],
                'status' => rand(0, 10) < 8 ? ListingStatus::ACTIVE : ListingStatus::DRAFT,
                'is_featured' => rand(0, 10) < 3 ? true : false,
                'is_verified' => rand(0, 10) < 7 ? true : false,
                'view_count' => rand(10, 500),
                'inquiry_count' => rand(0, 25),
                'images' => json_encode($this->generateSampleImages($equipment['brand'], $equipment['model'])),
                'tags' => json_encode($equipment['tags']),
                'seo_title' => $equipment['title'] . ' - Marine Equipment Nigeria',
                'seo_description' => substr($equipment['description'], 0, 160),
                'published_at' => rand(0, 10) < 8 ? now()->subDays(rand(1, 30)) : null,
                'expires_at' => now()->addDays(rand(30, 90)),
            ]);
        }
    }

    private function getRandomLatitude(string $state): float
    {
        $coordinates = [
            'Lagos' => ['min' => 6.4, 'max' => 6.7],
            'Rivers' => ['min' => 4.7, 'max' => 5.2],
            'Delta' => ['min' => 5.0, 'max' => 6.2],
            'Cross River' => ['min' => 4.8, 'max' => 6.9],
            'Kano' => ['min' => 11.9, 'max' => 12.1],
        ];

        $range = $coordinates[$state] ?? $coordinates['Lagos'];
        return round(rand($range['min'] * 100000, $range['max'] * 100000) / 100000, 6);
    }

    private function getRandomLongitude(string $state): float
    {
        $coordinates = [
            'Lagos' => ['min' => 3.2, 'max' => 3.6],
            'Rivers' => ['min' => 6.8, 'max' => 7.2],
            'Delta' => ['min' => 5.4, 'max' => 6.8],
            'Cross River' => ['min' => 8.2, 'max' => 9.4],
            'Kano' => ['min' => 8.4, 'max' => 8.6],
        ];

        $range = $coordinates[$state] ?? $coordinates['Lagos'];
        return round(rand($range['min'] * 100000, $range['max'] * 100000) / 100000, 6);
    }

    private function generateSampleImages(string $brand, string $model): array
    {
        $imageCount = rand(3, 8);
        $images = [];
        
        for ($i = 1; $i <= $imageCount; $i++) {
            $images[] = [
                'url' => "https://via.placeholder.com/800x600/0066cc/ffffff?text=" . urlencode($brand . " " . $model . " " . $i),
                'alt' => $brand . " " . $model . " - Image " . $i,
                'is_primary' => $i === 1,
                'order' => $i,
            ];
        }

        return $images;
    }
}