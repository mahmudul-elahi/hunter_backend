<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $premiumUser = User::updateOrCreate(
            ['email' => 'john@example.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_premium' => true,
                'is_active' => true,
                'onboarding_completed' => true,
                'date_of_birth' => '1995-06-15',
                'location' => 'New York, USA',
                'gender' => 'male',
            ]
        );

        $premiumUser->assignRole('user');

        Subscription::updateOrCreate(
            ['user_id' => $premiumUser->id],
            [
                'revenuecat_original_app_user_id' => 'original_user_premium_1',
                'revenuecat_product_id' => 'vip_monthly',
                'revenuecat_entitlement_id' => 'premium',
                'store' => 'app_store',
                'environment' => 'SANDBOX',
                'status' => 'active',
                'price' => 9.99,
                'currency' => 'USD',
                'purchased_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
                'raw_customer_info' => [
                    'id' => 'original_user_premium_1',
                    'subscriptions' => [],
                    'active_entitlements' => [],
                ],
            ]
        );

        $freeUser = User::updateOrCreate(
            ['email' => 'jane@example.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_premium' => false,
                'is_active' => true,
                'onboarding_completed' => false,
                'date_of_birth' => '1999-02-20',
                'location' => 'Las Vegas, USA',
                'gender' => 'female',
            ]
        );

        $freeUser->assignRole('user');
    }
}
