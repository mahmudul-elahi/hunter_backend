<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::firstOrCreate(
            ['name' => 'VIP Members'],
            [
                'price' => 99.00,
                'billing_period' => 'month',
                'features' => [
                    'Daily picks',
                    'Win rate access',
                    'Priority notifications',
                    'Premium support',
                ],
                'is_active' => true,
            ]
        );
    }
}
