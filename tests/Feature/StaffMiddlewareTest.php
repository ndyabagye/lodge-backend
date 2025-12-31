<?php

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staff can access staff routes', function () {
    $staff = User::factory()->create(['role' => UserRole::STAFF]);

    $response = $this->actingAs($staff)
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200);
});

test('admin can access staff routes', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $response = $this->actingAs($admin)
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(200);
});

test('guest cannot access staff routes', function () {
    $guest = User::factory()->create(['role' => UserRole::GUEST]);

    $response = $this->actingAs($guest)
        ->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Unauthorized. Staff access required.'
        ]);
});

test('unauthenticated users cannot access staff routes', function () {
    $response = $this->getJson('/api/v1/admin/dashboard');

    $response->assertStatus(401);
});
?>
