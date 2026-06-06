<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sports', 'icon' => 'sports', 'image' => 'sports', 'description' => 'Sports predictions'],
            ['name' => 'Casino', 'icon' => 'casino', 'image' => 'casino', 'description' => 'Casino predictions'],
            ['name' => 'Stocks', 'icon' => 'stocks', 'image' => 'stocks', 'description' => 'Stocks predictions'],
            ['name' => 'Crypto', 'icon' => 'crypto', 'image' => 'crypto', 'description' => 'Crypto predictions'],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(
                ['name' => $data['name']],
                ['icon' => $data['icon'], 'image' => $data['image'], 'description' => $data['description'], 'is_active' => true],
            );
        }
    }
}
