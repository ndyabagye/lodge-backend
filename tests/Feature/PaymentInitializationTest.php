<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can initialize payment for their booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 500000,
        'payment_status' => 'pending',
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/payments/bookings/{$booking->id}/initialize", [
            'gateway' => 'stripe',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => [
                'payment',
                'authorization_url',
                'reference',
            ]
        ]);
});

test('cannot initialize payment for already paid booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'payment_status' => 'paid',
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/v1/payments/bookings/{$booking->id}/initialize", [
            'gateway' => 'stripe',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'This booking has already been paid'
        ]);
});

test('cannot initialize payment for another users booking', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)
        ->postJson("/api/v1/payments/bookings/{$booking->id}/initialize", [
            'gateway' => 'stripe',
        ]);

    $response->assertStatus(403);
});

test('can get available payment gateways', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/payments/gateways');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'name',
                    'label',
                    'supports_card',
                    'supports_mobile_money',
                ]
            ]
        ]);
});
