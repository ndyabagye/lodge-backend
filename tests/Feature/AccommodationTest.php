<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccommodationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_accommodations()
    {
        Accommodation::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/accommodations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'type', 'base_price']
                ]
            ]);
    }

    public function test_can_filter_accommodations_by_type()
    {
        Accommodation::factory()->create(['type' => 'Villa']);
        Accommodation::factory()->create(['type' => 'Cottage']);

        $response = $this->getJson('/api/v1/accommodations?type=Villa');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_admin_can_create_accommodation()
    {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/accommodations', [
                'name' => 'Test Villa',
                'slug' => 'test-villa',
                'type' => 'Villa',
                'description' => 'A beautiful test villa',
                'short_description' => 'Beautiful villa',
                'max_guests' => 4,
                'num_bedrooms' => 2,
                'num_bathrooms' => 2,
                'num_beds' => 2,
                'base_price' => 200000,
                'weekend_price' => 250000,
                'minimum_stay' => 1,
                'status' => 'available',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Villa');
    }
}
