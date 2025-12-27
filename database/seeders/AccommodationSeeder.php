<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\AccommodationImage;
use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccommodationSeeder extends Seeder
{
    public function run(): void
    {
        $accommodations = [
            [
                'name' => 'Lakeside Villa',
                'type' => 'Villa',
                'description' => 'Luxurious villa with stunning lake views and private beach access. Perfect for families and groups seeking tranquility and exclusivity.',
                'short_description' => 'Luxurious lakeside villa with private beach access',
                'max_guests' => 8,
                'num_bedrooms' => 4,
                'num_bathrooms' => 3,
                'num_beds' => 5,
                'size_sqft' => 3200,
                'base_price' => 450000,
                'weekend_price' => 550000,
                'cleaning_fee' => 50000,
                'minimum_stay' => 2,
                'featured' => true,
            ],
            [
                'name' => 'Garden Cottage',
                'type' => 'Cottage',
                'description' => 'Cozy cottage nestled in lush gardens. Ideal for couples seeking a romantic getaway.',
                'short_description' => 'Cozy cottage in beautiful gardens',
                'max_guests' => 2,
                'num_bedrooms' => 1,
                'num_bathrooms' => 1,
                'num_beds' => 1,
                'size_sqft' => 800,
                'base_price' => 150000,
                'weekend_price' => 180000,
                'cleaning_fee' => 20000,
                'minimum_stay' => 1,
                'featured' => true,
            ],
            [
                'name' => 'Family Suite',
                'type' => 'Suite',
                'description' => 'Spacious family suite with modern amenities and children\'s play area.',
                'short_description' => 'Spacious suite perfect for families',
                'max_guests' => 6,
                'num_bedrooms' => 3,
                'num_bathrooms' => 2,
                'num_beds' => 4,
                'size_sqft' => 1800,
                'base_price' => 280000,
                'weekend_price' => 320000,
                'cleaning_fee' => 35000,
                'minimum_stay' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Hilltop Bungalow',
                'type' => 'Bungalow',
                'description' => 'Modern bungalow with panoramic views of the surrounding hills and valleys.',
                'short_description' => 'Modern bungalow with panoramic views',
                'max_guests' => 4,
                'num_bedrooms' => 2,
                'num_bathrooms' => 2,
                'num_beds' => 2,
                'size_sqft' => 1400,
                'base_price' => 220000,
                'weekend_price' => 260000,
                'cleaning_fee' => 30000,
                'minimum_stay' => 1,
                'featured' => false,
            ],
            [
                'name' => 'Executive Lodge',
                'type' => 'Lodge',
                'description' => 'Premium lodge with business facilities and executive amenities.',
                'short_description' => 'Premium lodge with executive amenities',
                'max_guests' => 2,
                'num_bedrooms' => 1,
                'num_bathrooms' => 1,
                'num_beds' => 1,
                'size_sqft' => 1000,
                'base_price' => 200000,
                'weekend_price' => 230000,
                'cleaning_fee' => 25000,
                'minimum_stay' => 1,
                'featured' => true,
            ],
        ];

        $amenities = Amenity::all();

        foreach ($accommodations as $data) {
            $accommodation = Accommodation::create([
                ...$data,
                'slug' => Str::slug($data['name']),
                'status' => 'available',
            ]);

            // Attach random amenities
            $randomAmenities = $amenities->random(rand(8, 12));
            $accommodation->amenities()->attach($randomAmenities);

            // Create placeholder images
            for ($i = 1; $i <= 4; $i++) {
                AccommodationImage::create([
                    'accommodation_id' => $accommodation->id,
                    'url' => "https://picsum.photos/seed/{$accommodation->id}{$i}/800/600",
                    'thumbnail_url' => "https://picsum.photos/seed/{$accommodation->id}{$i}/400/300",
                    'alt_text' => "{$accommodation->name} - Image {$i}",
                    'order' => $i,
                    'is_featured' => $i === 1,
                ]);
            }
        }
    }
}
