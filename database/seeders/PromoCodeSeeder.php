<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::firstOrCreate(
            ['code' => 'WELCOME20'],
            [
                'discount' => 20.00,
                'type' => 'percentage',
                'max_users' => 100,
                'used_count' => 0,
                'status' => 'active',
                'expires_at' => now()->addYear(),
            ]
        );

        PromoCode::firstOrCreate(
            ['code' => 'FLAT10'],
            [
                'discount' => 10.00,
                'type' => 'fixed',
                'max_users' => 50,
                'used_count' => 0,
                'status' => 'active',
                'expires_at' => now()->addYear(),
            ]
        );
    }
}
