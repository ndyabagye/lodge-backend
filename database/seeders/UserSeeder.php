<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@lodge.com',
            'phone' => '+256700000000',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserPreference::create([
            'user_id' => $admin->id,
            'email_notifications' => true,
            'sms_notifications' => true,
            'marketing_communications' => false,
        ]);

        // Create staff user
        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => 'staff@lodge.com',
            'phone' => '+256700000001',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        UserPreference::create([
            'user_id' => $staff->id,
        ]);

        // Create 20 guest users
        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'first_name' => "Guest{$i}",
                'last_name' => "User",
                'email' => "guest{$i}@example.com",
                'phone' => "+25670000" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'guest',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            UserPreference::create([
                'user_id' => $user->id,
            ]);
        }
    }
}
