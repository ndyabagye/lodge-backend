<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Sunset Boat Cruise',
                'category' => 'Water Sports',
                'description' => 'Enjoy a relaxing boat cruise on the lake during sunset with complimentary drinks.',
                'short_description' => 'Romantic sunset cruise on the lake',
                'duration' => 120,
                'adult_price' => 80000,
                'child_price' => 40000,
                'max_participants' => 20,
                'featured' => true,
            ],
            [
                'name' => 'Nature Hiking Trail',
                'category' => 'Adventure',
                'description' => 'Guided hiking through scenic trails with experienced guides.',
                'short_description' => 'Guided nature hike through scenic trails',
                'duration' => 180,
                'adult_price' => 50000,
                'child_price' => 25000,
                'max_participants' => 15,
                'min_age' => 10,
                'featured' => true,
            ],
            [
                'name' => 'Spa Treatment Package',
                'category' => 'Wellness',
                'description' => 'Full body massage and spa treatment for ultimate relaxation.',
                'short_description' => 'Relaxing spa and massage treatment',
                'duration' => 90,
                'price' => 120000,
                'max_participants' => 4,
                'featured' => false,
            ],
        ];

        foreach ($activities as $data) {
            $activity = Activity::create([
                ...$data,
                'slug' => Str::slug($data['name']),
                'status' => 'available',
                'requirements' => 'Comfortable clothing and shoes recommended',
                'safety_info' => 'All safety equipment provided',
                'included' => 'Guide, Equipment, Refreshments',
                'excluded' => 'Personal expenses, Tips',
            ]);

            // Create placeholder images
            for ($i = 1; $i <= 3; $i++) {
                ActivityImage::create([
                    'activity_id' => $activity->id,
                    'url' => "https://picsum.photos/seed/act{$activity->id}{$i}/800/600",
                    'thumbnail_url' => "https://picsum.photos/seed/act{$activity->id}{$i}/400/300",
                    'alt_text' => "{$activity->name} - Image {$i}",
                    'order' => $i,
                    'is_featured' => $i === 1,
                ]);
            }
        }
    }
}
