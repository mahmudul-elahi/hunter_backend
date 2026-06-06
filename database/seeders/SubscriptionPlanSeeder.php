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
                'billing_period' => 'monthly',
                'revenuecat_product_id' => 'vip_members_monthly',
                'revenuecat_entitlement_id' => 'premium',
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
