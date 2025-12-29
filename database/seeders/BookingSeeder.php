<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\User;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'guest')->get();
        $accommodations = Accommodation::all();
        $pricingService = new PricingService;

        foreach ($accommodations as $accommodation) {
            // Create 3-5 bookings per accommodation
            $numBookings = rand(3, 5);

            for ($i = 0; $i < $numBookings; $i++) {
                $checkIn = Carbon::now()->addDays(rand(10, 60));
                $checkOut = $checkIn->copy()->addDays(rand(2, 7));

                $pricing = $pricingService->calculatePrice(
                    $accommodation,
                    $checkIn,
                    $checkOut
                );

                $user = $users->random();

                Booking::create([
                    'booking_number' => Booking::generateBookingNumber(),
                    'user_id' => $user->id,
                    'accommodation_id' => $accommodation->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'num_guests' => rand(1, $accommodation->max_guests),
                    'num_adults' => rand(1, 3),
                    'num_children' => rand(0, 2),
                    'subtotal' => $pricing['subtotal'],
                    'tax_amount' => $pricing['tax_amount'],
                    'cleaning_fee' => $pricing['cleaning_fee'],
                    'total_amount' => $pricing['total_amount'],
                    'payment_status' => ['pending', 'paid', 'paid', 'paid'][rand(0, 3)],
                    'status' => ['pending', 'confirmed', 'confirmed'][rand(0, 2)],
                ]);
            }
        }
    }
}
