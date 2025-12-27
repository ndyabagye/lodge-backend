<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'WiFi', 'icon' => 'wifi', 'category' => 'Technology', 'order' => 1],
            ['name' => 'Air Conditioning', 'icon' => 'snowflake', 'category' => 'Comfort', 'order' => 2],
            ['name' => 'Swimming Pool', 'icon' => 'waves', 'category' => 'Recreation', 'order' => 3],
            ['name' => 'Parking', 'icon' => 'car', 'category' => 'Facilities', 'order' => 4],
            ['name' => 'Restaurant', 'icon' => 'utensils', 'category' => 'Dining', 'order' => 5],
            ['name' => 'Gym', 'icon' => 'dumbbell', 'category' => 'Recreation', 'order' => 6],
            ['name' => 'Spa', 'icon' => 'spa', 'category' => 'Recreation', 'order' => 7],
            ['name' => 'Bar', 'icon' => 'glass-martini', 'category' => 'Dining', 'order' => 8],
            ['name' => 'Lake View', 'icon' => 'water', 'category' => 'Views', 'order' => 9],
            ['name' => 'Garden View', 'icon' => 'tree', 'category' => 'Views', 'order' => 10],
            ['name' => 'Room Service', 'icon' => 'concierge-bell', 'category' => 'Service', 'order' => 11],
            ['name' => 'Laundry', 'icon' => 'washer', 'category' => 'Service', 'order' => 12],
            ['name' => 'Kitchen', 'icon' => 'kitchen-set', 'category' => 'Room Features', 'order' => 13],
            ['name' => 'TV', 'icon' => 'tv', 'category' => 'Technology', 'order' => 14],
            ['name' => 'Fireplace', 'icon' => 'fire', 'category' => 'Comfort', 'order' => 15],
            ['name' => 'Balcony', 'icon' => 'home', 'category' => 'Room Features', 'order' => 16],
            ['name' => 'Safe', 'icon' => 'lock', 'category' => 'Security', 'order' => 17],
            ['name' => 'Pet Friendly', 'icon' => 'paw', 'category' => 'Policies', 'order' => 18],
        ];

        foreach ($amenities as $amenity) {
            Amenity::create($amenity);
        }
    }
}
