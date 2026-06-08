<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminSetting::updateOrCreate(
            ['id' => 1],
            [
                'new_subscription' => true,
                'payment_failed' => true,
                'prediction_result' => true,
            ],
        );
    }
}
